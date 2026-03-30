<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('topics')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::unprepared('DROP TRIGGER IF EXISTS topics_before_insert_defaults');
            DB::unprepared('DROP TRIGGER IF EXISTS topics_before_update_defaults');

            DB::unprepared(<<<'SQL'
CREATE TRIGGER topics_before_insert_defaults
BEFORE INSERT ON topics
FOR EACH ROW
BEGIN
    IF NEW.case_type IS NULL OR TRIM(NEW.case_type) = '' THEN
        SET NEW.case_type = 'quiz';
    END IF;

    IF NEW.is_locked IS NULL THEN
        SET NEW.is_locked = 0;
    END IF;

    IF NEW.is_exam IS NULL THEN
        SET NEW.is_exam = 0;
    END IF;

    IF NEW.display_order IS NULL OR NEW.display_order <= 0 THEN
        SET NEW.display_order = (
            SELECT COALESCE(MAX(t.display_order), 0) + 1
            FROM topics t
            WHERE t.course_id = NEW.course_id
        );
    END IF;
END
SQL);

            DB::unprepared(<<<'SQL'
CREATE TRIGGER topics_before_update_defaults
BEFORE UPDATE ON topics
FOR EACH ROW
BEGIN
    IF NEW.case_type IS NULL OR TRIM(NEW.case_type) = '' THEN
        SET NEW.case_type = COALESCE(NULLIF(TRIM(OLD.case_type), ''), 'quiz');
    END IF;

    IF NEW.is_locked IS NULL THEN
        SET NEW.is_locked = COALESCE(OLD.is_locked, 0);
    END IF;

    IF NEW.is_exam IS NULL THEN
        SET NEW.is_exam = COALESCE(OLD.is_exam, 0);
    END IF;

    IF NEW.display_order IS NULL OR NEW.display_order <= 0 THEN
        SET NEW.display_order = COALESCE(OLD.display_order, (
            SELECT COALESCE(MAX(t.display_order), 0) + 1
            FROM topics t
            WHERE t.course_id = NEW.course_id AND t.id <> OLD.id
        ));
    END IF;
END
SQL);
        } elseif ($driver === 'sqlite') {
            // SQLite triggers use different syntax; the application layer handles these
            // defaults, so no triggers are needed for testing environments.
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS topics_before_insert_defaults');
        DB::unprepared('DROP TRIGGER IF EXISTS topics_before_update_defaults');
    }
};
