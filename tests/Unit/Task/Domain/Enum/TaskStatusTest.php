<?php

namespace App\Tests\Unit\Task\Domain\Enum;

use App\Task\Domain\Enum\TaskStatus;
use PHPUnit\Framework\TestCase;

class TaskStatusTest extends TestCase
{
    public function testTaskStatusHasCorrectValues(): void
    {
        $this->assertEquals('todo', TaskStatus::TODO->value);
        $this->assertEquals('in_progress', TaskStatus::IN_PROGRESS->value);
        $this->assertEquals('done', TaskStatus::DONE->value);
    }

    public function testTaskStatusCanConvertFromString(): void
    {
        $this->assertEquals(TaskStatus::TODO, TaskStatus::from('todo'));
        $this->assertEquals(TaskStatus::IN_PROGRESS, TaskStatus::from('in_progress'));
        $this->assertEquals(TaskStatus::DONE, TaskStatus::from('done'));
    }

    public function testTaskStatusToValuesReturnsAllValues(): void
    {
        $expectedValues = ['todo', 'in_progress', 'done'];
        $actualValues = TaskStatus::toValues();

        $this->assertEqualsCanonicalizing($expectedValues, $actualValues);
    }

    public function testTaskStatusFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        TaskStatus::from('invalid_status');
    }

    public function testTaskStatusCasesReturnsAllCases(): void
    {
        $cases = TaskStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(TaskStatus::TODO, $cases);
        $this->assertContains(TaskStatus::IN_PROGRESS, $cases);
        $this->assertContains(TaskStatus::DONE, $cases);
    }
}
