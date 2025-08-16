<?php

namespace Tests\Integration;

use Tests\TestCase;

class StringEnumDetectionTest extends TestCase
{
    /** @test */
    public function no_high_risk_string_comparisons_remain_in_app_code()
    {
        $highRiskPatterns = [
            "== 'admin'",
            "== 'student'", 
            "== 'tutor'",
            "=== 'admin'",
            "=== 'student'",
            "=== 'tutor'",
            "!== 'admin'",
            "!== 'student'",
            "!== 'tutor'",
            "!= 'admin'",
            "!= 'student'",
            "!= 'tutor'",
        ];
        
        $appFiles = $this->getPhpFilesInDirectory(app_path());
        $violations = [];
        
        foreach ($appFiles as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                foreach ($highRiskPatterns as $pattern) {
                    if (strpos($line, $pattern) !== false) {
                        $violations[] = [
                            'file' => str_replace(base_path() . '/', '', $file),
                            'line' => $lineNumber + 1,
                            'content' => trim($line),
                            'pattern' => $pattern
                        ];
                    }
                }
            }
        }
        
        if (!empty($violations)) {
            $message = "Found " . count($violations) . " high-risk string comparisons in app/ code:\n";
            foreach ($violations as $violation) {
                $message .= "- {$violation['file']}:{$violation['line']} - {$violation['pattern']}\n";
                $message .= "  Code: {$violation['content']}\n";
            }
            $this->fail($message);
        }
        
        $this->assertTrue(true, 'No high-risk string comparisons found in app/ code');
    }

    /** @test */
    public function no_string_role_constants_remain_in_models()
    {
        $modelFiles = $this->getPhpFilesInDirectory(app_path('Models'));
        $violations = [];
        
        $stringConstantPatterns = [
            "const.*=.*'admin'",
            "const.*=.*'student'",
            "const.*=.*'tutor'",
            "const.*=.*'pending'",
            "const.*=.*'completed'",
            "const.*=.*'failed'",
            "const.*=.*'active'",
            "const.*=.*'overdue'"
        ];
        
        foreach ($modelFiles as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                foreach ($stringConstantPatterns as $pattern) {
                    if (preg_match("/$pattern/", $line)) {
                        // Skip if it's in a comment or string mapping array
                        if (strpos($line, '//') !== false || 
                            strpos($line, 'STRINGS') !== false ||
                            strpos($line, '_STRINGS') !== false) {
                            continue;
                        }
                        
                        $violations[] = [
                            'file' => str_replace(base_path() . '/', '', $file),
                            'line' => $lineNumber + 1,
                            'content' => trim($line)
                        ];
                    }
                }
            }
        }
        
        if (!empty($violations)) {
            $message = "Found " . count($violations) . " string constants in model files:\n";
            foreach ($violations as $violation) {
                $message .= "- {$violation['file']}:{$violation['line']}\n";
                $message .= "  Code: {$violation['content']}\n";
            }
            $this->fail($message);
        }
        
        $this->assertTrue(true, 'No string constants found in model files');
    }

    /** @test */
    public function database_columns_use_integer_types()
    {
        // Test users.role column
        $userRoleColumn = \DB::select("SHOW COLUMNS FROM users WHERE Field = 'role'")[0];
        $this->assertStringContainsString('tinyint', strtolower($userRoleColumn->Type), 
            'users.role should be tinyint type');
        
        // Test payments.status column (if it exists)
        $statusColumns = \DB::select("SHOW COLUMNS FROM payments WHERE Field = 'status'");
        if (!empty($statusColumns)) {
            $paymentStatusColumn = $statusColumns[0];
            $this->assertStringContainsString('tinyint', strtolower($paymentStatusColumn->Type),
                'payments.status should be tinyint type');
        }
    }

    /** @test */
    public function database_constraints_exist()
    {
        // Check for role constraint
        $roleConstraints = \DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.CHECK_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
            AND CONSTRAINT_NAME LIKE '%users_role%'
        ");
        
        $this->assertNotEmpty($roleConstraints, 'users.role should have check constraint');
        
        // Check for status constraint (if status column exists)
        $statusConstraints = \DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.CHECK_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
            AND CONSTRAINT_NAME LIKE '%payments_status%'
        ");
        
        $statusColumns = \DB::select("SHOW COLUMNS FROM payments WHERE Field = 'status'");
        if (!empty($statusColumns)) {
            $this->assertNotEmpty($statusConstraints, 'payments.status should have check constraint');
        }
    }

    /** @test */
    public function indexes_exist_on_enum_columns()
    {
        // Check for role index
        $roleIndexes = \DB::select("SHOW INDEX FROM users WHERE Column_name = 'role'");
        $this->assertNotEmpty($roleIndexes, 'users.role should have index');
        
        // Check for status index (if status column exists)
        $statusColumns = \DB::select("SHOW COLUMNS FROM payments WHERE Field = 'status'");
        if (!empty($statusColumns)) {
            $statusIndexes = \DB::select("SHOW INDEX FROM payments WHERE Column_name = 'status'");
            $this->assertNotEmpty($statusIndexes, 'payments.status should have index');
        }
    }

    private function getPhpFilesInDirectory(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
}