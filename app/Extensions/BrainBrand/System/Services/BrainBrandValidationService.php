<?php

namespace App\Extensions\BrainBrand\System\Services;

use App\Extensions\BrainBrand\System\Models\BrainBrand;
use Illuminate\Support\Facades\Log;

class BrainBrandValidationService
{
    /**
     * Validate content quality before training
     */
    public function validateContent(array $content, string $type): array
    {
        $validation = [
            'is_valid' => true,
            'warnings' => [],
            'suggestions' => [],
            'quality_score' => 100
        ];

        switch ($type) {
            case 'url':
                $validation = $this->validateUrlContent($content, $validation);
                break;
            case 'text':
                $validation = $this->validateTextContent($content, $validation);
                break;
            case 'qa':
                $validation = $this->validateQaContent($content, $validation);
                break;
            case 'file':
                $validation = $this->validateFileContent($content, $validation);
                break;
        }

        Log::info('BrainBrand content validation completed', [
            'type' => $type,
            'is_valid' => $validation['is_valid'],
            'quality_score' => $validation['quality_score'],
            'warnings_count' => count($validation['warnings'])
        ]);

        return $validation;
    }

    /**
     * Validate URL content quality
     */
    private function validateUrlContent(array $content, array $validation): array
    {
        if (empty($content['content'])) {
            $validation['is_valid'] = false;
            $validation['warnings'][] = 'Content is empty';
            $validation['quality_score'] -= 50;
        }

        if (strlen($content['content']) < 100) {
            $validation['warnings'][] = 'Content is very short (less than 100 characters)';
            $validation['quality_score'] -= 20;
        }

        if (strlen($content['content']) > 50000) {
            $validation['warnings'][] = 'Content is very long (over 50,000 characters) - consider splitting';
            $validation['quality_score'] -= 10;
        }

        // Check for duplicate content patterns
        if ($this->hasDuplicatePatterns($content['content'])) {
            $validation['warnings'][] = 'Content contains repetitive patterns';
            $validation['quality_score'] -= 15;
        }

        // Check for meaningful title
        if (empty($content['title']) || strlen($content['title']) < 5) {
            $validation['suggestions'][] = 'Consider adding a more descriptive title';
            $validation['quality_score'] -= 5;
        }

        return $validation;
    }

    /**
     * Validate text content quality
     */
    private function validateTextContent(array $content, array $validation): array
    {
        if (empty($content['content'])) {
            $validation['is_valid'] = false;
            $validation['warnings'][] = 'Text content is empty';
            $validation['quality_score'] -= 50;
        }

        if (strlen($content['content']) < 50) {
            $validation['warnings'][] = 'Text content is very short (less than 50 characters)';
            $validation['quality_score'] -= 30;
        }

        // Check for proper sentence structure
        $sentences = preg_split('/[.!?]+/', $content['content'], -1, PREG_SPLIT_NO_EMPTY);
        if (count($sentences) < 2) {
            $validation['suggestions'][] = 'Consider adding more complete sentences';
            $validation['quality_score'] -= 10;
        }

        // Check for meaningful title
        if (empty($content['title']) || strlen($content['title']) < 3) {
            $validation['suggestions'][] = 'Please provide a descriptive title';
            $validation['quality_score'] -= 10;
        }

        return $validation;
    }

    /**
     * Validate Q&A content quality
     */
    private function validateQaContent(array $content, array $validation): array
    {
        if (empty($content['question'])) {
            $validation['is_valid'] = false;
            $validation['warnings'][] = 'Question is empty';
            $validation['quality_score'] -= 50;
        }

        if (empty($content['answer'])) {
            $validation['is_valid'] = false;
            $validation['warnings'][] = 'Answer is empty';
            $validation['quality_score'] -= 50;
        }

        if (strlen($content['question']) < 10) {
            $validation['warnings'][] = 'Question is very short (less than 10 characters)';
            $validation['quality_score'] -= 20;
        }

        if (strlen($content['answer']) < 20) {
            $validation['warnings'][] = 'Answer is very short (less than 20 characters)';
            $validation['quality_score'] -= 20;
        }

        // Check if question and answer are too similar (might be duplicate)
        if ($this->calculateSimilarity($content['question'], $content['answer']) > 0.8) {
            $validation['warnings'][] = 'Question and answer are very similar - might be a duplicate';
            $validation['quality_score'] -= 15;
        }

        return $validation;
    }

    /**
     * Validate file content quality
     */
    private function validateFileContent(array $content, array $validation): array
    {
        if (empty($content['content'])) {
            $validation['is_valid'] = false;
            $validation['warnings'][] = 'File content is empty';
            $validation['quality_score'] -= 50;
        }

        if (strlen($content['content']) > 100000) {
            $validation['warnings'][] = 'File content is very large (over 100,000 characters) - processing might be slow';
            $validation['quality_score'] -= 10;
        }

        // Check for file title
        if (empty($content['title']) || strlen($content['title']) < 3) {
            $validation['suggestions'][] = 'Consider adding a descriptive title for the file content';
            $validation['quality_score'] -= 5;
        }

        return $validation;
    }

    /**
     * Check for duplicate patterns in content
     */
    private function hasDuplicatePatterns(string $content): bool
    {
        $lines = explode("\n", $content);
        $lineCount = count($lines);

        if ($lineCount < 3) {
            return false;
        }

        $duplicates = 0;
        for ($i = 0; $i < $lineCount - 1; $i++) {
            $currentLine = trim($lines[$i]);
            $nextLine = trim($lines[$i + 1]);

            if (!empty($currentLine) && !empty($nextLine)) {
                $similarity = $this->calculateSimilarity($currentLine, $nextLine);
                if ($similarity > 0.9) {
                    $duplicates++;
                }
            }
        }

        return ($duplicates / $lineCount) > 0.3; // More than 30% duplicates
    }

    /**
     * Calculate similarity between two strings
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if (empty($str1) || empty($str2)) {
            return 0.0;
        }

        $length1 = strlen($str1);
        $length2 = strlen($str2);

        if ($length1 === 0 || $length2 === 0) {
            return 0.0;
        }

        // Simple similarity based on common words
        $words1 = explode(' ', $str1);
        $words2 = explode(' ', $str2);

        $commonWords = array_intersect($words1, $words2);
        $totalWords = array_unique(array_merge($words1, $words2));

        return count($commonWords) / count($totalWords);
    }

    /**
     * Get quality score description
     */
    public function getQualityScoreDescription(int $score): string
    {
        if ($score >= 90) {
            return 'Excellent quality content';
        } elseif ($score >= 75) {
            return 'Good quality content';
        } elseif ($score >= 60) {
            return 'Acceptable quality content';
        } elseif ($score >= 40) {
            return 'Poor quality content - consider improvements';
        } else {
            return 'Very poor quality content - needs significant improvements';
        }
    }
}