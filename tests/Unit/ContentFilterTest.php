<?php
/**
 * Content Filter Unit Tests
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ContentFilter;

class ContentFilterTest extends TestCase
{
    private ContentFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new ContentFilter();
    }

    public function testProfanityDetection(): void
    {
        $result = $this->filter->analyze('This is a clean message');
        $this->assertFalse($result['blocked']);
        $this->assertEmpty($result['flags']);
    }

    public function testPiiDetection(): void
    {
        $result = $this->filter->analyze('Contact me at john@example.com');
        $this->assertContains('pii_email', array_column($result['flags'], 'rule_code'));
    }

    public function testPhoneDetection(): void
    {
        $result = $this->filter->analyze('Call me at 555-123-4567');
        $this->assertContains('pii_phone', array_column($result['flags'], 'rule_code'));
    }

    public function testCleanContent(): void
    {
        $result = $this->filter->analyze('We should improve the onboarding process for new hires.');
        $this->assertFalse($result['blocked']);
        $this->assertEquals(0, $result['score']);
    }
}
