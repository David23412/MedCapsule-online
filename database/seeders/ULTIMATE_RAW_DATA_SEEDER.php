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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ULTIMATE_RAW_DATA_SEEDER extends Seeder
{
    /**
     * 🚀 CLEAN EFFICIENT SEEDER - NO SOFT DELETE NONSENSE
     */
    public function run(): void
    {
        $this->command->info('🚀 CLEAN EFFICIENT SEEDER - Creating real data...');
        
        DB::beginTransaction();
        
        try {
            $this->createUsers();
            $this->createCourses();
            $this->createQuestions();
            $this->createQuizHistory();
            $this->createMistakes();
            $this->createTutorRelationships();
            $this->createPayments();
            $this->createAssignments();
            $this->createProgress();
            
            DB::commit();
            $this->command->info('✅ CLEAN SEEDER completed successfully!');
            $this->displaySummary();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeder failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function createUsers(): void
    {
        $this->command->info('👥 Creating users...');
        
        // Admin
        User::updateOrCreate(['email' => 'admin@medcapsule.com'], [
            'name' => 'Admin',
            'password' => Hash::make('admin123'),
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
            'xp' => 10000,
            'study_streak_days' => 100,
            'correct_answers_count' => 1000,
            'total_questions_attempted' => 1200,
            'university' => 'MedCapsule',
            'study_time_seconds' => 360000,
        ]);
        
        // Tutors
        $tutors = [
            ['name' => 'Dr. Sarah Johnson', 'email' => 'sarah@medcapsule.com'],
            ['name' => 'Dr. Michael Chen', 'email' => 'michael@medcapsule.com'],
            ['name' => 'Dr. Emily Rodriguez', 'email' => 'emily@medcapsule.com'],
        ];
        
        foreach ($tutors as $tutor) {
            User::updateOrCreate(['email' => $tutor['email']], [
                'name' => $tutor['name'],
                'password' => Hash::make('tutor123'),
                'role' => User::ROLE_TUTOR,
                'email_verified_at' => now(),
                'xp' => rand(5000, 8000),
                'study_streak_days' => rand(30, 80),
                'correct_answers_count' => rand(400, 800),
                'total_questions_attempted' => rand(500, 900),
                'university' => 'Medical University',
                'study_time_seconds' => rand(180000, 300000),
            ]);
        }
        
        // Students
        $students = [
            ['name' => 'Alex Thompson', 'email' => 'alex@student.com', 'xp' => 8000, 'streak' => 45],
            ['name' => 'Maria Garcia', 'email' => 'maria@student.com', 'xp' => 3000, 'streak' => 20],
            ['name' => 'James Wilson', 'email' => 'james@student.com', 'xp' => 500, 'streak' => 3],
            ['name' => 'Lisa Chen', 'email' => 'lisa@student.com', 'xp' => 2000, 'streak' => 10],
            ['name' => 'David Brown', 'email' => 'david@student.com', 'xp' => 200, 'streak' => 2],
        ];
        
        foreach ($students as $student) {
            User::updateOrCreate(['email' => $student['email']], [
                'name' => $student['name'],
                'password' => Hash::make('student123'),
                'role' => User::ROLE_STUDENT,
                'email_verified_at' => now(),
                'xp' => $student['xp'],
                'study_streak_days' => $student['streak'],
                'correct_answers_count' => intval($student['xp'] * 0.1),
                'total_questions_attempted' => intval($student['xp'] * 0.12),
                'university' => 'Student University',
                'study_time_seconds' => $student['xp'] * 30,
            ]);
        }
    }
    
    private function createCourses(): void
    {
        $this->command->info('📚 Creating courses...');
        
        $courses = [
            [
                'name' => 'Human Anatomy',
                'description' => 'Basic anatomy course',
                'price' => 0,
                'topics' => [
                    ['name' => 'Cell Structure', 'locked' => false, 'order' => 1],
                    ['name' => 'Tissue Types', 'locked' => false, 'order' => 2],
                    ['name' => 'Skeletal System', 'locked' => true, 'order' => 3],
                ]
            ],
            [
                'name' => 'Physiology',
                'description' => 'Advanced physiology',
                'price' => 149.99,
                'topics' => [
                    ['name' => 'Cellular Physiology', 'locked' => false, 'order' => 1],
                    ['name' => 'Action Potentials', 'locked' => true, 'order' => 2],
                ]
            ]
        ];
        
        foreach ($courses as $courseData) {
            $course = Course::updateOrCreate(
                ['name' => $courseData['name']],
                [
                    'description' => $courseData['description'],
                    'price' => $courseData['price'],
                ]
            );
            
            foreach ($courseData['topics'] as $topicData) {
                Topic::updateOrCreate(
                    ['name' => $topicData['name'], 'course_id' => $course->id],
                    [

                        'display_order' => $topicData['order'],
                        'is_locked' => $topicData['locked'],
                    ]
                );
            }
        }
    }
    
    private function createQuestions(): void
    {
        $this->command->info('❓ Creating questions...');
        
        $sources = ['textbook', 'lecture', 'practice', 'exam'];
        $difficulties = ['easy', 'medium', 'hard'];
        $topics = Topic::all();
        
        foreach ($topics as $topic) {
            for ($i = 1; $i <= 10; $i++) {
                Question::create([
                    'topic_id' => $topic->id,
                    'question' => "Question {$i} about {$topic->name}",
                    'source' => $sources[array_rand($sources)],
                    'question_type' => 'multiple_choice',
                    'correct_answer' => 'A',
                    'option_a' => 'Correct answer',
                    'option_b' => 'Wrong answer 1',
                    'option_c' => 'Wrong answer 2',
                    'option_d' => 'Wrong answer 3',
                    'explanation' => "Explanation for question {$i}",
                ]);
            }
        }
    }
    
    private function createQuizHistory(): void
    {
        $this->command->info('📊 Creating quiz history...');
        
        $students = User::where('role', 'student')->get();
        $topics = Topic::all();
        
        foreach ($students as $student) {
            foreach ($topics->take(3) as $topic) {
                for ($i = 0; $i < rand(2, 5); $i++) {
                    $score = rand(6, 10);
                    $totalQuestions = 10;
                    $questions = Question::where('topic_id', $topic->id)->take($totalQuestions)->get();
                    
                    $questionData = [];
                    foreach ($questions as $question) {
                        $questionData[] = [
                            'question_id' => $question->id,
                            'user_answer' => ['A', 'B', 'C', 'D'][rand(0, 3)],
                            'is_correct' => rand(0, 1) === 1,
                        ];
                    }
                    
                    QuizAttempt::create([
                        'user_id' => $student->id,
                        'topic_id' => $topic->id,
                        'quiz_type' => 'normal',
                        'study_mode' => 'quiz',
                        'score' => $score,
                        'total_questions' => $totalQuestions,
                        'percentage_grade' => ($score / $totalQuestions) * 100,
                        'duration_seconds' => rand(300, 1800),
                        'question_data' => $questionData,
                        'created_at' => now()->subDays(rand(1, 30)),
                    ]);
                }
            }
        }
    }
    
    private function createMistakes(): void
    {
        $this->command->info('🔄 Creating mistakes...');
        
        $students = User::where('role', 'student')->get();
        $questions = Question::all();
        
        foreach ($students as $student) {
            $studentQuestions = $questions->random(rand(5, 15));
            
            foreach ($studentQuestions as $question) {
                $skipped = rand(1, 4) === 1; // 25% chance of being skipped
                
                Mistake::create([
                    'user_id' => $student->id,
                    'question_id' => $question->id,
                    'submitted_answer' => $skipped ? null : ['A', 'B', 'C', 'D'][rand(0, 3)],
                    'question_text' => $question->question,
                    'correct_answer' => $question->correct_answer,
                    'skipped' => $skipped,
                    'mastered' => false,
                    'correct_streak' => 0,
                    'quick_correct_count' => 0,
                    'quiz_correct_count' => 0,
                    'last_attempt_date' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }
    
    private function createTutorRelationships(): void
    {
        $this->command->info('👨‍🏫 Creating tutor relationships...');
        
        $tutors = User::where('role', 'tutor')->get();
        $students = User::where('role', 'student')->get();
        
        foreach ($tutors as $tutor) {
            $assignedStudents = $students->random(rand(2, 3));
            
            foreach ($assignedStudents as $student) {
                TutorStudentRelationship::create([
                    'tutor_id' => $tutor->id,
                    'student_id' => $student->id,
                    'assigned_at' => now()->subDays(rand(1, 60)),
                ]);
            }
        }
    }
    
    private function createPayments(): void
    {
        $this->command->info('💳 Creating payments...');
        
        $paidCourses = Course::where('price', '>', 0)->get();
        $payingStudents = User::where('role', 'student')->take(2)->get();
        
        foreach ($payingStudents as $student) {
            foreach ($paidCourses->take(1) as $course) {
                Payment::create([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'reference_number' => 'PAY_' . strtoupper(uniqid()),
                    'amount' => $course->price,
                    'status' => 'completed',
                    'payment_method' => 'stripe',
                    'payment_data' => ['transaction_id' => 'txn_' . uniqid()],
                    'paid_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }
    
    private function createAssignments(): void
    {
        $this->command->info('📝 Creating assignments...');
        
        $tutors = User::where('role', 'tutor')->get();
        $topics = Topic::all();
        
        foreach ($tutors as $tutor) {
            $students = TutorStudentRelationship::where('tutor_id', $tutor->id)->with('student')->get();
            
            foreach ($students as $relationship) {
                $topic = $topics->random();
                
                Assignment::create([
                    'tutor_id' => $tutor->id,
                    'student_id' => $relationship->student->id,
                    'course_id' => $topic->course_id,
                    'topic_id' => $topic->id,
                    'title' => "Practice: {$topic->name}",
                    'description' => "Complete practice questions for {$topic->name}",
                    'deadline' => now()->addDays(rand(1, 14)),
                    'duration_minutes' => rand(30, 120),
                    'status' => 'active',
                ]);
            }
        }
    }
    
    private function createProgress(): void
    {
        $this->command->info('📈 Creating progress...');
        
        $students = User::where('role', 'student')->get();
        $topics = Topic::all();
        
        foreach ($students as $student) {
            foreach ($topics->take(3) as $topic) {
                UserTopicProgress::create([
                    'user_id' => $student->id,
                    'topic_id' => $topic->id,
                    'percentage_grade' => rand(40, 95),
                    'last_attempt_date' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }
    
    private function displaySummary(): void
    {
        $this->command->info('📊 Data Summary:');
        $this->command->info('Users: ' . User::count());
        $this->command->info('Courses: ' . Course::count());
        $this->command->info('Topics: ' . Topic::count());
        $this->command->info('Questions: ' . Question::count());
        $this->command->info('Quiz Attempts: ' . QuizAttempt::count());
        $this->command->info('Mistakes: ' . Mistake::count());
        $this->command->info('Assignments: ' . Assignment::count());
    }
}