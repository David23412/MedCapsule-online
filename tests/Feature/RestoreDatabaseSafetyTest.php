<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RestoreDatabaseSafetyTest extends TestCase
{
    use RefreshDatabase;
    private string $filename;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('restore_test_parents', function (Blueprint $table) { $table->id(); $table->string('label'); });
        Schema::create('restore_test_children', function (Blueprint $table) {
            $table->id(); $table->foreignId('parent_id')->constrained('restore_test_parents');
        });
        DB::table('restore_test_parents')->insert(['id' => 1, 'label' => 'original']);
        DB::table('restore_test_children')->insert(['id' => 1, 'parent_id' => 1]);
        File::ensureDirectoryExists(storage_path('app/backups'));
        $this->filename = 'test-restore-' . bin2hex(random_bytes(8)) . '.json';
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('app/backups/' . $this->filename));
        Schema::dropIfExists('restore_test_children');
        Schema::dropIfExists('restore_test_parents');
        parent::tearDown();
    }

    private function backup(array $data): void
    {
        File::put(storage_path('app/backups/' . $this->filename), json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function test_valid_backup_restores_in_dependency_order_and_dry_run_preserves_data(): void
    {
        $this->backup(['restore_test_children' => [['id' => 2, 'parent_id' => 2]],
            'restore_test_parents' => [['id' => 2, 'label' => 'restored']]]);
        $this->artisan('db:restore', ['filename' => $this->filename, '--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseHas('restore_test_parents', ['id' => 1, 'label' => 'original']);
        $this->artisan('db:restore', ['filename' => $this->filename])->assertSuccessful();
        $this->assertDatabaseHas('restore_test_parents', ['id' => 2, 'label' => 'restored']);
        $this->assertDatabaseHas('restore_test_children', ['parent_id' => 2]);
        $this->assertDatabaseMissing('restore_test_parents', ['id' => 1]);
    }

    public function test_invalid_json_table_columns_and_path_never_clear_existing_data(): void
    {
        foreach (['{broken', '[]', '{}', '{"missing_table":[]}', '{"restore_test_parents":[{"unknown":1}]}'] as $json) {
            File::put(storage_path('app/backups/' . $this->filename), $json);
            $this->artisan('db:restore', ['filename' => $this->filename])->assertFailed();
            $this->assertDatabaseHas('restore_test_parents', ['id' => 1, 'label' => 'original']);
        }
        $this->artisan('db:restore', ['filename' => '../backups/' . $this->filename])->assertFailed();
    }

    public function test_insert_failure_rolls_back_every_table_and_constraints_remain_enabled(): void
    {
        $this->backup(['restore_test_parents' => [['id' => 2, 'label' => 'restored']],
            'restore_test_children' => [['id' => 2, 'parent_id' => 99999]]]);
        $this->artisan('db:restore', ['filename' => $this->filename])->assertFailed();
        $this->assertDatabaseHas('restore_test_parents', ['id' => 1, 'label' => 'original']);
        $this->assertDatabaseHas('restore_test_children', ['id' => 1, 'parent_id' => 1]);
        $this->assertDatabaseMissing('restore_test_parents', ['id' => 2]);
        $this->assertSame(1, (int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys);
    }
}
