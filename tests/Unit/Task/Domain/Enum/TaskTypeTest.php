<?php

namespace App\Tests\Unit\Task\Domain\Enum;

use App\Task\Domain\Enum\TaskType;
use PHPUnit\Framework\TestCase;

class TaskTypeTest extends TestCase
{
    public function testTaskTypeHasCorrectValues(): void
    {
        $this->assertEquals('task', TaskType::TASK->value);
        $this->assertEquals('bug', TaskType::BUG->value);
        $this->assertEquals('feature', TaskType::FEATURE->value);
    }

    public function testTaskTypeCanConvertFromString(): void
    {
        $this->assertEquals(TaskType::TASK, TaskType::from('task'));
        $this->assertEquals(TaskType::BUG, TaskType::from('bug'));
        $this->assertEquals(TaskType::FEATURE, TaskType::from('feature'));
    }

    public function testTaskTypeToValuesReturnsAllValues(): void
    {
        $expectedValues = ['task', 'bug', 'feature'];
        $actualValues = TaskType::toValues();

        $this->assertEqualsCanonicalizing($expectedValues, $actualValues);
    }

    public function testTaskTypeFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        TaskType::from('invalid_type');
    }

    public function testTaskTypeCasesReturnsAllCases(): void
    {
        $cases = TaskType::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(TaskType::TASK, $cases);
        $this->assertContains(TaskType::BUG, $cases);
        $this->assertContains(TaskType::FEATURE, $cases);
    }
}
