<?php

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidPaginationException;

class Pagination
{
    private const DEFAULT_LIMIT = 100;
    private const MAX_LIMIT = 100;
    private const MIN_LIMIT = 1;
    private const MIN_PAGE = 1;

    private readonly int $page;
    private readonly int $limit;

    private function __construct(int $page, int $limit)
    {
        if ($page < self::MIN_PAGE) {
            throw InvalidPaginationException::invalidPage();
        }

        if ($limit < self::MIN_LIMIT || $limit > self::MAX_LIMIT) {
            throw InvalidPaginationException::invalidLimit();
        }

        $this->page = $page;
        $this->limit = $limit;
    }

    public static function create(?int $page = null, ?int $limit = null): self
    {
        $page = $page ?? 1;
        $limit = $limit ?? self::DEFAULT_LIMIT;

        return new self($page, $limit);
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
