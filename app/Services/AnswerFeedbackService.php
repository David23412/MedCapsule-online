<?php

namespace App\Services;

class AnswerFeedbackService
{
    protected $medicalDomainService;
    protected $medicalPatternService;

    public function __construct(MedicalDomainService $medicalDomainService, MedicalPatternService $medicalPatternService)
    {
        $this->medicalDomainService = $medicalDomainService;
        $this->medicalPatternService = $medicalPatternService;
    }

    /**
     * Generate detailed feedback for answer evaluation
     */
    public function generateDetailedFeedback(
        string $submitted, 
        string $correct, 
        array $metrics, 
        bool $isCorrect, 
        float $similarity, 
        float $threshold, 
        ?string $courseTopic = null
    ): string {
        if ($isCorrect) {
            return $this->generatePositiveFeedback($submitted, $correct, $metrics, $similarity, $courseTopic);
        } else {
            return $this->generateImprovementFeedback($submitted, $correct, $metrics, $similarity, $threshold, $courseTopic);
        }
    }

    /**
     * Generate positive feedback for correct answers
     */
    private function generatePositiveFeedback(string $submitted, string $correct, array $metrics, float $similarity, ?string $courseTopic): string
    {
        $feedback = "✅ **Excellent work!** Your answer demonstrates a solid understanding of the concept.\n\n";
        
        // Highlight strengths based on metrics
        if ($metrics['keyword'] > 0.8) {
            $feedback .= "🎯 **Key Terms**: You've included the essential medical terminology.\n";
        }
        
        if ($metrics['conceptual'] > 0.7) {
            $feedback .= "🧠 **Conceptual Understanding**: Your explanation shows good grasp of the underlying principles.\n";
        }
        
        if (isset($metrics['pattern']['score']) && $metrics['pattern']['score'] > 0.6) {
            $feedback .= "🔄 **Pattern Recognition**: You've correctly identified the relevant medical patterns.\n";
        }
        
        // Domain-specific praise
        if ($courseTopic) {
            $feedback .= $this->getDomainSpecificPraise($courseTopic);
        }
        
        // Similarity score feedback
        if ($similarity > 0.9) {
            $feedback .= "\n💯 **Precision**: Your answer closely matches the expected response (similarity: " . round($similarity * 100, 1) . "%)";
        } else {
            $feedback .= "\n✨ **Good Match**: Your answer aligns well with the expected response (similarity: " . round($similarity * 100, 1) . "%)";
        }
        
        return $feedback;
    }

    /**
     * Generate improvement feedback for incorrect answers
     */
    private function generateImprovementFeedback(
        string $submitted, 
        string $correct, 
        array $metrics, 
        float $similarity, 
        float $threshold, 
        ?string $courseTopic
    ): string {
        $feedback = "📚 **Let's improve this answer together!**\n\n";
        
        // Identify specific areas for improvement
        $improvements = [];
        
        if ($metrics['keyword'] < 0.5) {
            $improvements[] = "🔑 **Key Terms**: Try to include more specific medical terminology from the correct answer.";
        }
        
        if ($metrics['conceptual'] < 0.4) {
            $improvements[] = "💡 **Conceptual Clarity**: Focus on explaining the underlying biological or physiological principles.";
        }
        
        if (isset($metrics['pattern']['score']) && $metrics['pattern']['score'] < 0.3) {
            $improvements[] = "🔄 **Pattern Recognition**: Look for cause-and-effect relationships or sequential processes.";
        }
        
        // Check for missing critical terms
        if ($courseTopic) {
            $patterns = $this->medicalPatternService->getCourseSpecificPatterns($courseTopic);
            $missingTerms = $this->findMissingCriticalTerms($submitted, $correct, $patterns['critical_terms'] ?? []);
            
            if (!empty($missingTerms)) {
                $improvements[] = "⚠️ **Critical Terms**: Consider including: " . implode(', ', $missingTerms);
            }
        }
        
        // Add improvements to feedback
        if (!empty($improvements)) {
            $feedback .= "**Areas for improvement:**\n";
            foreach ($improvements as $improvement) {
                $feedback .= "• " . $improvement . "\n";
            }
            $feedback .= "\n";
        }
        
        // Provide hints based on similarity
        if ($similarity > 0.6) {
            $feedback .= "🎯 **You're on the right track!** Your answer has good elements but needs refinement.\n";
        } elseif ($similarity > 0.3) {
            $feedback .= "🔍 **Partial Understanding**: You've grasped some concepts. Try to be more specific and complete.\n";
        } else {
            $feedback .= "📖 **Review Needed**: Consider reviewing the relevant material and try to address the question more directly.\n";
        }
        
        // Similarity score
        $feedback .= "\n📊 **Similarity Score**: " . round($similarity * 100, 1) . "% (threshold: " . round($threshold * 100, 1) . "%)";
        
        return $feedback;
    }

    /**
     * Get domain-specific praise
     */
    private function getDomainSpecificPraise(string $courseTopic): string
    {
        $praise = [
            'cardiovascular' => "❤️ **Cardiovascular Mastery**: Great understanding of heart and circulation concepts!\n",
            'respiratory' => "🫁 **Respiratory Excellence**: Solid grasp of breathing and lung function!\n",
            'nervous' => "🧠 **Neurological Insight**: Excellent understanding of nervous system principles!\n",
            'gastrointestinal' => "🍽️ **Digestive Expertise**: Good knowledge of GI system processes!\n",
            'endocrine' => "⚖️ **Hormonal Balance**: Strong understanding of endocrine regulation!\n",
        ];
        
        return $praise[$courseTopic] ?? "🎓 **Subject Mastery**: Great understanding of the medical concepts!\n";
    }

    /**
     * Find missing critical terms
     */
    private function findMissingCriticalTerms(string $submitted, string $correct, array $criticalTerms): array
    {
        $missing = [];
        $submittedLower = strtolower($submitted);
        $correctLower = strtolower($correct);
        
        foreach ($criticalTerms as $term) {
            if (strpos($correctLower, strtolower($term)) !== false && 
                strpos($submittedLower, strtolower($term)) === false) {
                $missing[] = $term;
            }
        }
        
        return $missing;
    }

    /**
     * Generate quick feedback for simple evaluations
     */
    public function generateQuickFeedback(bool $isCorrect, float $similarity): string
    {
        if ($isCorrect) {
            if ($similarity > 0.9) {
                return "Perfect! Excellent understanding demonstrated.";
            } elseif ($similarity > 0.8) {
                return "Great job! Very good grasp of the concept.";
            } else {
                return "Correct! Good understanding shown.";
            }
        } else {
            if ($similarity > 0.7) {
                return "Close! You're on the right track but need minor adjustments.";
            } elseif ($similarity > 0.5) {
                return "Partially correct. Review the key concepts and try again.";
            } else {
                return "Needs improvement. Please review the material and focus on the main points.";
            }
        }
    }
}