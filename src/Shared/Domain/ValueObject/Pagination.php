<?php

namespace App\Shared\Domain\ValueObject;

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
        $this->page = max(
            self::MIN_PAGE,
            $page
        );
        $this->limit = max(
            self::MIN_LIMIT,
            min($limit === 0 ? self::DEFAULT_LIMIT : $limit, self::MAX_LIMIT)
        );
    }

    public static function create(int $page = 1, int $limit = self::DEFAULT_LIMIT): self
    {
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
