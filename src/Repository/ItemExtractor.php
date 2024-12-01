<?php

namespace App\Repository;

use App\Model\Book;
use App\Model\Car;
use App\Model\Item;
use App\Model\ItemType;
use Symfony\Component\Serializer\SerializerInterface;

readonly class ItemExtractor
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {}

    public function extractItem(array $data): Item
    {
        switch (ItemType::from($data['type'])) {
            case ItemType::BOOK:
                return $this->serializer->denormalize($data, Book::class);

            case ItemType::CAR:
                return $this->serializer->denormalize($data, Car::class);
        }
    }
}
