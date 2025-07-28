<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Faker\Factory as Faker;

class MassiveStressTestSeeder extends Seeder
{
    private $faker;
    private $batchSize = 1000;
    private $totalUsers = 50000;
    private $totalCourses = 1000;
    private $totalQuestions = 100000;
    private $totalAssignments = 25000;
    private $totalNotifications = 200000;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

    public function run()
    {
        echo "🚀 LAUNCHING MASSIVE STRESS TEST DATA SEEDING ENGINE\n";
        echo "Target Data Volume:\n";
        echo "├─ Users: {$this->totalUsers}\n";
        echo "├─ Courses: {$this->totalCourses}\n";
        echo "├─ Questions: {$this->totalQuestions}\n";
        echo "├─ Assignments: {$this->totalAssignments}\n";
        echo "└─ Notifications: {$this->totalNotifications}\n\n";

        $startTime = microtime(true);

        // Disable foreign key checks for faster insertion
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Seed core data
        $this->seedUsers();
        $this->seedCourses();
        $this->seedTopics();
        $this->seedQuestions();
        $this->seedQuizzes();
        $this->seedAssignments();
        $this->seedNotifications();
        $this->seedPayments();
        $this->seedUserProgress();
        $this->seedMistakes();
        $this->seedSessions();
        $this->seedCourseEnrollments();
        $this->seedTopicLocks();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $totalTime = microtime(true) - $startTime;
        echo "\n✅ MASSIVE STRESS TEST DATA SEEDING COMPLETED\n";
        echo "Total Execution Time: " . round($totalTime, 2) . " seconds\n";
        echo "Records Created: " . number_format($this->getTotalRecordsCreated()) . "\n";
        echo "Average Records/Second: " . number_format($this->getTotalRecordsCreated() / $totalTime) . "\n";
    }

    private function seedUsers()
    {
        echo "👥 Seeding {$this->totalUsers} users...\n";
        
        $this->truncateTable('users');
        
        $batches = ceil($this->totalUsers / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $users = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $this->totalUsers);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $users[] = [
                    'name' => $this->faker->name,
                    'email' => "stresstest{$i}@example.com",
                    'email_verified_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
                    'password' => Hash::make('password123'),
                    'role' => $this->faker->randomElement(['student', 'tutor', 'admin']),
                    'is_active' => $this->faker->boolean(90), // 90% active
                    'last_login' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
                    'profile_data' => json_encode([
                        'bio' => $this->faker->paragraph,
                        'preferences' => [
                            'notifications' => $this->faker->boolean,
                            'theme' => $this->faker->randomElement(['light', 'dark']),
                            'language' => $this->faker->randomElement(['en', 'es', 'fr'])
                        ]
                    ]),
                    'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
                    'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                ];
            }
            
            DB::table('users')->insert($users);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedCourses()
    {
        echo "📚 Seeding {$this->totalCourses} courses...\n";
        
        $this->truncateTable('courses');
        
        $batches = ceil($this->totalCourses / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $courses = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $this->totalCourses);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $courses[] = [
                    'title' => "Stress Test Course {$i}: " . $this->faker->sentence(3),
                    'description' => $this->faker->paragraphs(3, true),
                    'category' => $this->faker->randomElement(['Mathematics', 'Science', 'History', 'Literature', 'Programming', 'Medicine']),
                    'difficulty_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced', 'expert']),
                    'price' => $this->faker->randomFloat(2, 0, 999.99),
                    'duration_hours' => $this->faker->numberBetween(10, 200),
                    'instructor_id' => $this->faker->numberBetween(1, min(1000, $this->totalUsers)),
                    'is_active' => $this->faker->boolean(85), // 85% active
                    'enrollment_limit' => $this->faker->optional(0.3)->numberBetween(10, 1000),
                    'metadata' => json_encode([
                        'tags' => $this->faker->words(5),
                        'prerequisites' => $this->faker->optional(0.4)->words(3),
                        'learning_outcomes' => $this->faker->sentences(5)
                    ]),
                    'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
                    'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                ];
            }
            
            DB::table('courses')->insert($courses);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedTopics()
    {
        echo "📖 Seeding topics...\n";
        
        $this->truncateTable('topics');
        
        $totalTopics = $this->totalCourses * 10; // 10 topics per course on average
        $batches = ceil($totalTopics / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $topics = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $totalTopics);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $courseId = ($i % $this->totalCourses) + 1;
                
                $topics[] = [
                    'title' => "Topic {$i}: " . $this->faker->sentence(2),
                    'description' => $this->faker->paragraph,
                    'course_id' => $courseId,
                    'order_index' => ($i % 10) + 1,
                    'content' => $this->faker->paragraphs(10, true),
                    'is_locked' => $this->faker->boolean(20), // 20% locked
                    'unlock_requirements' => json_encode([
                        'previous_topics' => $this->faker->optional(0.6)->numberBetween(1, 3),
                        'minimum_score' => $this->faker->optional(0.4)->numberBetween(70, 90)
                    ]),
                    'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
                    'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                ];
            }
            
            DB::table('topics')->insert($topics);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedQuestions()
    {
        echo "❓ Seeding {$this->totalQuestions} questions...\n";
        
        $this->truncateTable('questions');
        
        $batches = ceil($this->totalQuestions / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $questions = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $this->totalQuestions);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $topicId = $this->faker->numberBetween(1, $this->totalCourses * 10);
                
                $options = [
                    'A' => $this->faker->sentence,
                    'B' => $this->faker->sentence,
                    'C' => $this->faker->sentence,
                    'D' => $this->faker->sentence,
                ];
                
                $correctAnswer = $this->faker->randomElement(['A', 'B', 'C', 'D']);
                
                $questions[] = [
                    'question_text' => "Question {$i}: " . $this->faker->sentence . "?",
                    'question_type' => $this->faker->randomElement(['multiple_choice', 'true_false', 'short_answer']),
                    'topic_id' => $topicId,
                    'difficulty_level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
                    'options' => json_encode($options),
                    'correct_answer' => $correctAnswer,
                    'explanation' => $this->faker->paragraph,
                    'points' => $this->faker->numberBetween(1, 10),
                    'time_limit_seconds' => $this->faker->numberBetween(30, 300),
                    'metadata' => json_encode([
                        'tags' => $this->faker->words(3),
                        'source' => $this->faker->optional(0.3)->company,
                        'references' => $this->faker->optional(0.2)->urls(2)
                    ]),
                    'is_active' => $this->faker->boolean(90),
                    'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
                    'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                ];
            }
            
            DB::table('questions')->insert($questions);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedQuizzes()
    {
        echo "📝 Seeding quizzes...\n";
        
        $this->truncateTable('quizzes');
        
        $totalQuizzes = $this->totalUsers * 5; // 5 quizzes per user on average
        $batches = ceil($totalQuizzes / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $quizzes = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $totalQuizzes);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $userId = ($i % $this->totalUsers) + 1;
                $questionCount = $this->faker->numberBetween(5, 50);
                
                // Generate random question IDs
                $questionIds = [];
                for ($j = 0; $j < $questionCount; $j++) {
                    $questionIds[] = $this->faker->numberBetween(1, $this->totalQuestions);
                }
                
                $quizzes[] = [
                    'title' => "Quiz {$i}: " . $this->faker->sentence(3),
                    'user_id' => $userId,
                    'course_id' => $this->faker->numberBetween(1, $this->totalCourses),
                    'questions_data' => json_encode($questionIds),
                    'total_questions' => $questionCount,
                    'time_limit_minutes' => $this->faker->numberBetween(10, 120),
                    'status' => $this->faker->randomElement(['draft', 'active', 'completed', 'expired']),
                    'settings' => json_encode([
                        'shuffle_questions' => $this->faker->boolean,
                        'show_results_immediately' => $this->faker->boolean,
                        'allow_retakes' => $this->faker->boolean,
                        'passing_score' => $this->faker->numberBetween(60, 90)
                    ]),
                    'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                    'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                ];
            }
            
            DB::table('quizzes')->insert($quizzes);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedAssignments()
    {
        echo "📋 Seeding {$this->totalAssignments} assignments...\n";
        
        $this->truncateTable('assignments');
        
        $batches = ceil($this->totalAssignments / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $assignments = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $this->totalAssignments);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $dueDate = $this->faker->dateTimeBetween('now', '+3 months');
                $status = $this->faker->randomElement(['pending', 'in_progress', 'submitted', 'graded', 'overdue']);
                
                $assignments[] = [
                    'title' => "Assignment {$i}: " . $this->faker->sentence(4),
                    'description' => $this->faker->paragraphs(5, true),
                    'instructions' => $this->faker->paragraphs(3, true),
                    'tutor_id' => $this->faker->numberBetween(1, min(1000, $this->totalUsers)),
                    'student_id' => $this->faker->numberBetween(1, $this->totalUsers),
                    'course_id' => $this->faker->numberBetween(1, $this->totalCourses),
                    'due_date' => $dueDate,
                    'status' => $status,
                    'max_points' => $this->faker->numberBetween(50, 200),
                    'submission_type' => $this->faker->randomElement(['text', 'file', 'both']),
                    'requirements' => json_encode([
                        'word_count_min' => $this->faker->optional(0.6)->numberBetween(500, 2000),
                        'file_types_allowed' => $this->faker->optional(0.4)->randomElements(['pdf', 'doc', 'docx', 'txt'], 2),
                        'plagiarism_check' => $this->faker->boolean
                    ]),
                    'submission_content' => $status === 'submitted' || $status === 'graded' ? $this->faker->paragraphs(10, true) : null,
                    'submitted_at' => $status === 'submitted' || $status === 'graded' ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
                    'grade' => $status === 'graded' ? $this->faker->numberBetween(60, 100) : null,
                    'feedback' => $status === 'graded' ? $this->faker->paragraphs(2, true) : null,
                    'graded_at' => $status === 'graded' ? $this->faker->dateTimeBetween('-2 weeks', 'now') : null,
                    'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
                    'updated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                ];
            }
            
            DB::table('assignments')->insert($assignments);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedNotifications()
    {
        echo "🔔 Seeding {$this->totalNotifications} notifications...\n";
        
        $this->truncateTable('notifications');
        
        $batches = ceil($this->totalNotifications / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $notifications = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $this->totalNotifications);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $isRead = $this->faker->boolean(60); // 60% read
                
                $notifications[] = [
                    'user_id' => $this->faker->numberBetween(1, $this->totalUsers),
                    'title' => "Notification {$i}: " . $this->faker->sentence(4),
                    'message' => $this->faker->paragraphs(2, true),
                    'type' => $this->faker->randomElement(['info', 'warning', 'success', 'error', 'reminder']),
                    'category' => $this->faker->randomElement(['assignment', 'quiz', 'course', 'payment', 'system']),
                    'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
                    'is_read' => $isRead,
                    'read_at' => $isRead ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
                    'data' => json_encode([
                        'action_url' => $this->faker->optional(0.7)->url,
                        'related_id' => $this->faker->optional(0.5)->numberBetween(1, 1000),
                        'expires_at' => $this->faker->optional(0.3)->dateTimeBetween('now', '+1 month')
                    ]),
                    'delivery_channels' => json_encode($this->faker->randomElements(['email', 'sms', 'push', 'in_app'], 2)),
                    'delivered_at' => $this->faker->optional(0.9)->dateTimeBetween('-1 month', 'now'),
                    'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
                    'updated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                ];
            }
            
            DB::table('notifications')->insert($notifications);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedPayments()
    {
        echo "💳 Seeding payments...\n";
        
        $this->truncateTable('payments');
        
        $totalPayments = $this->totalUsers * 2; // 2 payments per user on average
        $batches = ceil($totalPayments / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $payments = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $totalPayments);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $amount = $this->faker->randomFloat(2, 10, 500);
                $status = $this->faker->randomElement(['pending', 'processing', 'completed', 'failed', 'refunded']);
                
                $payments[] = [
                    'user_id' => $this->faker->numberBetween(1, $this->totalUsers),
                    'course_id' => $this->faker->optional(0.8)->numberBetween(1, $this->totalCourses),
                    'amount' => $amount,
                    'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
                    'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'bank_transfer', 'stripe']),
                    'transaction_id' => 'stress_' . $i . '_' . uniqid(),
                    'status' => $status,
                    'gateway_response' => json_encode([
                        'gateway_id' => $this->faker->uuid,
                        'response_code' => $status === 'completed' ? '200' : $this->faker->randomElement(['400', '401', '402', '500']),
                        'message' => $status === 'completed' ? 'Payment successful' : 'Payment failed'
                    ]),
                    'processed_at' => in_array($status, ['completed', 'failed', 'refunded']) ? $this->faker->dateTimeBetween('-6 months', 'now') : null,
                    'refunded_at' => $status === 'refunded' ? $this->faker->dateTimeBetween('-3 months', 'now') : null,
                    'refund_amount' => $status === 'refunded' ? $amount : null,
                    'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                    'updated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                ];
            }
            
            DB::table('payments')->insert($payments);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedUserProgress()
    {
        echo "📈 Seeding user progress...\n";
        
        $this->truncateTable('user_progress');
        
        $totalProgress = $this->totalUsers * 20; // 20 progress records per user
        $batches = ceil($totalProgress / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $progressRecords = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $totalProgress);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $progressRecords[] = [
                    'user_id' => $this->faker->numberBetween(1, $this->totalUsers),
                    'course_id' => $this->faker->numberBetween(1, $this->totalCourses),
                    'topic_id' => $this->faker->optional(0.8)->numberBetween(1, $this->totalCourses * 10),
                    'total_quizzes' => $this->faker->numberBetween(0, 50),
                    'completed_quizzes' => $this->faker->numberBetween(0, 30),
                    'total_questions' => $this->faker->numberBetween(0, 1000),
                    'correct_answers' => $this->faker->numberBetween(0, 800),
                    'total_time_spent' => $this->faker->numberBetween(0, 36000), // seconds
                    'average_score' => $this->faker->randomFloat(2, 0, 100),
                    'streak_days' => $this->faker->numberBetween(0, 365),
                    'last_activity' => $this->faker->dateTimeBetween('-1 month', 'now'),
                    'completion_percentage' => $this->faker->randomFloat(2, 0, 100),
                    'milestones_reached' => json_encode($this->faker->randomElements(['first_quiz', 'week_streak', 'perfect_score', 'course_complete'], 2)),
                    'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                    'updated_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
                ];
            }
            
            DB::table('user_progress')->insert($progressRecords);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedMistakes()
    {
        echo "❌ Seeding mistakes...\n";
        
        $this->truncateTable('mistakes');
        
        $totalMistakes = $this->totalUsers * 50; // 50 mistakes per user
        $batches = ceil($totalMistakes / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $mistakes = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $totalMistakes);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $mistakes[] = [
                    'user_id' => $this->faker->numberBetween(1, $this->totalUsers),
                    'question_id' => $this->faker->numberBetween(1, $this->totalQuestions),
                    'quiz_id' => $this->faker->optional(0.8)->numberBetween(1, $this->totalUsers * 5),
                    'topic_id' => $this->faker->numberBetween(1, $this->totalCourses * 10),
                    'incorrect_answer' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
                    'correct_answer' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
                    'mistake_type' => $this->faker->randomElement(['conceptual', 'calculation', 'reading', 'time_pressure']),
                    'difficulty_level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
                    'time_taken_seconds' => $this->faker->numberBetween(5, 300),
                    'confidence_level' => $this->faker->numberBetween(1, 5),
                    'review_count' => $this->faker->numberBetween(0, 10),
                    'last_reviewed' => $this->faker->optional(0.6)->dateTimeBetween('-1 month', 'now'),
                    'mastered' => $this->faker->boolean(30), // 30% mastered
                    'notes' => $this->faker->optional(0.3)->paragraph,
                    'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                    'updated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                ];
            }
            
            DB::table('mistakes')->insert($mistakes);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedSessions()
    {
        echo "🔐 Seeding sessions...\n";
        
        $this->truncateTable('sessions');
        
        $totalSessions = $this->totalUsers * 3; // 3 sessions per user
        $batches = ceil($totalSessions / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $sessions = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $totalSessions);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $lastActivity = $this->faker->dateTimeBetween('-1 week', 'now')->getTimestamp();
                
                $sessions[] = [
                    'id' => \Str::random(40),
                    'user_id' => $this->faker->numberBetween(1, $this->totalUsers),
                    'ip_address' => $this->faker->ipv4,
                    'user_agent' => $this->faker->userAgent,
                    'payload' => base64_encode(json_encode([
                        'session_data' => $this->faker->words(10, true),
                        'csrf_token' => \Str::random(40),
                        'last_page' => $this->faker->url
                    ])),
                    'last_activity' => $lastActivity,
                ];
            }
            
            DB::table('sessions')->insert($sessions);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedCourseEnrollments()
    {
        echo "🎓 Seeding course enrollments...\n";
        
        $this->truncateTable('course_enrollments');
        
        $totalEnrollments = $this->totalUsers * 5; // 5 enrollments per user
        $batches = ceil($totalEnrollments / $this->batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $enrollments = [];
            $batchStart = $batch * $this->batchSize;
            $batchEnd = min(($batch + 1) * $this->batchSize, $totalEnrollments);
            
            for ($i = $batchStart; $i < $batchEnd; $i++) {
                $enrolledAt = $this->faker->dateTimeBetween('-1 year', 'now');
                $status = $this->faker->randomElement(['active', 'completed', 'dropped', 'suspended']);
                
                $enrollments[] = [
                    'user_id' => $this->faker->numberBetween(1, $this->totalUsers),
                    'course_id' => $this->faker->numberBetween(1, $this->totalCourses),
                    'enrolled_at' => $enrolledAt,
                    'status' => $status,
                    'progress' => $this->faker->numberBetween(0, 100),
                    'completion_date' => $status === 'completed' ? $this->faker->dateTimeBetween($enrolledAt, 'now') : null,
                    'grade' => $status === 'completed' ? $this->faker->randomFloat(2, 60, 100) : null,
                    'certificate_issued' => $status === 'completed' ? $this->faker->boolean(80) : false,
                    'last_accessed' => $this->faker->dateTimeBetween('-1 month', 'now'),
                    'time_spent_minutes' => $this->faker->numberBetween(0, 10000),
                    'created_at' => $enrolledAt,
                    'updated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                ];
            }
            
            DB::table('course_enrollments')->insert($enrollments);
            echo "   Batch " . ($batch + 1) . "/{$batches} completed\n";
        }
    }

    private function seedTopicLocks()
    {
        echo "🔒 Topic locks table removed - skipping...\n";
    }

    private function truncateTable($tableName)
    {
        try {
            DB::statement("TRUNCATE TABLE {$tableName}");
        } catch (\Exception $e) {
            echo "   Warning: Could not truncate {$tableName} - table might not exist\n";
        }
    }

    private function getTotalRecordsCreated()
    {
        $tables = [
            'users' => $this->totalUsers,
            'courses' => $this->totalCourses,
            'topics' => $this->totalCourses * 10,
            'questions' => $this->totalQuestions,
            'quizzes' => $this->totalUsers * 5,
            'assignments' => $this->totalAssignments,
            'notifications' => $this->totalNotifications,
            'payments' => $this->totalUsers * 2,
            'user_progress' => $this->totalUsers * 20,
            'mistakes' => $this->totalUsers * 50,
            'sessions' => $this->totalUsers * 3,
            'course_enrollments' => $this->totalUsers * 5,
        ];

        return array_sum($tables);
    }
}