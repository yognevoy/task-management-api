<?php

namespace App\Shared\Application\DTO;

class PaginatedResponse
{
    public mixed $data;
    public int $total;
    public int $page;
    public int $limit;

    public function __construct(mixed $data, int $total, int $page, int $limit)
    {
        $this->data = $data;
        $this->total = $total;
        $this->page = $page;
        $this->limit = $limit;
    }
}
