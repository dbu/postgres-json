<?php

namespace App\Model;

class Book extends Item
{
    public function __construct(
        string $title,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        string $description,
        public string $author,
        public string $genre,
    ) {
        parent::__construct($title, $description, $startDate, $endDate, ItemType::BOOK);
    }
}
