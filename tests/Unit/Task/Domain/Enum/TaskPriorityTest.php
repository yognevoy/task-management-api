<?php

namespace App\Tests\Unit\Task\Domain\Enum;

use App\Task\Domain\Enum\TaskPriority;
use PHPUnit\Framework\TestCase;

class TaskPriorityTest extends TestCase
{
    public function testTaskPriorityHasCorrectValues(): void
    {
        $this->assertEquals('low', TaskPriority::LOW->value);
        $this->assertEquals('medium', TaskPriority::MEDIUM->value);
        $this->assertEquals('high', TaskPriority::HIGH->value);
        $this->assertEquals('critical', TaskPriority::CRITICAL->value);
    }

    public function testTaskPriorityCanConvertFromString(): void
    {
        $this->assertEquals(TaskPriority::LOW, TaskPriority::from('low'));
        $this->assertEquals(TaskPriority::MEDIUM, TaskPriority::from('medium'));
        $this->assertEquals(TaskPriority::HIGH, TaskPriority::from('high'));
        $this->assertEquals(TaskPriority::CRITICAL, TaskPriority::from('critical'));
    }

    public function testTaskPriorityToValuesReturnsAllValues(): void
    {
        $expectedValues = ['low', 'medium', 'high', 'critical'];
        $actualValues = TaskPriority::toValues();

        $this->assertEqualsCanonicalizing($expectedValues, $actualValues);
    }

    public function testTaskPriorityFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        TaskPriority::from('invalid_priority');
    }

    public function testTaskPriorityCasesReturnsAllCases(): void
    {
        $cases = TaskPriority::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(TaskPriority::LOW, $cases);
        $this->assertContains(TaskPriority::MEDIUM, $cases);
        $this->assertContains(TaskPriority::HIGH, $cases);
        $this->assertContains(TaskPriority::CRITICAL, $cases);
    }
}
