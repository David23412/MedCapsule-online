<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure clean database state for each test
        $this->ensureCleanDatabaseState();
    }

    protected function tearDown(): void
    {
        // Clean up any hanging transactions
        $this->cleanupDatabaseTransactions();
        
        parent::tearDown();
    }

    /**
     * Ensure clean database state before each test
     */
    private function ensureCleanDatabaseState(): void
    {
        try {
            // Rollback any existing transactions
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            // If rollback fails, disconnect and reconnect
            DB::disconnect();
            DB::reconnect();
        }
    }

    /**
     * Clean up database transactions after each test
     */
    private function cleanupDatabaseTransactions(): void
    {
        try {
            // Rollback any remaining transactions
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            // Force disconnect if cleanup fails
            DB::disconnect();
        }
    }
}
