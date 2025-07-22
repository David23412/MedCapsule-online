<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use App\Models\Payment;
use App\Models\Assignment;
use App\Models\UserTopicProgress;
use App\Models\TutorStudentRelationship;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ComprehensiveTestSeeder extends Seeder
{
    /**
     * Run the comprehensive test seeder.
     * Creates realistic test data for all major models and scenarios.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Comprehensive Test Seeder...');
        
        DB::beginTransaction();
        
        try {
            // Step 1: Create diverse users
            $this->createUsers();
            
            // Step 2: Create courses and topics
            $this->createCoursesAndTopics();
            
            // Step 3: Create questions
            $this->createQuestions();
            
            // Step 4: Create quiz attempts and progress
            $this->createQuizAttemptsAndProgress();
            
            // Step 5: Create tutor-student relationships
            $this->createTutorStudentRelationships();
            
            // Step 6: Create payments
            $this->createPayments();
            
            // Step 7: Create assignments
            $this->createAssignments();
            
            // Step 8: Create comprehensive notifications
            $this->createNotifications();
            
            DB::commit();
            $this->command->info('✅ Comprehensive Test Seeder completed successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeder failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create diverse users for testing
     */
    private function createUsers(): void
    {
        $this->command->info('👥 Creating test users...');
        
        // Admin users
        User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'xp' => 5000,
                'study_streak_days' => 30,
                'correct_answers_count' => 500,
                'total_questions_attempted' => 600,
            ]
        );
        
        // Tutors
        for ($i = 1; $i <= 3; $i++) {
            User::firstOrCreate(
                ['email' => "tutor{$i}@test.com"],
                [
                    'name' => "Test Tutor {$i}",
                    'password' => Hash::make('password'),
                    'role' => 'tutor',
                    'email_verified_at' => now(),
                    'xp' => rand(2000, 4000),
                    'study_streak_days' => rand(10, 50),
                    'correct_answers_count' => rand(200, 400),
                    'total_questions_attempted' => rand(250, 500),
                ]
            );
        }
        
        // Students with varying progress levels
        $studentProfiles = [
            ['name' => 'Beginner Student', 'xp' => 100, 'streak' => 3, 'correct' => 20, 'total' => 30],
            ['name' => 'Intermediate Student', 'xp' => 1500, 'streak' => 15, 'correct' => 150, 'total' => 200],
            ['name' => 'Advanced Student', 'xp' => 3000, 'streak' => 45, 'correct' => 300, 'total' => 350],
            ['name' => 'Struggling Student', 'xp' => 50, 'streak' => 1, 'correct' => 10, 'total' => 50],
            ['name' => 'Consistent Student', 'xp' => 2000, 'streak' => 60, 'correct' => 200, 'total' => 250],
        ];
        
        foreach ($studentProfiles as $index => $profile) {
            User::firstOrCreate(
                ['email' => "student" . ($index + 1) . "@test.com"],
                [
                    'name' => $profile['name'],
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'email_verified_at' => now(),
                    'xp' => $profile['xp'],
                    'study_streak_days' => $profile['streak'],
                    'correct_answers_count' => $profile['correct'],
                    'total_questions_attempted' => $profile['total'],
                    'university' => 'Test University ' . ($index + 1),
                ]
            );
        }
    }
    
    /**
     * Create courses and topics
     */
    private function createCoursesAndTopics(): void
    {
        $this->command->info('📚 Creating courses and topics...');
        
        $courses = [
            ['name' => 'Basic Anatomy', 'price' => 0, 'description' => 'Fundamental anatomy concepts'],
            ['name' => 'Advanced Physiology', 'price' => 99.99, 'description' => 'Advanced physiological processes'],
            ['name' => 'Medical Terminology', 'price' => 49.99, 'description' => 'Essential medical terms'],
            ['name' => 'Pharmacology Basics', 'price' => 0, 'description' => 'Introduction to pharmacology'],
        ];
        
        foreach ($courses as $courseData) {
            $course = Course::firstOrCreate(
                ['name' => $courseData['name']],
                $courseData
            );
            
            // Create topics for each course
            $topicNames = [
                'Basic Anatomy' => ['Heart Structure', 'Lung Function', 'Bone Composition', 'Muscle Types'],
                'Advanced Physiology' => ['Cardiac Cycle', 'Respiratory Mechanics', 'Neural Pathways', 'Hormonal Regulation'],
                'Medical Terminology' => ['Prefixes', 'Suffixes', 'Root Words', 'Abbreviations'],
                'Pharmacology Basics' => ['Drug Classifications', 'Dosage Calculations', 'Side Effects', 'Interactions'],
            ];
            
            foreach ($topicNames[$courseData['name']] as $topicName) {
                Topic::firstOrCreate(
                    ['name' => $topicName, 'course_id' => $course->id],
                    [
                        'description' => "Topic covering {$topicName}",
                        'display_order' => array_search($topicName, $topicNames[$courseData['name']]) + 1,
                    ]
                );
            }
        }
        
        // Enroll students in courses
        $students = User::where('role', 'student')->get();
        $courses = Course::all();
        
        foreach ($students as $student) {
            // Enroll in all free courses
            $student->enrollInAllFreeCourses();
            
            // Some students also have paid courses
            if (rand(1, 3) === 1) {
                $paidCourse = $courses->where('price', '>', 0)->random();
                $student->enrollInCourse($paidCourse->id);
            }
        }
    }
    
    /**
     * Create test questions
     */
    private function createQuestions(): void
    {
        $this->command->info('❓ Creating test questions...');
        
        $topics = Topic::all();
        
        foreach ($topics as $topic) {
            for ($i = 1; $i <= 10; $i++) {
                Question::firstOrCreate([
                    'topic_id' => $topic->id,
                    'question' => "Test question {$i} for {$topic->name}?",
                ], [
                    'correct_answer' => 'A',
                    'option_a' => 'Correct answer',
                    'option_b' => 'Incorrect option 1',
                    'option_c' => 'Incorrect option 2',
                    'option_d' => 'Incorrect option 3',
                    'explanation' => "This is the explanation for question {$i} in {$topic->name}.",
                ]);
            }
        }
    }
    
    /**
     * Create quiz attempts and progress data
     */
    private function createQuizAttemptsAndProgress(): void
    {
        $this->command->info('📊 Creating quiz attempts and progress...');
        
        $students = User::where('role', 'student')->get();
        $questions = Question::all();
        
        foreach ($students as $student) {
            // Create quiz attempts over the past 30 days
            for ($day = 30; $day >= 0; $day--) {
                $attemptDate = now()->subDays($day);
                
                // Skip some days randomly to create realistic patterns
                if (rand(1, 4) === 1) continue;
                
                // Create 1-3 quiz attempts per active day
                $attemptsPerDay = rand(1, 3);
                
                for ($attempt = 0; $attempt < $attemptsPerDay; $attempt++) {
                    $quizQuestions = $questions->random(rand(5, 15));
                    $correctAnswers = 0;
                    $totalQuestions = $quizQuestions->count();
                    
                    // Simulate different accuracy levels based on student profile
                    $accuracyRate = match($student->name) {
                        'Beginner Student' => 0.4,
                        'Struggling Student' => 0.2,
                        'Intermediate Student' => 0.7,
                        'Advanced Student' => 0.85,
                        'Consistent Student' => 0.8,
                        default => 0.6
                    };
                    
                    $correctAnswers = floor($totalQuestions * $accuracyRate);
                    
                    // Create question data array
                    $questionData = [];
                    foreach ($quizQuestions as $index => $question) {
                        $isCorrect = $index < $correctAnswers;
                        $questionData[] = [
                            'question_id' => $question->id,
                            'user_answer' => $isCorrect ? $question->correct_answer : 'B',
                            'is_correct' => $isCorrect
                        ];
                    }
                    
                    $quizAttempt = QuizAttempt::create([
                        'user_id' => $student->id,
                        'topic_id' => $quizQuestions->first()->topic_id,
                        'quiz_type' => 'normal',
                        'study_mode' => 'quiz',
                        'source' => 'seeder',
                        'score' => $correctAnswers,
                        'total_questions' => $totalQuestions,
                        'correct_answers' => $correctAnswers,
                        'percentage_grade' => ($correctAnswers / $totalQuestions) * 100,
                        'duration_seconds' => rand(300, 1800), // 5-30 minutes
                        'question_data' => $questionData,
                        'created_at' => $attemptDate,
                        'updated_at' => $attemptDate,
                    ]);
                    
                    // Create mistakes for incorrect answers
                    $incorrectCount = $totalQuestions - $correctAnswers;
                    $incorrectQuestions = $quizQuestions->random($incorrectCount);
                    
                    foreach ($incorrectQuestions as $question) {
                        Mistake::firstOrCreate([
                            'user_id' => $student->id,
                            'question_id' => $question->id,
                        ], [
                            'submitted_answer' => ['B', 'C', 'D'][rand(0, 2)], // Wrong answers
                            'question_text' => $question->question,
                            'correct_answer' => $question->correct_answer,
                            'last_attempt_date' => $attemptDate,
                            'created_at' => $attemptDate,
                            'updated_at' => $attemptDate,
                        ]);
                    }
                }
            }
            
            // Update user stats based on quiz attempts
            $userAttempts = QuizAttempt::where('user_id', $student->id)->get();
            $totalCorrect = $userAttempts->sum('correct_answers');
            $totalQuestions = $userAttempts->sum('total_questions');
            
            $student->update([
                'correct_answers_count' => $totalCorrect,
                'total_questions_attempted' => $totalQuestions,
                'xp' => $student->calculateXP(),
            ]);
            
            // Update study streak
            $student->updateStudyStreak();
            
            // Create topic progress
            $topics = Topic::whereIn('id', $userAttempts->pluck('topic_id')->unique())->get();
            
            foreach ($topics as $topic) {
                $topicAttempts = $userAttempts->where('topic_id', $topic->id);
                $topicCorrect = $topicAttempts->sum('correct_answers');
                $topicTotal = $topicAttempts->sum('total_questions');
                
                UserTopicProgress::updateOrCreate([
                    'user_id' => $student->id,
                    'topic_id' => $topic->id,
                ], [
                    'percentage_grade' => min(100, ($topicCorrect / max($topicTotal, 1)) * 100),
                    'last_attempt_date' => $topicAttempts->max('created_at'),
                ]);
            }
        }
    }
    
    /**
     * Create tutor-student relationships
     */
    private function createTutorStudentRelationships(): void
    {
        $this->command->info('👨‍🏫 Creating tutor-student relationships...');
        
        $tutors = User::where('role', 'tutor')->get();
        $students = User::where('role', 'student')->get();
        
        foreach ($tutors as $tutor) {
            // Assign 2-3 students to each tutor
            $assignedStudents = $students->random(rand(2, 3));
            
            foreach ($assignedStudents as $student) {
                TutorStudentRelationship::firstOrCreate([
                    'tutor_id' => $tutor->id,
                    'student_id' => $student->id,
                ], [
                    'assigned_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }
    
    /**
     * Create payment records
     */
    private function createPayments(): void
    {
        $this->command->info('💳 Creating payment records...');
        
        $students = User::where('role', 'student')->get();
        $paidCourses = Course::where('price', '>', 0)->get();
        
        foreach ($students->take(3) as $student) {
            foreach ($paidCourses->random(rand(1, 2)) as $course) {
                Payment::create([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'reference_number' => 'REF_' . uniqid(),
                    'amount' => $course->price,
                    'status' => 'completed',
                    'payment_method' => 'stripe',
                    'payment_data' => ['stripe_payment_intent_id' => 'pi_test_' . uniqid()],
                    'paid_at' => now()->subDays(rand(1, 60)),
                    'created_at' => now()->subDays(rand(1, 60)),
                ]);
            }
        }
    }
    
    /**
     * Create assignments
     */
    private function createAssignments(): void
    {
        $this->command->info('📝 Creating assignments...');
        
        $tutors = User::where('role', 'tutor')->get();
        $topics = Topic::all();
        
        foreach ($tutors as $tutor) {
            $students = $tutor->assignedStudents;
            
            foreach ($students as $student) {
                // Create 2-3 assignments per student
                $assignmentCount = rand(2, 3);
                $usedTopics = [];
                
                for ($i = 0; $i < $assignmentCount; $i++) {
                    // Get a topic that hasn't been used for this tutor-student pair
                    $availableTopics = $topics->reject(function($topic) use ($usedTopics) {
                        return in_array($topic->id, $usedTopics);
                    });
                    
                    if ($availableTopics->isEmpty()) {
                        break; // No more unique topics available
                    }
                    
                    $topic = $availableTopics->random();
                    $usedTopics[] = $topic->id;
                    
                    Assignment::firstOrCreate([
                        'tutor_id' => $tutor->id,
                        'student_id' => $student->id,
                        'topic_id' => $topic->id,
                    ], [
                        'course_id' => $topic->course_id,
                        'title' => "Assignment " . ($i + 1) . " for " . $student->name,
                        'description' => "Complete practice questions on the assigned topic.",
                        'deadline' => now()->addDays(rand(1, 14)),
                        'duration_minutes' => rand(30, 120),
                        'status' => ['active', 'completed', 'overdue'][rand(0, 2)],
                        'created_at' => now()->subDays(rand(1, 7)),
                    ]);
                }
            }
        }
    }
    
    /**
     * Create comprehensive notifications
     */
    private function createNotifications(): void
    {
        $this->command->info('🔔 Creating comprehensive notifications...');
        
        $notificationService = app(NotificationService::class);
        $users = User::where('role', '!=', 'admin')->get();
        
        foreach ($users as $user) {
            // Create various types of notifications
            
            // Milestone notifications
            if ($user->correct_answers_count >= 100) {
                $notificationService->createCorrectAnswersMilestoneNotification(
                    $user,
                    $user->correct_answers_count
                );
            }
            
            // Streak notifications
            if ($user->study_streak_days >= 7) {
                $notificationService->createStudyStreakNotification(
                    $user,
                    $user->study_streak_days
                );
            }
            
            // Level up notifications
            $currentLevel = $user->getCurrentLevel();
            if ($currentLevel > 1) {
                $notificationService->createLevelUpNotification(
                    $user,
                    $currentLevel,
                    $user->xp
                );
            }
            
            // Topic mastery notifications
            $masteredTopics = UserTopicProgress::where('user_id', $user->id)
                ->where('percentage_grade', '>=', 80)
                ->with('topic')
                ->get();
                
            foreach ($masteredTopics->take(2) as $progress) {
                $notificationService->createTopicMasteryNotification(
                    $user,
                    $progress->topic->name
                );
            }
            
            // Review mistakes notifications
            $mistakeCount = Mistake::where('user_id', $user->id)->count();
            if ($mistakeCount > 5) {
                $notificationService->createReviewMistakesNotification(
                    $user,
                    $mistakeCount
                );
            }
            
            // Assignment notifications for students
            if ($user->role === 'student') {
                $assignments = Assignment::where('student_id', $user->id)
                    ->where('status', 'active')
                    ->get();
                    
                foreach ($assignments->take(2) as $assignment) {
                    $notificationService->createNotification(
                        $user->id,
                        'assignment',
                        'New Assignment: ' . $assignment->title,
                        'You have a new assignment due on ' . $assignment->deadline->format('M j, Y'),
                        ['assignment_id' => $assignment->id]
                    );
                }
            }
            
            // Random info notifications
            $infoMessages = [
                'Welcome to the platform! Start your learning journey today.',
                'Don\'t forget to review your mistakes to improve your performance.',
                'Try the new quiz features available in your dashboard.',
                'Check out the latest course updates in your enrolled courses.',
            ];
            
            $randomCount = rand(1, 2);
            $randomIndexes = (array) array_rand($infoMessages, $randomCount);
            
            foreach ($randomIndexes as $index) {
                $notificationService->createNotification(
                    $user->id,
                    'info',
                    'Platform Update',
                    $infoMessages[$index],
                    ['type' => 'general_info']
                );
            }
        }
    }
}