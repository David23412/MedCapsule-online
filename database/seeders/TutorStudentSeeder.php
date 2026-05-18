<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TutorStudentSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create realistic tutors
        $tutors = [
            [
                'name' => 'Dr. Sarah Johnson',
                'email' => 'tutor@medcapsule.com',
                'university' => 'Cairo University Medical School',
                'profile_picture_url' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=150&h=150&fit=crop&crop=face'
            ],
            [
                'name' => 'Prof. Ahmed Hassan',
                'email' => 'ahmed.tutor@medcapsule.com',
                'university' => 'Ain Shams University',
                'profile_picture_url' => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=150&h=150&fit=crop&crop=face'
            ]
        ];

        $createdTutors = [];
        foreach ($tutors as $tutorData) {
            $tutor = User::updateOrCreate(
                ['email' => $tutorData['email']],
                [
                    'name' => $tutorData['name'],
                    'password' => bcrypt('password'),
                    'role' => User::ROLE_TUTOR,
                    'university' => $tutorData['university'],
                    'email_verified_at' => now(),
                    'profile_picture_url' => $tutorData['profile_picture_url'],
                    'xp' => rand(2000, 5000),
                    'study_streak_days' => rand(30, 100),
                ]
            );
            $createdTutors[] = $tutor;
        }

        // Create realistic students with diverse data
        $studentData = [
            [
                'name' => 'Ahmed Mohamed',
                'email' => 'ahmed.student@medcapsule.com',
                'university' => 'Cairo University',
                'profile_picture_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face',
                'performance' => 'excellent'
            ],
            [
                'name' => 'Fatima Al-Zahra',
                'email' => 'fatima.student@medcapsule.com',
                'university' => 'Ain Shams University',
                'profile_picture_url' => 'https://images.unsplash.com/photo-1494790108755-2616b612b786?w=150&h=150&fit=crop&crop=face',
                'performance' => 'good'
            ],
            [
                'name' => 'Omar Khaled',
                'email' => 'omar.student@medcapsule.com',
                'university' => 'Alexandria University',
                'profile_picture_url' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face',
                'performance' => 'average'
            ],
            [
                'name' => 'Nour Hassan',
                'email' => 'nour.student@medcapsule.com',
                'university' => 'Cairo University',
                'profile_picture_url' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face',
                'performance' => 'struggling'
            ],
            [
                'name' => 'Youssef Ibrahim',
                'email' => 'youssef.student@medcapsule.com',
                'university' => 'Mansoura University',
                'profile_picture_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop&crop=face',
                'performance' => 'excellent'
            ],
            [
                'name' => 'Maryam Ali',
                'email' => 'maryam.student@medcapsule.com',
                'university' => 'Assiut University',
                'profile_picture_url' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop&crop=face',
                'performance' => 'good'
            ]
        ];

        $createdStudents = [];
        foreach ($studentData as $data) {
            // Set performance-based stats
            switch ($data['performance']) {
                case 'excellent':
                    $xp = rand(1500, 3000);
                    $streak = rand(15, 45);
                    $correct = rand(80, 150);
                    $total = rand(100, 180);
                    $lastActive = now()->subHours(rand(1, 12));
                    break;
                case 'good':
                    $xp = rand(800, 1500);
                    $streak = rand(7, 20);
                    $correct = rand(50, 100);
                    $total = rand(70, 140);
                    $lastActive = now()->subDays(rand(0, 2));
                    break;
                case 'average':
                    $xp = rand(400, 800);
                    $streak = rand(3, 10);
                    $correct = rand(30, 70);
                    $total = rand(50, 100);
                    $lastActive = now()->subDays(rand(1, 4));
                    break;
                case 'struggling':
                    $xp = rand(100, 400);
                    $streak = rand(0, 5);
                    $correct = rand(10, 40);
                    $total = rand(25, 70);
                    $lastActive = now()->subDays(rand(3, 10));
                    break;
            }

            $student = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt('password'),
                    'role' => User::ROLE_STUDENT,
                    'university' => $data['university'],
                    'email_verified_at' => now(),
                    'profile_picture_url' => $data['profile_picture_url'],
                    'xp' => $xp,
                    'study_streak_days' => $streak,
                    'correct_answers_count' => $correct,
                    'total_questions_attempted' => $total,
                    'last_active_at' => $lastActive,
                ]
            );
            $createdStudents[] = $student;
        }

        // Assign students to tutors
        $mainTutor = $createdTutors[0]; // Dr. Sarah Johnson
        $secondTutor = $createdTutors[1]; // Prof. Ahmed Hassan
        
        // Assign first 4 students to main tutor
        for ($i = 0; $i < 4; $i++) {
            if (isset($createdStudents[$i])) {
                DB::table('tutor_student_relationships')->updateOrInsert([
                    'tutor_id' => $mainTutor->id,
                    'student_id' => $createdStudents[$i]->id,
                ], [
                    'assigned_at' => now()->subDays(rand(1, 30)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Assign remaining students to second tutor
        for ($i = 4; $i < count($createdStudents); $i++) {
            DB::table('tutor_student_relationships')->updateOrInsert([
                'tutor_id' => $secondTutor->id,
                'student_id' => $createdStudents[$i]->id,
            ], [
                'assigned_at' => now()->subDays(rand(1, 30)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create realistic quiz attempts for students
        $this->createQuizAttempts($createdStudents);

        $this->command->info("Created " . count($createdTutors) . " tutors and " . count($createdStudents) . " students");
        $this->command->info("Assigned 4 students to {$mainTutor->name}");
        $this->command->info("Assigned " . (count($createdStudents) - 4) . " students to {$secondTutor->name}");
    }

    /**
     * Create realistic quiz attempts for students
     */
    private function createQuizAttempts($students)
    {
        // Get existing topics (assuming they exist)
        $topics = \App\Models\Topic::take(5)->get();
        
        if ($topics->isEmpty()) {
            $this->command->warn("No topics found. Skipping quiz attempts creation.");
            return;
        }

        foreach ($students as $student) {
            $attemptCount = rand(5, 25); // Each student has 5-25 quiz attempts
            
            for ($i = 0; $i < $attemptCount; $i++) {
                $topic = $topics->random();
                
                // Generate realistic scores based on student performance
                $baseScore = $this->getBaseScoreForStudent($student);
                $score = max(0, min(100, $baseScore + rand(-15, 15))); // Add some variation
                
                $totalQuestions = rand(10, 20);
                $correctAnswers = round(($score / 100) * $totalQuestions);
                
                \App\Models\QuizAttempt::create([
                    'user_id' => $student->id,
                    'topic_id' => $topic->id,
                    'quiz_type' => 'normal',
                    'study_mode' => 'quiz',
                    'source' => ['Assuit', 'Cairo', 'Alexandria'][rand(0, 2)],
                    'score' => $score,
                    'total_questions' => $totalQuestions,
                    'percentage_grade' => $score,
                    'duration_seconds' => rand(300, 1800), // 5-30 minutes
                    'question_data' => $this->generateQuestionData($totalQuestions, $correctAnswers),
                    'created_at' => now()->subDays(rand(0, 60))->subHours(rand(0, 23)),
                ]);
            }
        }
        
        $this->command->info("Created realistic quiz attempts for all students");
    }

    /**
     * Get base score for student based on their performance level
     */
    private function getBaseScoreForStudent($student)
    {
        $xp = $student->xp;
        
        if ($xp >= 1500) return rand(80, 95); // Excellent students
        if ($xp >= 800) return rand(70, 85);  // Good students
        if ($xp >= 400) return rand(60, 75);  // Average students
        return rand(40, 65);                  // Struggling students
    }

    /**
     * Generate realistic question data for quiz attempts
     */
    private function generateQuestionData($totalQuestions, $correctAnswers)
    {
        // Get actual question IDs from the database
        $questionIds = \App\Models\Question::pluck('id')->toArray();
        
        if (empty($questionIds)) {
            // If no questions exist, create simple mock data
            $this->command->warn("No questions found in database. Creating mock question data.");
            $questionData = [];
            $correctCount = 0;
            
            for ($i = 1; $i <= $totalQuestions; $i++) {
                $isCorrect = $correctCount < $correctAnswers && (rand(0, 1) || ($i > $totalQuestions - ($correctAnswers - $correctCount)));
                if ($isCorrect) $correctCount++;
                
                $questionData[] = [
                    'question_id' => 1, // Use a default question ID
                    'user_answer' => ['A', 'B', 'C', 'D'][rand(0, 3)],
                    'is_correct' => $isCorrect,
                    'answered_at' => now()->toIso8601String(),
                ];
            }
            
            return $questionData;
        }
        
        $questionData = [];
        $correctCount = 0;
        
        for ($i = 1; $i <= $totalQuestions; $i++) {
            $isCorrect = $correctCount < $correctAnswers && (rand(0, 1) || ($i > $totalQuestions - ($correctAnswers - $correctCount)));
            if ($isCorrect) $correctCount++;
            
            $questionData[] = [
                'question_id' => $questionIds[array_rand($questionIds)], // Use actual question ID
                'user_answer' => ['A', 'B', 'C', 'D'][rand(0, 3)],
                'is_correct' => $isCorrect,
                'answered_at' => now()->toIso8601String(),
            ];
        }
        
        return $questionData;
    }
}