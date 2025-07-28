<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix courses table - add is_active column if missing
        if (Schema::hasTable('courses') && !Schema::hasColumn('courses', 'is_active')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('updated_at');
                $table->index('is_active', 'idx_courses_is_active');
            });
        }

        // Fix assignments table - add user_id column if missing (should already exist)
        if (Schema::hasTable('assignments') && !Schema::hasColumn('assignments', 'user_id')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->after('tutor_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index('user_id', 'idx_assignments_user_id');
            });
        }

        // Create quizzes table if it doesn't exist
        if (!Schema::hasTable('quizzes')) {
            Schema::create('quizzes', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('course_id')->nullable();
                $table->json('questions_data');
                $table->integer('total_questions')->default(0);
                $table->integer('time_limit_minutes')->nullable();
                $table->enum('status', ['draft', 'active', 'completed', 'expired'])->default('draft');
                $table->json('settings')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('course_id')->references('id')->on('courses')->onDelete('set null');
                
                // Critical indexes for performance
                $table->index('user_id', 'idx_quizzes_user_id');
                $table->index('course_id', 'idx_quizzes_course_id');
                $table->index('status', 'idx_quizzes_status');
                $table->index('created_at', 'idx_quizzes_created_at');
            });
        }



        // Create questions table if it doesn't exist
        if (!Schema::hasTable('questions')) {
            Schema::create('questions', function (Blueprint $table) {
                $table->id();
                $table->text('question_text');
                $table->enum('question_type', ['multiple_choice', 'true_false', 'short_answer'])->default('multiple_choice');
                $table->unsignedBigInteger('topic_id')->nullable();
                $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
                $table->json('options')->nullable();
                $table->string('correct_answer');
                $table->text('explanation')->nullable();
                $table->integer('points')->default(1);
                $table->integer('time_limit_seconds')->nullable();
                $table->json('metadata')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                // Performance indexes
                $table->index('topic_id', 'idx_questions_topic_id');
                $table->index('difficulty_level', 'idx_questions_difficulty');
                $table->index('question_type', 'idx_questions_type');
                $table->index('is_active', 'idx_questions_is_active');
            });
        }

        // Create topics table if it doesn't exist
        if (!Schema::hasTable('topics')) {
            Schema::create('topics', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('course_id');
                $table->integer('order_index')->default(0);
                $table->longText('content')->nullable();
                $table->boolean('is_locked')->default(false);
                $table->json('unlock_requirements')->nullable();
                $table->timestamps();

                $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
                
                // Performance indexes
                $table->index('course_id', 'idx_topics_course_id');
                $table->index('order_index', 'idx_topics_order');
                $table->index('is_locked', 'idx_topics_is_locked');
            });
        }

        // Create mistakes table if it doesn't exist
        if (!Schema::hasTable('mistakes')) {
            Schema::create('mistakes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('question_id');
                $table->unsignedBigInteger('quiz_id')->nullable();
                $table->unsignedBigInteger('topic_id')->nullable();
                $table->string('incorrect_answer');
                $table->string('correct_answer');
                $table->enum('mistake_type', ['conceptual', 'calculation', 'reading', 'time_pressure'])->default('conceptual');
                $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
                $table->integer('time_taken_seconds')->nullable();
                $table->integer('confidence_level')->nullable();
                $table->integer('review_count')->default(0);
                $table->timestamp('last_reviewed')->nullable();
                $table->boolean('mastered')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
                $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('set null');
                $table->foreign('topic_id')->references('id')->on('topics')->onDelete('set null');
                
                // Critical performance indexes
                $table->index('user_id', 'idx_mistakes_user_id');
                $table->index('question_id', 'idx_mistakes_question_id');
                $table->index('topic_id', 'idx_mistakes_topic_id');
                $table->index('mastered', 'idx_mistakes_mastered');
                $table->index('created_at', 'idx_mistakes_created_at');
            });
        }

        // Create user_progress table if it doesn't exist
        if (!Schema::hasTable('user_progress')) {
            Schema::create('user_progress', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('course_id')->nullable();
                $table->unsignedBigInteger('topic_id')->nullable();
                $table->integer('total_quizzes')->default(0);
                $table->integer('completed_quizzes')->default(0);
                $table->integer('total_questions')->default(0);
                $table->integer('correct_answers')->default(0);
                $table->integer('total_time_spent')->default(0); // in seconds
                $table->decimal('average_score', 5, 2)->default(0);
                $table->integer('streak_days')->default(0);
                $table->timestamp('last_activity')->nullable();
                $table->decimal('completion_percentage', 5, 2)->default(0);
                $table->json('milestones_reached')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('course_id')->references('id')->on('courses')->onDelete('set null');
                $table->foreign('topic_id')->references('id')->on('topics')->onDelete('set null');
                
                // Performance indexes
                $table->index('user_id', 'idx_user_progress_user_id');
                $table->index('course_id', 'idx_user_progress_course_id');
                $table->index('topic_id', 'idx_user_progress_topic_id');
                $table->index('last_activity', 'idx_user_progress_last_activity');
                $table->index('completion_percentage', 'idx_user_progress_completion');
            });
        }

        // Create course_enrollments table if it doesn't exist
        if (!Schema::hasTable('course_enrollments')) {
            Schema::create('course_enrollments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('course_id');
                $table->timestamp('enrolled_at');
                $table->enum('status', ['active', 'completed', 'dropped', 'suspended'])->default('active');
                $table->decimal('progress_percentage', 5, 2)->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
                
                // Performance indexes
                $table->index('user_id', 'idx_course_enrollments_user_id');
                $table->index('course_id', 'idx_course_enrollments_course_id');
                $table->index('status', 'idx_course_enrollments_status');
                $table->index('enrolled_at', 'idx_course_enrollments_enrolled_at');
                
                // Unique constraint to prevent duplicate enrollments
                $table->unique(['user_id', 'course_id'], 'unique_user_course_enrollment');
            });
        }



        // Create payments table if it doesn't exist
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('course_id');
                $table->string('transaction_id')->unique();
                $table->decimal('amount', 10, 2);
                $table->string('payment_method'); // 'fawry', 'vodafone_cash', etc.
                $table->string('status'); // 'pending', 'completed', 'failed', 'expired'
                $table->json('payment_data')->nullable(); // Store gateway-specific data
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
                
                // Performance indexes
                $table->index('user_id', 'idx_payments_user_id');
                $table->index('course_id', 'idx_payments_course_id');
                $table->index('transaction_id', 'idx_payments_transaction_id');
                $table->index('status', 'idx_payments_status');
                $table->index('paid_at', 'idx_payments_paid_at');
            });
        }





        // Add missing indexes to existing tables
        $this->addMissingIndexes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to handle foreign key constraints
        Schema::dropIfExists('payments');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('user_progress');
        Schema::dropIfExists('mistakes');
        Schema::dropIfExists('topics');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quizzes');

        // Remove added columns
        if (Schema::hasColumn('courses', 'is_active')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropIndex('idx_courses_is_active');
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('assignments', 'user_id')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }

    private function addMissingIndexes(): void
    {
        // Add indexes to existing tables that might be missing them
        
        // Users table indexes
        if (Schema::hasTable('users')) {
            if (!$this->indexExists('users', 'email')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->index('email', 'idx_users_email');
                });
            }
            if (!$this->indexExists('users', 'role')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->index('role', 'idx_users_role');
                });
            }
        }

        // Assignments table indexes
        if (Schema::hasTable('assignments')) {
            if (!$this->indexExists('assignments', 'tutor_id')) {
                Schema::table('assignments', function (Blueprint $table) {
                    $table->index('tutor_id', 'idx_assignments_tutor_id');
                });
            }
        }

        // Notifications table indexes
        if (Schema::hasTable('notifications')) {
            if (!$this->indexExists('notifications', 'user_id')) {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->index('user_id', 'idx_notifications_user_id');
                });
            }
            if (!$this->indexExists('notifications', 'is_read')) {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->index('is_read', 'idx_notifications_is_read');
                });
            }
        }

        // Sessions table indexes
        if (Schema::hasTable('sessions')) {
            if (!$this->indexExists('sessions', 'user_id')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->index('user_id', 'idx_sessions_user_id');
                });
            }
            if (!$this->indexExists('sessions', 'last_activity')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->index('last_activity', 'idx_sessions_last_activity');
                });
            }
        }
    }

    private function indexExists(string $table, string $column): bool
    {
        try {
            // Database-agnostic index checking
            $connection = \DB::connection();
            $driver = $connection->getDriverName();
            
            if ($driver === 'sqlite') {
                // SQLite specific index check
                $indexes = \DB::select("PRAGMA index_list({$table})");
                foreach ($indexes as $index) {
                    $indexInfo = \DB::select("PRAGMA index_info({$index->name})");
                    foreach ($indexInfo as $info) {
                        if ($info->name === $column) {
                            return true;
                        }
                    }
                }
                return false;
            } else {
                // MySQL/PostgreSQL specific index check
                $indexes = \DB::select("SHOW INDEX FROM {$table} WHERE Column_name = ?", [$column]);
                return !empty($indexes);
            }
        } catch (\Exception $e) {
            // If index checking fails, assume it doesn't exist and let the migration continue
            return false;
        }
    }
}; 