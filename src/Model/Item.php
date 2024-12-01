<?php

namespace App\Model;

abstract class Item
{
    public function __construct(
        public string $title,
        public string $description,
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public ItemType $type,
    ) {}
}
