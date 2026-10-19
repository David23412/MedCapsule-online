<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class RestoreDatabase extends Command
{
    protected $signature = 'db:restore {filename} {--dry-run : Validate the backup without changing data}';
    protected $description = 'Validate and transactionally restore a legacy JSON database backup';

    public function handle(): int
    {
        try {
            $directory = realpath(storage_path('app/backups'));
            $filename = (string) $this->argument('filename');
            $path = $directory ? realpath($directory . DIRECTORY_SEPARATOR . $filename) : false;
            if (!$path || basename($filename) !== $filename || dirname($path) !== $directory || !is_file($path)) {
                throw new InvalidArgumentException('Choose a backup file inside storage/app/backups.');
            }
            $backup = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($backup) || !$backup || array_is_list($backup)) {
                throw new InvalidArgumentException('The backup must contain a nonempty object of table names and row lists.');
            }

            $dependencies = [];
            foreach ($backup as $table => $records) {
                if (!is_string($table) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $table) || !Schema::hasTable($table)) {
                    throw new InvalidArgumentException('The backup contains an unknown table.');
                }
                if (!is_array($records) || !array_is_list($records)) {
                    throw new InvalidArgumentException("Invalid row list for {$table}.");
                }
                $columns = Schema::getColumnListing($table);
                $shape = null;
                foreach ($records as $record) {
                    if (!is_array($record) || !$record || array_is_list($record) || array_diff(array_keys($record), $columns)) {
                        throw new InvalidArgumentException("Invalid columns in {$table}.");
                    }
                    foreach ($record as $value) {
                        if (!is_scalar($value) && $value !== null) {
                            throw new InvalidArgumentException("Invalid value in {$table}.");
                        }
                    }
                    $keys = array_keys($record);
                    sort($keys);
                    if ($shape !== null && $keys !== $shape) {
                        throw new InvalidArgumentException("Inconsistent row columns in {$table}.");
                    }
                    $shape = $keys;
                }
                $dependencies[$table] = array_values(array_filter(array_column(Schema::getForeignKeys($table), 'foreign_table'),
                    fn ($parent) => $parent !== $table && array_key_exists($parent, $backup)));
            }
            $ordered = [];
            while ($dependencies) {
                $ready = array_keys(array_filter($dependencies, fn ($parents) => !array_diff($parents, $ordered)));
                if (!$ready) {
                    throw new InvalidArgumentException('Cyclic table dependencies require a database-native restore.');
                }
                foreach ($ready as $table) {
                    $ordered[] = $table;
                    unset($dependencies[$table]);
                }
            }
            if (DB::getDriverName() === 'mysql') {
                $engines = DB::table('information_schema.tables')->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->whereIn('TABLE_NAME', $ordered)->pluck('ENGINE');
                if ($engines->contains(fn ($engine) => strtoupper((string) $engine) !== 'INNODB')) {
                    throw new InvalidArgumentException('Restore requires transactional InnoDB tables.');
                }
            }
            if ($this->option('dry-run')) {
                $this->info('Backup structure and table dependencies are valid. No data changed; row constraints are checked during restore.');
                return self::SUCCESS;
            }
            // DELETE is transactional; TRUNCATE implicitly commits on MySQL. Keep FK checks enabled.
            DB::transaction(function () use ($backup, $ordered) {
                foreach (array_reverse($ordered) as $table) {
                    DB::table($table)->delete();
                }
                foreach ($ordered as $table) {
                    foreach (array_chunk($backup[$table], 100) as $records) {
                        DB::table($table)->insert($records);
                    }
                }
            });
            $this->info('Database restore completed successfully.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Do not echo SQL bindings: backup rows may contain private student data.
            $this->error($e instanceof InvalidArgumentException
                ? $e->getMessage() : 'Restore failed. No changes were committed. Check backup format and database constraints.');
            return self::FAILURE;
        }
    }
}
