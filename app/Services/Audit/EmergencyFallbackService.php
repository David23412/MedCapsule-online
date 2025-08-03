<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\Log;
use Exception;

class EmergencyFallbackService
{
    private const FALLBACK_DATA_DIR = 'fallback_data';
    private const EMERGENCY_QUIZ_QUESTIONS = 5;
    private const MAX_FALLBACK_USAGE_MINUTES = 30;

    /**
     * Activate emergency mode
     */
    public static function activateEmergencyMode(string $reason = 'System failure'): bool
    {
        try {
            $emergencyData = [
                'activated_at' => now(),
                'reason' => $reason,
                'expires_at' => now()->addMinutes(self::MAX_FALLBACK_USAGE_MINUTES),
                'request_id' => uniqid('emergency_'),
                'user_agent' => request()->userAgent() ?? 'unknown',
                'ip' => request()->ip() ?? 'unknown'
            ];

            // Store in file system as cache may be down
            self::storeEmergencyData('emergency_mode', $emergencyData);

            Log::critical('Emergency mode activated', $emergencyData);

            return true;
        } catch (Exception $e) {
            Log::critical('Failed to activate emergency mode', [
                'error' => $e->getMessage(),
                'reason' => $reason
            ]);
            return false;
        }
    }

    /**
     * Check if emergency mode is active
     */
    public static function isEmergencyModeActive(): bool
    {
        try {
            $emergencyData = self::getEmergencyData('emergency_mode');
            
            if (!$emergencyData) {
                return false;
            }

            // Check if emergency mode has expired
            $expiresAt = \Carbon\Carbon::parse($emergencyData['expires_at']);
            if (now()->isAfter($expiresAt)) {
                self::deactivateEmergencyMode();
                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::error('Error checking emergency mode status', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Deactivate emergency mode
     */
    public static function deactivateEmergencyMode(): bool
    {
        try {
            $emergencyData = self::getEmergencyData('emergency_mode');
            
            if ($emergencyData) {
                $deactivationData = array_merge($emergencyData, [
                    'deactivated_at' => now(),
                    'duration_minutes' => now()->diffInMinutes($emergencyData['activated_at'])
                ]);

                self::storeEmergencyData('emergency_mode_history', $deactivationData);
                self::deleteEmergencyData('emergency_mode');

                Log::info('Emergency mode deactivated', $deactivationData);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to deactivate emergency mode', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get fallback quiz questions when database is down
     */
    public static function getFallbackQuizQuestions(int $count = null): array
    {
        $count = $count ?? self::EMERGENCY_QUIZ_QUESTIONS;
        
        $fallbackQuestions = [
            [
                'id' => 'fb_1',
                'question' => 'What is the basic functional unit of the nervous system?',
                'type' => 'multiple_choice',
                'options' => ['Neuron', 'Synapse', 'Axon', 'Dendrite'],
                'correct_answer' => 'Neuron',
                'explanation' => 'The neuron is the basic functional unit of the nervous system.'
            ],
            [
                'id' => 'fb_2',
                'question' => 'Which organ system is responsible for gas exchange?',
                'type' => 'multiple_choice',
                'options' => ['Cardiovascular', 'Respiratory', 'Digestive', 'Nervous'],
                'correct_answer' => 'Respiratory',
                'explanation' => 'The respiratory system is responsible for gas exchange between the body and environment.'
            ],
            [
                'id' => 'fb_3',
                'question' => 'What is the largest organ in the human body?',
                'type' => 'multiple_choice',
                'options' => ['Liver', 'Brain', 'Skin', 'Heart'],
                'correct_answer' => 'Skin',
                'explanation' => 'The skin is the largest organ in the human body by surface area.'
            ],
            [
                'id' => 'fb_4',
                'question' => 'Which blood cells are responsible for carrying oxygen?',
                'type' => 'multiple_choice',
                'options' => ['White blood cells', 'Red blood cells', 'Platelets', 'Plasma'],
                'correct_answer' => 'Red blood cells',
                'explanation' => 'Red blood cells contain hemoglobin which carries oxygen throughout the body.'
            ],
            [
                'id' => 'fb_5',
                'question' => 'What is the normal resting heart rate range for adults?',
                'type' => 'multiple_choice',
                'options' => ['40-60 bpm', '60-100 bpm', '100-120 bpm', '120-140 bpm'],
                'correct_answer' => '60-100 bpm',
                'explanation' => 'The normal resting heart rate for adults is typically 60-100 beats per minute.'
            ]
        ];

        return array_slice($fallbackQuestions, 0, $count);
    }

    /**
     * Process fallback quiz answers
     */
    public static function processFallbackQuiz(array $answers): array
    {
        try {
            $questions = self::getFallbackQuizQuestions();
            $score = 0;
            $results = [];

            foreach ($questions as $question) {
                $userAnswer = $answers[$question['id']] ?? '';
                $isCorrect = strtolower(trim($userAnswer)) === strtolower(trim($question['correct_answer']));
                
                if ($isCorrect) {
                    $score++;
                }

                $results[] = [
                    'question_id' => $question['id'],
                    'question' => $question['question'],
                    'user_answer' => $userAnswer,
                    'correct_answer' => $question['correct_answer'],
                    'is_correct' => $isCorrect,
                    'explanation' => $question['explanation']
                ];
            }

            $percentage = round(($score / count($questions)) * 100, 2);

            return [
                'success' => true,
                'score' => $score,
                'total' => count($questions),
                'percentage' => $percentage,
                'results' => $results,
                'message' => 'Fallback quiz completed successfully.',
                'is_fallback' => true
            ];

        } catch (Exception $e) {
            Log::error('Fallback quiz processing failed', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => 'Unable to process quiz in emergency mode.',
                'is_fallback' => true
            ];
        }
    }

    /**
     * Get fallback course list when database is down
     */
    public static function getFallbackCourses(): array
    {
        return [
            [
                'id' => 'fb_course_1',
                'name' => 'Basic Medical Sciences',
                'description' => 'Emergency fallback content for basic medical sciences.',
                'status' => 'This is fallback content. Full course access will be restored shortly.',
                'is_fallback' => true
            ]
        ];
    }

    /**
     * Get system status message for users
     */
    public static function getSystemStatusMessage(): array
    {
        if (!self::isEmergencyModeActive()) {
            return [
                'status' => 'operational',
                'message' => 'All systems operational.',
                'show_banner' => false
            ];
        }

        $emergencyData = self::getEmergencyData('emergency_mode');
        $activatedAt = \Carbon\Carbon::parse($emergencyData['activated_at']);
        $duration = $activatedAt->diffForHumans();

        return [
            'status' => 'emergency',
            'message' => "We're experiencing temporary technical difficulties. Limited functionality is available. Issue started {$duration}.",
            'show_banner' => true,
            'banner_type' => 'warning',
            'estimated_resolution' => 'We\'re working to resolve this quickly.'
        ];
    }

    /**
     * Store emergency data to file system
     */
    private static function storeEmergencyData(string $key, array $data): bool
    {
        try {
            $dir = storage_path(self::FALLBACK_DATA_DIR);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = $dir . '/' . $key . '.json';
            $jsonData = json_encode($data, JSON_PRETTY_PRINT);
            
            return file_put_contents($filename, $jsonData) !== false;
        } catch (Exception $e) {
            Log::error('Failed to store emergency data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get emergency data from file system
     */
    private static function getEmergencyData(string $key): ?array
    {
        try {
            $filename = storage_path(self::FALLBACK_DATA_DIR . '/' . $key . '.json');
            
            if (!file_exists($filename)) {
                return null;
            }

            $jsonData = file_get_contents($filename);
            return json_decode($jsonData, true);
        } catch (Exception $e) {
            Log::error('Failed to get emergency data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete emergency data
     */
    private static function deleteEmergencyData(string $key): bool
    {
        try {
            $filename = storage_path(self::FALLBACK_DATA_DIR . '/' . $key . '.json');
            
            if (file_exists($filename)) {
                return unlink($filename);
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete emergency data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Auto-activate emergency mode if critical systems are down
     */
    public static function checkAndActivateEmergencyMode(): bool
    {
        // Don't re-activate if already active
        if (self::isEmergencyModeActive()) {
            return true;
        }

        // Check if critical systems are failing
        if (!HealthCheckService::quickHealthCheck()) {
            return self::activateEmergencyMode('Critical systems health check failed');
        }

        return false;
    }

    /**
     * Get fallback user data when authentication is working but database access is limited
     */
    public static function getFallbackUserData($userId): array
    {
        return [
            'id' => $userId,
            'name' => 'User',
            'email' => 'user@medcapsule.com',
            'level' => 1,
            'xp' => 0,
            'progress' => 0,
            'message' => 'Your actual progress will be restored once systems are back online.',
            'is_fallback' => true
        ];
    }

    /**
     * Log emergency mode usage for analysis
     */
    public static function logEmergencyUsage(string $feature, array $context = []): void
    {
        try {
            $usageData = [
                'timestamp' => now(),
                'feature' => $feature,
                'context' => $context,
                'user_id' => auth()->id() ?? 'anonymous',
                'ip' => request()->ip() ?? 'unknown',
                'user_agent' => request()->userAgent() ?? 'unknown'
            ];

            self::storeEmergencyData('usage_' . time() . '_' . uniqid(), $usageData);
        } catch (Exception $e) {
            // Don't let logging failures affect emergency functionality
        }
    }

    /**
     * Get emergency mode statistics
     */
    public static function getEmergencyStatistics(): array
    {
        try {
            $dir = storage_path(self::FALLBACK_DATA_DIR);
            if (!is_dir($dir)) {
                return ['total_activations' => 0, 'usage_logs' => 0];
            }

            $files = glob($dir . '/*.json');
            $historyFiles = array_filter($files, function($file) {
                return str_contains(basename($file), 'emergency_mode_history');
            });
            
            $usageFiles = array_filter($files, function($file) {
                return str_contains(basename($file), 'usage_');
            });

            return [
                'total_activations' => count($historyFiles),
                'usage_logs' => count($usageFiles),
                'current_emergency_active' => self::isEmergencyModeActive()
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}