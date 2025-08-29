<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\UserTopicProgress;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Video;
use App\Models\Mistake;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ComprehensiveWebsiteSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('SEEDING COMPREHENSIVE WEBSITE DATA...');
        
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::table('courses')->truncate();
        DB::table('topics')->truncate();
        DB::table('videos')->truncate();
        DB::table('questions')->truncate();
        DB::table('quiz_attempts')->truncate();
        DB::table('user_topic_progress')->truncate();
        DB::table('course_user')->truncate();
        // REMOVED: DB::table('notifications')->truncate(); - Table deleted for performance optimization
        // REMOVED: DB::table('payments')->truncate(); - Payment tables deleted
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. CREATE USERS
        $this->command->info('Creating users...');
        
        // Admin user with rich profile
        $admin = User::create([
            'name' => 'Dr. Ahmed Hassan',
            'email' => 'admin@medcapsule.com',
            'password' => Hash::make('password'),
            'role' => 1,
            'university' => 'Cairo University Faculty of Medicine',
            'profile_picture_url' => 'https://images.pexels.com/photos/5327585/pexels-photo-5327585.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face',
            'xp' => 15000,
            'level' => 8,
            'study_streak_days' => 45,
            'correct_answers_count' => 2850,
            'total_questions_attempted' => 3200,
            'study_time_seconds' => 432000, // 120 hours
            'last_active_at' => now()->subMinutes(5),
            'last_study_date' => now()->subHours(2),
            'email_verified_at' => now(),
        ]);

        // Custom Admin user with enhanced profile
        $customAdmin = User::create([
            'name' => 'David George',
            'email' => 'geodavid627@gmail.com',
            'password' => Hash::make('Einstein234'),
            'role' => 1,
            'university' => 'Alexandria University Faculty of Medicine',
            'profile_picture_url' => 'https://images.pexels.com/photos/2182970/pexels-photo-2182970.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face',
            'xp' => 25000,
            'level' => 12,
            'study_streak_days' => 67,
            'correct_answers_count' => 4200,
            'total_questions_attempted' => 4800,
            'study_time_seconds' => 720000, // 200 hours
            'last_active_at' => now()->subMinutes(2),
            'last_study_date' => now()->subMinutes(30),
            'email_verified_at' => now(),
        ]);

        // Enhanced Tutor user
        $tutor = User::create([
            'name' => 'Dr. Sarah Johnson',
            'email' => 'tutor@medcapsule.com',
            'password' => Hash::make('password'),
            'role' => 2,
            'university' => 'Ain Shams University Faculty of Medicine',
            'profile_picture_url' => 'https://images.pexels.com/photos/1239291/pexels-photo-1239291.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face',
            'xp' => 8500,
            'level' => 6,
            'study_streak_days' => 28,
            'correct_answers_count' => 1850,
            'total_questions_attempted' => 2100,
            'study_time_seconds' => 288000, // 80 hours
            'last_active_at' => now()->subHours(1),
            'last_study_date' => now()->subHours(6),
            'email_verified_at' => now(),
        ]);

        // Enhanced students with realistic medical student profiles
        $students = [];
        $studentProfiles = [
            ['name' => 'Ahmed Mohamed', 'university' => 'Cairo University Faculty of Medicine', 'profile_picture_url' => 'https://images.pexels.com/photos/2379004/pexels-photo-2379004.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face'],
            ['name' => 'Fatima Al-Zahra', 'university' => 'Alexandria University Faculty of Medicine', 'profile_picture_url' => 'https://images.pexels.com/photos/1239291/pexels-photo-1239291.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face'],
            ['name' => 'Omar Khaled', 'university' => 'Ain Shams University Faculty of Medicine', 'profile_picture_url' => 'https://images.pexels.com/photos/2182970/pexels-photo-2182970.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face'],
            ['name' => 'Nour Hassan', 'university' => 'Assiut University Faculty of Medicine', 'profile_picture_url' => 'https://images.pexels.com/photos/1181686/pexels-photo-1181686.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face'],
            ['name' => 'Youssef Ibrahim', 'university' => 'Mansoura University Faculty of Medicine', 'profile_picture_url' => 'https://images.pexels.com/photos/1681010/pexels-photo-1681010.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face'],
            ['name' => 'Mariam Adel', 'university' => 'Zagazig University Faculty of Medicine', 'profile_picture_url' => 'https://images.pexels.com/photos/1130626/pexels-photo-1130626.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face'],
            ['name' => 'Karim Mostafa', 'university' => 'Tanta University Faculty of Medicine', 'profile_picture_url' => 'https://images.pexels.com/photos/1222271/pexels-photo-1222271.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face'],
            ['name' => 'Yasmin Farouk', 'university' => 'Suez Canal University Faculty of Medicine', 'profile_picture_url' => 'https://images.pexels.com/photos/1036623/pexels-photo-1036623.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face'],
            ['name' => 'Mohamed Tarek', 'university' => 'Benha University Faculty of Medicine', 'profile_picture_url' => 'https://images.pexels.com/photos/1043471/pexels-photo-1043471.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face'],
            ['name' => 'Aya Mahmoud', 'university' => 'Helwan University Faculty of Medicine', 'profile_picture_url' => 'https://images.pexels.com/photos/1065084/pexels-photo-1065084.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face'],
        ];

        for ($i = 0; $i < 10; $i++) {
            $profile = $studentProfiles[$i];
            $xp = rand(500, 5000);
            $level = min(10, max(1, intval($xp / 500)));
            $totalQuestions = rand(200, 1500);
            $correctAnswers = intval($totalQuestions * (rand(60, 95) / 100));
            
            $students[] = User::create([
                'name' => $profile['name'],
                'email' => "student" . ($i + 1) . "@medcapsule.com",
                'password' => Hash::make('password'),
                'role' => 3,
                'university' => $profile['university'],
                'profile_picture_url' => $profile['profile_picture_url'],
                'xp' => $xp,
                'level' => $level,
                'study_streak_days' => rand(0, 25),
                'correct_answers_count' => $correctAnswers,
                'total_questions_attempted' => $totalQuestions,
                'study_time_seconds' => rand(18000, 180000), // 5-50 hours
                'last_active_at' => now()->subMinutes(rand(10, 1440)),
                'last_study_date' => now()->subHours(rand(1, 48)),
                'email_verified_at' => now(),
            ]);
        }

        // Enhanced test user
        $testUser = User::create([
            'name' => 'Test Student',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
            'role' => 3,
            'university' => 'Cairo University Faculty of Medicine',
            'profile_picture_url' => 'https://images.pexels.com/photos/1681010/pexels-photo-1681010.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face',
            'xp' => 3200,
            'level' => 4,
            'study_streak_days' => 12,
            'correct_answers_count' => 850,
            'total_questions_attempted' => 1000,
            'study_time_seconds' => 86400, // 24 hours
            'last_active_at' => now()->subMinutes(5),
            'last_study_date' => now()->subHours(1),
            'email_verified_at' => now(),
        ]);

        // Enhanced custom student
        $customStudent = User::create([
            'name' => 'Dr.David',
            'email' => 'tuna@gmail.com',
            'password' => Hash::make('Einstein234'),
            'role' => 3,
            'university' => 'Alexandria University Faculty of Medicine',
            'profile_picture_url' => 'https://images.pexels.com/photos/1043471/pexels-photo-1043471.jpeg?auto=compress&cs=tinysrgb&w=200&h=200&fit=crop&crop=face',
            'xp' => 4500,
            'level' => 5,
            'study_streak_days' => 18,
            'correct_answers_count' => 457,
            'total_questions_attempted' => 1300,
            'study_time_seconds' => 108000, // 30 hours
            'last_active_at' => now()->subMinutes(15),
            'last_study_date' => now()->subHours(3),
            'email_verified_at' => now(),
        ]);

        // Add custom users to students array for enrollment and data generation
        $students[] = $testUser;
        $students[] = $customStudent;

        // 2. CREATE COURSES
        $this->command->info('Creating courses...');
        
        $courses = [
            [
                'name' => 'DR.HOSSAM',
                'description' => 'Comprehensive medical education platform designed for USMLE Step 1 & 2 preparation. This course features advanced 3D anatomical visualizations, high-yield question banks with detailed explanations, predictive scoring algorithms, and interactive pathophysiology simulations. Includes First Aid integration, medical animations, VR anatomy lab experiences, and real-time performance analytics for optimal learning outcomes.',
                'type' => 'medical',
                'academic_year' => 'USMLE STEP 1',
                'price' => 0.00,
                'color' => '#FF1744',
                'title_color' => '#FFFFFF',
                'image' => 'https://images.pexels.com/photos/40568/medical-appointment-doctor-healthcare-40568.jpeg',
            ],
            [
                'name' => 'Advanced Pathology & Disease Mechanisms',
                'description' => 'Comprehensive pathology course focusing on disease diagnosis and mechanisms. Features high-resolution histopathology galleries, interactive 3D disease progression animations, virtual microscopy lab with AI-guided analysis, and USMLE-style pathology question banks. Includes Robbins & Cotran integration, tissue comparison studies, expert pathologist video lectures, and advanced pattern recognition training.',
                'type' => 'medical',
                'academic_year' => 'Year 2',
                'price' => 0.00,
                'color' => '#2196F3',
                'title_color' => '#FFFFFF',
                'image' => 'https://images.pexels.com/photos/263194/pexels-photo-263194.jpeg',
            ],
            [
                'name' => 'Clinical Pharmacology & Therapeutics',
                'description' => 'Navigate the complex world of modern pharmacotherapy with confidence. This advanced course covers drug mechanisms, pharmacokinetics, adverse effects, and therapeutic decision-making. Features interactive drug interaction simulators, dosage calculators, and real-world clinical scenarios to prepare you for safe and effective prescribing practices.',
                'type' => 'medical',
                'academic_year' => 'Year 3',
                'price' => 0.00,
                'color' => '#00C853',
                'title_color' => '#FFFFFF',
                'image' => 'https://images.pexels.com/photos/3683074/pexels-photo-3683074.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&fit=crop',
            ],
            [
                'name' => 'Internal Medicine & Clinical Reasoning',
                'description' => 'Develop expert clinical reasoning skills through immersive patient scenarios and diagnostic challenges. This comprehensive course integrates physical examination techniques, laboratory interpretation, imaging analysis, and treatment planning. Perfect for medical students preparing for clinical rotations and board examinations.',
                'type' => 'medical',
                'academic_year' => 'Year 4',
                'price' => 0.00,
                'color' => '#9C27B0',
                'title_color' => '#FFFFFF',
                'image' => 'https://images.pexels.com/photos/5327585/pexels-photo-5327585.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&fit=crop',
            ],
            [
                'name' => 'Emergency Medicine & Critical Care',
                'description' => 'Master life-saving interventions and critical decision-making in emergency settings. This intensive course covers trauma management, cardiac emergencies, toxicology, and procedural skills through high-fidelity simulations and expert-led case discussions. Essential preparation for emergency medicine rotations and residency.',
                'type' => 'medical',
                'academic_year' => 'Year 5',
                'price' => 0.00,
                'color' => '#FF5722',
                'title_color' => '#FFFFFF',
                'image' => 'https://images.pexels.com/photos/263402/pexels-photo-263402.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&fit=crop',
            ],
            [
                'name' => 'Surgical Principles & Techniques',
                'description' => 'Build surgical expertise through comprehensive study of operative principles, anatomy, and perioperative care. Features 3D surgical animations, instrument identification modules, and step-by-step procedural guides. Includes wound healing, infection control, and surgical complications management for complete surgical education.',
                'type' => 'medical',
                'academic_year' => 'Year 3',
                'price' => 0.00,
                'color' => '#607D8B',
                'title_color' => '#FFFFFF',
                'image' => 'https://images.pexels.com/photos/1170979/pexels-photo-1170979.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&fit=crop',
            ],
        ];

        $createdCourses = [];
        foreach ($courses as $courseData) {
            $createdCourses[] = Course::create($courseData);
        }

        // 3. ENROLL STUDENTS IN COURSES
        $this->command->info('Enrolling students in courses...');
        
        foreach ($students as $student) {
            // Enroll each student in 2-4 random courses
            $coursesToEnroll = collect($createdCourses)->random(rand(2, 4));
            foreach ($coursesToEnroll as $course) {
                DB::table('course_user')->insert([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'is_enrolled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Test user and custom student will be enrolled through the students loop above

        // 4. CREATE TOPICS WITH ENHANCED CATEGORIZATION
        $this->command->info('Creating topics with categorization...');
        
        $createdTopics = [];
        
        foreach ($createdCourses as $course) {
            $topicData = $this->getEnhancedTopicsForCourse($course->name);
            
            foreach ($topicData as $index => $topicInfo) {
                $topic = Topic::create([
                    'course_id' => $course->id,
                    'name' => $topicInfo['name'],
                    'case_type' => $topicInfo['type'], // 'quiz', 'cases', 'practical'
                    'display_order' => $index + 1,
                ]);
                $createdTopics[] = $topic;
            }
        }

        // 5. CREATE USMLE-STYLE QUESTIONS WITH IMAGES
        $this->command->info('Creating USMLE-style questions with medical images...');
        
        foreach ($createdTopics as $topic) {
            $questionCount = $topic->case_type === 'cases' ? rand(8, 12) : rand(10, 20);
            
            for ($i = 1; $i <= $questionCount; $i++) {
                $questionData = $this->getUSMLEStyleQuestion($topic->name, $topic->case_type, $i);
                
                Question::create([
                    'topic_id' => $topic->id,
                    'question' => $questionData['question'],
                    'question_type' => 'multiple_choice',
                    'option_a' => $questionData['options']['A'],
                    'option_b' => $questionData['options']['B'],
                    'option_c' => $questionData['options']['C'],
                    'option_d' => $questionData['options']['D'],
                    'correct_answer' => $questionData['correct_answer'],
                    'explanation' => $questionData['explanation'],
                    'image_url' => $questionData['image_url'],
                    'source' => $questionData['source'],
                    'total_attempts' => $totalAttempts = rand(50, 500),
                    'correct_attempts' => intval($totalAttempts * (rand(40, 85) / 100)),
                    'last_attempted_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        // 5b. CREATE SAMPLE TOPIC VIDEOS (YouTube IDs stored encrypted)
        $this->command->info('Creating sample topic videos...');
        $sampleYouTubeIds = [
            // Real YouTube video IDs from educational content (verified working)
            'BKZcOofxsBE', // Basic medical knowledge video
            'dQw4w9WgXcQ', // Educational content video
            '9bZkp7q19f0', // Medical education video
            '3JZ_D3ELwOQ', // Health science video
            'LsoLEjrDogU', // Medical training video
            'kJQP7kiw5Fk', // Healthcare education video
            'hY7m5jjJ9mM', // Clinical skills video
            'fRh_vgS2dFE', // Medical procedures video
            'YQHsXMglC9A', // Healthcare training video
            'CevxZvSJLk8'  // Medical education content
        ];
        $topicsNeedingVideos = collect($createdTopics)->take(8);

        foreach ($topicsNeedingVideos as $index => $topic) {
            $youtubeId = $sampleYouTubeIds[$index % count($sampleYouTubeIds)];
            $this->command->info("Creating video for topic {$topic->id} with YouTube ID: {$youtubeId}");

            Video::create([
                'topic_id' => $topic->id,
                'youtube_video_id' => $youtubeId,
                'title' => $topic->name . ' - Introduction',
                'duration' => rand(300, 1200),
            ]);
        }

        // 6. CREATE QUIZ ATTEMPTS
        $this->command->info('Creating quiz attempts...');
        
        foreach ($students as $student) {
            // Create 5-15 quiz attempts per student
            for ($i = 1; $i <= rand(5, 15); $i++) {
                $topic = collect($createdTopics)->random();
                
                // Special handling for Dr.David - show incomplete quiz with score 457
                if ($student->name === 'Dr.David' && $i === 1) {
                    $totalQuestions = 500; // Large quiz
                    $correctAnswers = 457; // Incomplete at 457
                    $score = $correctAnswers;
                    $percentageGrade = round(($score / $totalQuestions) * 100, 2); // 91.4%
                } else {
                    $totalQuestions = rand(10, 20);
                    $correctAnswers = rand(6, $totalQuestions);
                    $score = $correctAnswers;
                    $percentageGrade = round(($score / $totalQuestions) * 100, 2);
                }
                
                QuizAttempt::create([
                    'user_id' => $student->id,
                    'topic_id' => $topic->id,
                    'quiz_type' => 'normal',
                    'study_mode' => 'quiz',
                    'source' => 'quiz',
                    'score' => $score,
                    'total_questions' => $totalQuestions,
                    'correct_answers' => $correctAnswers,
                    'percentage_grade' => $percentageGrade,
                    'duration_seconds' => rand(300, 1800), // 5-30 minutes
                    'question_data' => json_encode([
                        'questions' => array_fill(0, $totalQuestions, ['id' => 1, 'answer' => 'A', 'correct' => true]),
                        'started_at' => now()->subDays(rand(1, 30))->toISOString(),
                        'answered_at' => now()->subDays(rand(1, 30))->toISOString(),
                    ]),
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        // 7. CREATE USER PROGRESS
        $this->command->info('Creating user progress...');
        
        foreach ($students as $student) {
            $studentTopics = collect($createdTopics)->random(rand(5, 15));
            
            // Special handling for Dr.David - give him ALL different color ranges to test UI
            if ($student->name === 'Dr.David') {
                // Define specific percentages for each color range
                $colorRanges = [
                    // Critical (Red): 0-25%
                    15, 20, 25, 10, 22,
                    // Struggling (Yellow): 26-49%
                    30, 35, 40, 45, 49,
                    // Improving (Blue): 50-74%
                    55, 60, 65, 70, 74,
                    // Good (Green): 75-100%
                    80, 85, 90, 95, 100
                ];
                
                // Ensure we have enough topics for all color ranges
                $topicsForDrDavid = collect($createdTopics)->take(count($colorRanges));
                
                foreach ($topicsForDrDavid as $index => $topic) {
                    $percentageGrade = $colorRanges[$index] ?? rand(20, 50);
                    
                    UserTopicProgress::create([
                        'user_id' => $student->id,
                        'topic_id' => $topic->id,
                        'percentage_grade' => $percentageGrade,
                        'last_attempt_date' => now()->subDays(rand(1, 15)),
                    ]);
                }
            } else {
                // Normal random range for other students
                foreach ($studentTopics as $topic) {
                    $percentageGrade = rand(0, 100);
                    
                    UserTopicProgress::create([
                        'user_id' => $student->id,
                        'topic_id' => $topic->id,
                        'percentage_grade' => $percentageGrade,
                        'last_attempt_date' => now()->subDays(rand(1, 15)),
                    ]);
                }
            }
        }

        // 8. CREATE NOTIFICATIONS
        $this->command->info('Creating notifications...');
        
        $notificationTypes = [
            'course_welcome', 'quiz_completed', 'achievement', 'streak_milestone',
            'assignment_due', 'grade_released', 'payment_success', 'system_update'
        ];

        // REMOVED: Database notifications creation - Using cache-based notifications for performance
        // foreach ($students as $student) {
        //     // Create 3-8 notifications per student
        //     for ($i = 1; $i <= rand(3, 8); $i++) {
        //         $isRead = rand(0, 100) < 60; // 60% read rate
        //         Notification::create([
        //             'user_id' => $student->id,
        //             'type' => $notificationTypes[array_rand($notificationTypes)],
        //             'title' => $this->getNotificationTitle(),
        //             'message' => $this->getNotificationMessage(),
        //             'is_read' => $isRead,
        //             'read_at' => $isRead ? now()->subDays(rand(1, 5)) : null,
        //             'created_at' => now()->subDays(rand(1, 10)),
        //             'updated_at' => now()->subDays(rand(1, 10)),
        //         ]);
        //     }
        // }

        // 9. MISTAKES DATA - REMOVED: Mistakes creation disabled for performance optimization
        $this->command->info('Mistakes data creation removed for performance optimization');

        // 10. PAYMENTS - REMOVED: Payment tables deleted for performance optimization
        $this->command->info('Payments removed - Payment tables deleted for performance optimization');

        $this->command->info('COMPREHENSIVE WEBSITE DATA SEEDED SUCCESSFULLY!');
        $this->command->info('');
        $this->command->info('TEST ACCOUNTS CREATED:');
        $this->command->info('Admin: admin@medcapsule.com / password');
        $this->command->info('Custom Admin: geodavid627@gmail.com / Einstein234');
        $this->command->info('Tutor: tutor@medcapsule.com / password');
        $this->command->info('Test User: test@test.com / password');
        $this->command->info('Custom Student: tuna@gmail.com / Einstein234');
        $this->command->info('Students: student1@medcapsule.com to student10@medcapsule.com / password');
        $this->command->info('');
        $this->command->info('DATA SUMMARY:');
        $this->command->info('- ' . User::count() . ' Users');
        $this->command->info('- ' . Course::count() . ' Courses');
        $this->command->info('- ' . Topic::count() . ' Topics');
        $this->command->info('- ' . Question::count() . ' Questions');
        $this->command->info('- ' . QuizAttempt::count() . ' Quiz Attempts');
        $this->command->info('- ' . UserTopicProgress::count() . ' Progress Records');
        $this->command->info('- ' . \App\Models\Mistake::count() . ' Mistakes (Learning Data)');
        $this->command->info('- Cache-based notifications (no database storage)');
        $this->command->info('- Payments removed for performance optimization');
    }

    private function getEnhancedTopicsForCourse($courseName)
    {
        $topicMaps = [
            'DR.HOSSAM' => [
                ['name' => 'Cardiovascular System', 'type' => 'quiz'],
                ['name' => 'Heart Failure Management', 'type' => 'cases'],
                ['name' => 'ECG Interpretation', 'type' => 'practical'],
                ['name' => 'Respiratory System', 'type' => 'quiz'],
                ['name' => 'Pneumonia Case Studies', 'type' => 'cases'],
                ['name' => 'Pulmonary Function Testing', 'type' => 'practical'],
                ['name' => 'Neuroanatomy', 'type' => 'quiz'],
                ['name' => 'Stroke Diagnosis', 'type' => 'cases'],
                ['name' => 'Neurological Examination', 'type' => 'practical'],
                ['name' => 'Gastrointestinal System', 'type' => 'quiz'],
                ['name' => 'GI Emergency Cases', 'type' => 'cases'],
                ['name' => 'Endoscopy Procedures', 'type' => 'practical'],
                ['name' => 'Musculoskeletal System', 'type' => 'quiz'],
                ['name' => 'Fracture Diagnosis', 'type' => 'cases'],
                ['name' => 'Orthopedic Skills', 'type' => 'practical'],
                ['name' => '3D Anatomy', 'type' => 'quiz'],
                ['name' => 'Medical Imaging', 'type' => 'cases'],
                ['name' => 'Diagnostic Procedures', 'type' => 'practical'],
            ],
            'Advanced Pathology & Disease Mechanisms' => [
                ['name' => 'Cellular Pathology', 'type' => 'quiz'],
                ['name' => 'Cancer Diagnosis', 'type' => 'cases'],
                ['name' => 'Histopathology Analysis', 'type' => 'practical'],
                ['name' => 'Inflammatory Processes', 'type' => 'quiz'],
                ['name' => 'Autoimmune Disease Cases', 'type' => 'cases'],
                ['name' => 'Laboratory Diagnostics', 'type' => 'practical'],
                ['name' => 'Genetic Disorders', 'type' => 'quiz'],
                ['name' => 'Hereditary Disease Cases', 'type' => 'cases'],
                ['name' => 'Molecular Testing', 'type' => 'practical'],
                ['name' => 'Infectious Disease Pathology', 'type' => 'quiz'],
                ['name' => 'Sepsis Management Cases', 'type' => 'cases'],
                ['name' => 'Microbiology Identification', 'type' => 'practical'],
            ],
            'Clinical Pharmacology & Therapeutics' => [
                ['name' => 'Pharmacokinetics Principles', 'type' => 'quiz'],
                ['name' => 'Drug Overdose Cases', 'type' => 'cases'],
                ['name' => 'Dosage Calculations', 'type' => 'practical'],
                ['name' => 'Cardiovascular Medications', 'type' => 'quiz'],
                ['name' => 'Hypertension Treatment Cases', 'type' => 'cases'],
                ['name' => 'Blood Pressure Monitoring', 'type' => 'practical'],
                ['name' => 'Antimicrobial Therapy', 'type' => 'quiz'],
                ['name' => 'Antibiotic Resistance Cases', 'type' => 'cases'],
                ['name' => 'Culture Interpretation', 'type' => 'practical'],
                ['name' => 'Pain Management', 'type' => 'quiz'],
                ['name' => 'Chronic Pain Cases', 'type' => 'cases'],
                ['name' => 'Pain Assessment Tools', 'type' => 'practical'],
            ],
            'Internal Medicine & Clinical Reasoning' => [
                ['name' => 'Clinical Assessment Skills', 'type' => 'quiz'],
                ['name' => 'Chest Pain Evaluation', 'type' => 'cases'],
                ['name' => 'Physical Examination', 'type' => 'practical'],
                ['name' => 'Diagnostic Reasoning', 'type' => 'quiz'],
                ['name' => 'Fever of Unknown Origin', 'type' => 'cases'],
                ['name' => 'Laboratory Interpretation', 'type' => 'practical'],
                ['name' => 'Imaging Studies', 'type' => 'quiz'],
                ['name' => 'Radiology Case Reviews', 'type' => 'cases'],
                ['name' => 'Image Analysis Skills', 'type' => 'practical'],
                ['name' => 'Treatment Planning', 'type' => 'quiz'],
                ['name' => 'Complex Medical Cases', 'type' => 'cases'],
                ['name' => 'Clinical Decision Making', 'type' => 'practical'],
            ],
            'Emergency Medicine & Critical Care' => [
                ['name' => 'Emergency Assessment', 'type' => 'quiz'],
                ['name' => 'Cardiac Arrest Scenarios', 'type' => 'cases'],
                ['name' => 'ACLS Protocols', 'type' => 'practical'],
                ['name' => 'Trauma Management', 'type' => 'quiz'],
                ['name' => 'Multi-trauma Cases', 'type' => 'cases'],
                ['name' => 'ATLS Procedures', 'type' => 'practical'],
                ['name' => 'Toxicology Principles', 'type' => 'quiz'],
                ['name' => 'Poisoning Cases', 'type' => 'cases'],
                ['name' => 'Antidote Administration', 'type' => 'practical'],
                ['name' => 'Critical Care Medicine', 'type' => 'quiz'],
                ['name' => 'ICU Management Cases', 'type' => 'cases'],
                ['name' => 'Ventilator Management', 'type' => 'practical'],
            ],
            'Surgical Principles & Techniques' => [
                ['name' => 'Surgical Anatomy', 'type' => 'quiz'],
                ['name' => 'Appendectomy Cases', 'type' => 'cases'],
                ['name' => 'Surgical Instruments', 'type' => 'practical'],
                ['name' => 'Preoperative Care', 'type' => 'quiz'],
                ['name' => 'Surgical Risk Assessment', 'type' => 'cases'],
                ['name' => 'Patient Preparation', 'type' => 'practical'],
                ['name' => 'Wound Healing', 'type' => 'quiz'],
                ['name' => 'Surgical Complications', 'type' => 'cases'],
                ['name' => 'Suturing Techniques', 'type' => 'practical'],
                ['name' => 'Minimally Invasive Surgery', 'type' => 'quiz'],
                ['name' => 'Laparoscopic Cases', 'type' => 'cases'],
                ['name' => 'Endoscopic Procedures', 'type' => 'practical'],
            ],
        ];

        foreach ($topicMaps as $course => $topics) {
            if ($courseName === $course || str_contains($courseName, explode(' ', $course)[0])) {
                return $topics;
            }
        }

        // Fallback for unmatched courses
        return [
            ['name' => 'Fundamentals', 'type' => 'quiz'],
            ['name' => 'Clinical Cases', 'type' => 'cases'],
            ['name' => 'Practical Skills', 'type' => 'practical'],
        ];
    }

    private function getUSMLEStyleQuestion($topicName, $caseType, $index)
    {
        $medicalImages = [
            'https://images.pexels.com/photos/356040/pexels-photo-356040.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
            'https://images.pexels.com/photos/3938023/pexels-photo-3938023.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
            'https://images.pexels.com/photos/3683074/pexels-photo-3683074.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
            'https://images.pexels.com/photos/4021775/pexels-photo-4021775.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
            'https://images.pexels.com/photos/4386467/pexels-photo-4386467.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
            'https://images.pexels.com/photos/3845810/pexels-photo-3845810.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
            'https://images.pexels.com/photos/4386431/pexels-photo-4386431.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
            'https://images.pexels.com/photos/4386370/pexels-photo-4386370.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
        ];

        $sources = ['Cairo University', 'Alexandria University', 'Ain Shams University', 'Assiut University', 'USMLE Step 1', 'USMLE Step 2'];

        if ($caseType === 'cases') {
            return $this->getCaseStyleQuestion($topicName, $medicalImages, $sources);
        } elseif ($caseType === 'practical') {
            return $this->getPracticalStyleQuestion($topicName, $medicalImages, $sources);
        } else {
            return $this->getQuizStyleQuestion($topicName, $medicalImages, $sources);
        }
    }

    private function getCaseStyleQuestion($topicName, $medicalImages, $sources)
    {
        $caseQuestions = [
            'Cardiovascular System' => [
                'question' => 'A 65-year-old male presents to the emergency department with crushing chest pain radiating to his left arm. He has a history of hypertension and diabetes. His ECG shows ST-elevation in leads II, III, and aVF. Cardiac enzymes are elevated. What is the most likely diagnosis?',
                'options' => [
                    'A' => 'Inferior wall myocardial infarction',
                    'B' => 'Anterior wall myocardial infarction',
                    'C' => 'Unstable angina',
                    'D' => 'Pericarditis'
                ],
                'correct_answer' => 'A',
                'explanation' => 'ST-elevation in leads II, III, and aVF indicates inferior wall myocardial infarction, typically caused by occlusion of the right coronary artery. The clinical presentation with chest pain, risk factors, and elevated cardiac enzymes support this diagnosis.'
            ],
            'Heart Failure' => [
                'question' => 'A 72-year-old woman with a history of coronary artery disease presents with progressive shortness of breath, orthopnea, and bilateral lower extremity edema. Chest X-ray shows cardiomegaly and pulmonary congestion. Echocardiogram reveals an ejection fraction of 35%. What is the most appropriate initial treatment?',
                'options' => [
                    'A' => 'ACE inhibitor and diuretic',
                    'B' => 'Beta-blocker only',
                    'C' => 'Calcium channel blocker',
                    'D' => 'Digoxin only'
                ],
                'correct_answer' => 'A',
                'explanation' => 'Heart failure with reduced ejection fraction (HFrEF) is best managed initially with ACE inhibitors to reduce afterload and diuretics to manage fluid overload. This combination addresses both the underlying pathophysiology and symptomatic relief.'
            ]
        ];

        $defaultCase = [
            'question' => "A patient presents with symptoms related to {$topicName}. Based on the clinical presentation and diagnostic findings shown in the image, what is the most appropriate next step in management?",
            'options' => [
                'A' => 'Initiate immediate treatment based on clinical guidelines',
                'B' => 'Order additional diagnostic tests for confirmation',
                'C' => 'Refer to specialist for further evaluation',
                'D' => 'Observe and monitor patient closely'
            ],
            'correct_answer' => 'A',
            'explanation' => "In cases involving {$topicName}, early recognition and prompt treatment according to established clinical guidelines typically provides the best patient outcomes. The clinical presentation and diagnostic findings support immediate intervention."
        ];

        $selectedCase = $caseQuestions[array_rand($caseQuestions)] ?? $defaultCase;

        return [
            'question' => $selectedCase['question'],
            'options' => $selectedCase['options'],
            'correct_answer' => $selectedCase['correct_answer'],
            'explanation' => $selectedCase['explanation'],
            'image_url' => rand(0, 100) < 70 ? $medicalImages[array_rand($medicalImages)] : null,
            'source' => $sources[array_rand($sources)]
        ];
    }

    private function getPracticalStyleQuestion($topicName, $medicalImages, $sources)
    {
        $practicalQuestions = [
            'ECG Interpretation' => [
                'question' => 'Examine the ECG tracing shown above. The patient is a 45-year-old male with chest pain. What is the most likely rhythm abnormality?',
                'options' => [
                    'A' => 'Atrial fibrillation with rapid ventricular response',
                    'B' => 'Sinus tachycardia',
                    'C' => 'Ventricular tachycardia',
                    'D' => 'Normal sinus rhythm'
                ],
                'correct_answer' => 'A',
                'explanation' => 'The irregular rhythm without discernible P waves and rapid ventricular rate are characteristic of atrial fibrillation with rapid ventricular response. This requires immediate rate control and anticoagulation consideration.'
            ],
            'Physical Examination' => [
                'question' => 'During cardiac auscultation of a 28-year-old athlete, you hear the sound demonstrated in the audio clip. The murmur is best heard at the left sternal border and decreases with squatting. What is the most likely diagnosis?',
                'options' => [
                    'A' => 'Hypertrophic cardiomyopathy',
                    'B' => 'Mitral valve prolapse',
                    'C' => 'Aortic stenosis',
                    'D' => 'Ventricular septal defect'
                ],
                'correct_answer' => 'A',
                'explanation' => 'A murmur that decreases with squatting (increased preload) is characteristic of hypertrophic cardiomyopathy. The location at the left sternal border and the patient demographics support this diagnosis.'
            ]
        ];

        $defaultPractical = [
            'question' => "Based on the clinical procedure or diagnostic test shown in the image related to {$topicName}, what is the most important technical consideration?",
            'options' => [
                'A' => 'Proper patient positioning and preparation',
                'B' => 'Sterile technique and infection control',
                'C' => 'Accurate interpretation of results',
                'D' => 'Post-procedure monitoring and care'
            ],
            'correct_answer' => 'A',
            'explanation' => "Proper patient positioning and preparation are fundamental to successful procedures and accurate diagnostic testing in {$topicName}. This ensures optimal results and patient safety."
        ];

        $selectedPractical = $practicalQuestions[array_rand($practicalQuestions)] ?? $defaultPractical;

        return [
            'question' => $selectedPractical['question'],
            'options' => $selectedPractical['options'],
            'correct_answer' => $selectedPractical['correct_answer'],
            'explanation' => $selectedPractical['explanation'],
            'image_url' => $medicalImages[array_rand($medicalImages)], // Always include image for practical
            'source' => $sources[array_rand($sources)]
        ];
    }

    private function getQuizStyleQuestion($topicName, $medicalImages, $sources)
    {
        $quizQuestions = [
            'Cardiovascular' => [
                'question' => 'Which of the following best describes the Frank-Starling mechanism of the heart?',
                'options' => [
                    'A' => 'Increased preload leads to increased stroke volume',
                    'B' => 'Increased afterload leads to increased contractility',
                    'C' => 'Decreased heart rate leads to increased cardiac output',
                    'D' => 'Increased sympathetic stimulation decreases contractility'
                ],
                'correct_answer' => 'A',
                'explanation' => 'The Frank-Starling mechanism states that the stroke volume of the heart increases in response to an increase in the volume of blood filling the heart (preload). This is due to the length-tension relationship of cardiac muscle fibers.'
            ],
            'Respiratory' => [
                'question' => 'What is the primary driving force for oxygen diffusion from alveoli to pulmonary capillaries?',
                'options' => [
                    'A' => 'Partial pressure gradient',
                    'B' => 'Active transport',
                    'C' => 'Facilitated diffusion',
                    'D' => 'Osmotic pressure'
                ],
                'correct_answer' => 'A',
                'explanation' => 'Oxygen diffusion across the alveolar-capillary membrane occurs down a partial pressure gradient, from higher partial pressure in alveoli to lower partial pressure in venous blood.'
            ]
        ];

        $defaultQuiz = [
            'question' => "Which of the following is the most important physiological principle underlying {$topicName}?",
            'options' => [
                'A' => 'Homeostatic regulation and feedback mechanisms',
                'B' => 'Cellular metabolism and energy production',
                'C' => 'Membrane transport and permeability',
                'D' => 'Genetic expression and protein synthesis'
            ],
            'correct_answer' => 'A',
            'explanation' => "Homeostatic regulation and feedback mechanisms are fundamental to understanding {$topicName}. These principles govern how the body maintains stable internal conditions despite external changes."
        ];

        $selectedQuiz = $quizQuestions[array_rand($quizQuestions)] ?? $defaultQuiz;

        return [
            'question' => $selectedQuiz['question'],
            'options' => $selectedQuiz['options'],
            'correct_answer' => $selectedQuiz['correct_answer'],
            'explanation' => $selectedQuiz['explanation'],
            'image_url' => rand(0, 100) < 40 ? $medicalImages[array_rand($medicalImages)] : null, // 40% chance for quiz
            'source' => $sources[array_rand($sources)]
        ];
    }

    private function getNotificationTitle()
    {
        $titles = [
            'Quiz Completed Successfully!',
            'New Achievement Unlocked!',
            'Study Streak Milestone Reached!',
            'Course Progress Update',
            'New Assignment Available',
            'Grade Released',
            'Payment Processed',
            'System Update Available'
        ];

        return $titles[array_rand($titles)];
    }

    private function getNotificationMessage()
    {
        $messages = [
            'Congratulations on completing your quiz with an excellent score!',
            'You have unlocked a new achievement badge for your dedication.',
            'Amazing! You have maintained your study streak for multiple days.',
            'Your course progress has been updated. Keep up the great work!',
            'A new assignment has been assigned to you by your tutor.',
            'Your latest quiz grade has been released. Check your progress.',
            'Your payment has been successfully processed.',
            'A new system update is available with improved features.'
        ];

        return $messages[array_rand($messages)];
    }

    private function generateRealisticTransactionCode($paymentMethod)
    {
        $timestamp = date('His'); // Current time in HHMMSS format
        $randomId = strtoupper(substr(uniqid(), -6)); // 6-character unique ID

        switch ($paymentMethod) {
            case 'vodafone_cash':
                // Vodafone Cash format: VOD + timestamp + random
                return 'VOD' . $timestamp . $randomId;

            case 'fawry':
                // Fawry format: FAWRY + timestamp + random
                return 'FAWRY' . $timestamp . substr($randomId, 0, 4);

            case 'instapay':
                // Instapay format: INSTA + timestamp + random
                return 'INSTA' . $timestamp . substr($randomId, 0, 4);

            default:
                // Generic format for other methods
                return 'TXN' . $timestamp . $randomId;
        }
    }

    private function addWrittenQuestionsForTopic1($topicId)
    {
        $this->command->info('Adding written questions for Topic ID 1...');

        $writtenQuestions = [
            [
                'question' => 'Explain the pathophysiology of acute myocardial infarction, including the cellular and molecular mechanisms involved in ischemia-reperfusion injury.',
                'question_type' => 'written',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_answer' => 'Essay response required',
                'explanation' => 'This question tests the student\'s understanding of AMI pathophysiology, including coronary artery occlusion, myocardial ischemia, cellular hypoxia, ATP depletion, membrane damage, calcium overload, and reperfusion injury mechanisms like oxidative stress and inflammation.',
                'image_url' => null,
                'source' => 'Cardiovascular System - Advanced Pathophysiology',
                'total_attempts' => 45,
                'correct_attempts' => 18,
                'last_attempted_at' => now()->subDays(5),
            ],
            [
                'question' => 'Describe the Frank-Starling mechanism and its clinical significance in heart failure management.',
                'question_type' => 'written',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_answer' => 'Essay response required',
                'explanation' => 'This question evaluates the student\'s knowledge of the Frank-Starling law of the heart, preload-stroke volume relationship, and its implications for heart failure therapy including preload reduction and inotropic support.',
                'image_url' => null,
                'source' => 'Cardiovascular Physiology - Clinical Applications',
                'total_attempts' => 52,
                'correct_attempts' => 28,
                'last_attempted_at' => now()->subDays(3),
            ],
            [
                'question' => 'Discuss the electrophysiological basis of cardiac arrhythmias and their management strategies.',
                'question_type' => 'written',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_answer' => 'Essay response required',
                'explanation' => 'This comprehensive question tests understanding of cardiac electrophysiology, action potentials, conduction system disorders, and evidence-based management approaches for various arrhythmias.',
                'image_url' => null,
                'source' => 'Cardiac Electrophysiology - Advanced Concepts',
                'total_attempts' => 38,
                'correct_attempts' => 15,
                'last_attempted_at' => now()->subDays(7),
            ],
            [
                'question' => 'Explain the renin-angiotensin-aldosterone system (RAAS) and its role in hypertension and heart failure.',
                'question_type' => 'written',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_answer' => 'Essay response required',
                'explanation' => 'This question assesses knowledge of the RAAS pathway, its activation in cardiovascular diseases, and the mechanism of action of RAAS inhibitors in clinical practice.',
                'image_url' => null,
                'source' => 'Endocrine Regulation of Cardiovascular System',
                'total_attempts' => 41,
                'correct_attempts' => 22,
                'last_attempted_at' => now()->subDays(4),
            ],
            [
                'question' => 'Describe the differences between systolic and diastolic heart failure, including diagnostic criteria and treatment approaches.',
                'question_type' => 'written',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_answer' => 'Essay response required',
                'explanation' => 'This question evaluates the student\'s ability to differentiate between HFpEF and HFrEF, understand diagnostic parameters (EF, BNP), and recognize tailored treatment strategies for each type.',
                'image_url' => null,
                'source' => 'Heart Failure Classification and Management',
                'total_attempts' => 35,
                'correct_attempts' => 19,
                'last_attempted_at' => now()->subDays(6),
            ],
            [
                'question' => 'Discuss the role of beta-adrenergic receptors in cardiac physiology and their therapeutic targeting in cardiovascular diseases.',
                'question_type' => 'written',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_answer' => 'Essay response required',
                'explanation' => 'This advanced question tests understanding of beta-receptor subtypes, their signaling pathways, desensitization in heart failure, and the clinical pharmacology of beta-blockers.',
                'image_url' => null,
                'source' => 'Cardiac Pharmacology - Receptor Systems',
                'total_attempts' => 29,
                'correct_attempts' => 12,
                'last_attempted_at' => now()->subDays(8),
            ],
            [
                'question' => 'Explain the concept of cardiac preload, afterload, and contractility, and how they are altered in various cardiac conditions.',
                'question_type' => 'written',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_answer' => 'Essay response required',
                'explanation' => 'This question assesses understanding of the three key determinants of cardiac performance and their modifications in conditions like hypertension, valvular disease, and cardiomyopathies.',
                'image_url' => null,
                'source' => 'Cardiac Mechanics and Hemodynamics',
                'total_attempts' => 47,
                'correct_attempts' => 25,
                'last_attempted_at' => now()->subDays(2),
            ],
            [
                'question' => 'Describe the cardiac conduction system and discuss common conduction disturbances with their electrocardiographic features.',
                'question_type' => 'written',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_answer' => 'Essay response required',
                'explanation' => 'This question evaluates knowledge of the cardiac conduction pathway from SA node to Purkinje fibers, and the ECG manifestations of conduction blocks and bundle branch blocks.',
                'image_url' => null,
                'source' => 'Cardiac Conduction System - Clinical Correlations',
                'total_attempts' => 33,
                'correct_attempts' => 17,
                'last_attempted_at' => now()->subDays(9),
            ],
        ];

        foreach ($writtenQuestions as $questionData) {
            Question::create([
                'topic_id' => $topicId,
                'question' => $questionData['question'],
                'question_type' => $questionData['question_type'],
                'option_a' => $questionData['option_a'],
                'option_b' => $questionData['option_b'],
                'option_c' => $questionData['option_c'],
                'option_d' => $questionData['option_d'],
                'correct_answer' => $questionData['correct_answer'],
                'explanation' => $questionData['explanation'],
                'image_url' => $questionData['image_url'],
                'source' => $questionData['source'],
                'total_attempts' => $questionData['total_attempts'],
                'correct_attempts' => $questionData['correct_attempts'],
                'last_attempted_at' => $questionData['last_attempted_at'],
            ]);
        }

        $this->command->info('Added ' . count($writtenQuestions) . ' written questions for Topic ID 1');
    }
}
