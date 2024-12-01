<?php

namespace App\Model;

class Car extends Item
{
    public function __construct(
        string $title,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        string $description,
        public string $brand,
        public int $mileage,
    ) {
        parent::__construct($title, $description, $startDate, $endDate, ItemType::BOOK);
    }
}
