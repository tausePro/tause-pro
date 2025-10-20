<?php

namespace Tests\Feature\BrainBrand;

use App\Extensions\BrainBrand\System\Services\BrainBrandValidationService;
use Tests\TestCase;

class BrainBrandValidationServiceTest extends TestCase
{
    private BrainBrandValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new BrainBrandValidationService();
    }

    /** @test */
    public function it_validates_url_content_correctly()
    {
        $content = [
            'title' => 'Test Website',
            'content' => 'This is a test content from a website with enough text to pass validation. It contains multiple sentences and should be considered good quality content for training purposes.'
        ];

        $validation = $this->validationService->validateContent($content, 'url');

        $this->assertTrue($validation['is_valid']);
        $this->assertGreaterThan(70, $validation['quality_score']);
        $this->assertContains('Good quality content', $this->validationService->getQualityScoreDescription($validation['quality_score']));
    }

    /** @test */
    public function it_detects_empty_content()
    {
        $content = [
            'title' => 'Test',
            'content' => ''
        ];

        $validation = $this->validationService->validateContent($content, 'text');

        $this->assertFalse($validation['is_valid']);
        $this->assertLessThan(50, $validation['quality_score']);
        $this->assertContains('empty', $validation['warnings'][0]);
    }

    /** @test */
    public function it_validates_qa_content()
    {
        $content = [
            'question' => 'What is Brain Brand?',
            'answer' => 'Brain Brand is a centralized training system for chatbots and agents that allows you to train once and distribute knowledge to all your chatbots automatically.'
        ];

        $validation = $this->validationService->validateContent($content, 'qa');

        $this->assertTrue($validation['is_valid']);
        $this->assertGreaterThanOrEqual(80, $validation['quality_score']);
    }

    /** @test */
    public function it_detects_similar_question_and_answer()
    {
        $content = [
            'question' => 'What is AI?',
            'answer' => 'What is AI?' // Duplicate content
        ];

        $validation = $this->validationService->validateContent($content, 'qa');

        $this->assertTrue($validation['is_valid']); // Still valid but with warnings
        $this->assertContains('similar', $validation['warnings'][0]);
        $this->assertLessThan(90, $validation['quality_score']);
    }

    /** @test */
    public function it_provides_quality_score_descriptions()
    {
        $this->assertEquals('Excellent quality content', $this->validationService->getQualityScoreDescription(95));
        $this->assertEquals('Good quality content', $this->validationService->getQualityScoreDescription(80));
        $this->assertEquals('Acceptable quality content', $this->validationService->getQualityScoreDescription(65));
        $this->assertEquals('Poor quality content - consider improvements', $this->validationService->getQualityScoreDescription(45));
        $this->assertEquals('Very poor quality content - needs significant improvements', $this->validationService->getQualityScoreDescription(25));
    }
}