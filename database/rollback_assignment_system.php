<?php

/**
 * Emergency Rollback Script for Tutor Assignment System
 * 
 * This script provides safe rollback capabilities for the assignment system
 * Run this if you need to revert changes quickly
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignmentSystemRollback
{
    public function rollbackAll()
    {
        echo "Starting Assignment System Rollback...\n";
        
        try {
            // Backup critical data before rollback
            $this->backupCriticalData();
            
            // Drop views
            $this->dropViews();
            
            // Drop indexes
            $this->dropIndexes();
            
            // Optionally drop tables (commented out for safety)
            // $this->dropTables();
            
            echo "Rollback completed successfully!\n";
            
        } catch (Exception $e) {
            echo "Rollback failed: " . $e->getMessage() . "\n";
            echo "Please check the database manually.\n";
        }
    }
    
    private function backupCriticalData()
    {
        echo "Backing up critical data...\n";
        
        $timestamp = date('Y_m_d_H_i_s');
        
        // Backup assignments
        DB::statement("CREATE TABLE assignments_backup_{$timestamp} AS SELECT * FROM assignments");
        
        // Backup skipped questions
        DB::statement("CREATE TABLE skipped_questions_backup_{$timestamp} AS SELECT * FROM skipped_questions");
        
        echo "Data backed up to tables with suffix _{$timestamp}\n";
    }
    
    private function dropViews()
    {
        echo "Dropping database views...\n";
        
        DB::statement('DROP VIEW IF EXISTS assignment_dashboard_view');
        DB::statement('DROP VIEW IF EXISTS student_progress_view');
        
        echo "Views dropped.\n";
    }
    
    private function dropIndexes()
    {
        echo "Dropping performance indexes...\n";
        
        $indexes = [
            'assignments' => [
                'idx_assignments_status_deadline',
                'idx_assignments_tutor_status_deadline',
                'idx_assignments_student_course_status',
                'idx_assignments_topic_status',
                'idx_assignments_completed_status'
            ],
            'skipped_questions' => [
                'idx_skipped_time_session',
                'idx_skipped_user_time'
            ],
            'topics' => [
                'idx_topics_course_locked_order',
                'idx_topics_user_mastered',
                'idx_topics_attempt_mastered'
            ],
            'courses' => [
                'idx_courses_year_price_currency',
                'idx_courses_cache_version'
            ]
        ];
        
        foreach ($indexes as $table => $tableIndexes) {
            foreach ($tableIndexes as $index) {
                try {
                    DB::statement("DROP INDEX {$index} ON {$table}");
                    echo "Dropped index {$index} from {$table}\n";
                } catch (Exception $e) {
                    echo "Warning: Could not drop index {$index}: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "Indexes dropped.\n";
    }
    
    private function dropTables()
    {
        echo "WARNING: This will drop assignment system tables!\n";
        echo "Uncomment this section only if you're sure.\n";
        
        /*
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('skipped_questions');
        echo "Assignment system tables dropped.\n";
        */
    }
    
    public function listBackups()
    {
        echo "Available backup tables:\n";
        
        $tables = DB::select("SHOW TABLES LIKE '%_backup_%'");
        
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            echo "- {$tableName}\n";
        }
    }
    
    public function restoreFromBackup($backupSuffix)
    {
        echo "Restoring from backup with suffix: {$backupSuffix}\n";
        
        try {
            // Restore assignments
            DB::statement("TRUNCATE TABLE assignments");
            DB::statement("INSERT INTO assignments SELECT * FROM assignments_backup_{$backupSuffix}");
            
            // Restore skipped questions
            DB::statement("TRUNCATE TABLE skipped_questions");
            DB::statement("INSERT INTO skipped_questions SELECT * FROM skipped_questions_backup_{$backupSuffix}");
            
            echo "Data restored successfully from backup_{$backupSuffix}\n";
            
        } catch (Exception $e) {
            echo "Restore failed: " . $e->getMessage() . "\n";
        }
    }
}

// Usage examples:
// $rollback = new AssignmentSystemRollback();
// $rollback->rollbackAll();
// $rollback->listBackups();
// $rollback->restoreFromBackup('2025_07_20_12_30_45');

echo "Assignment System Rollback Script Loaded.\n";
echo "Uncomment the usage section at the bottom to execute.\n";