<?php

namespace App\Entity;

use App\Model\ItemType;
use App\Repository\AuctionJsonbRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Variant where we maintain title, start and end date inside the item data.
 *
 * Postgres will update those generated columns automatically on insert and update.
 */
#[ORM\Entity(repositoryClass: AuctionJsonbRepository::class)]
#[ORM\Index(columns: ['start_date'])]
#[ORM\Index(columns: ['author'])]
class AuctionGeneratedFieldsIndexed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(
        length: 255,
        insertable: false,
        updatable: false,
        columnDefinition: "VARCHAR(255) generated always as (item->>'title') stored NOT NULL",
        generated: "ALWAYS",
    )]
    private string $title;

    #[ORM\Column(
        insertable: false,
        updatable: false,
        columnDefinition: "TIMESTAMP(0) generated always as (text_to_timestamp(item->>'startDate')) stored NOT NULL",
        generated: "ALWAYS",
    )]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(
        insertable: false,
        updatable: false,
        columnDefinition: "TIMESTAMP(0) generated always as (text_to_timestamp(item->>'endDate')) stored NOT NULL",
        generated: "ALWAYS",
    )]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(
        length: 255,
        nullable: true,
        insertable: false,
        updatable: false,
        columnDefinition: "VARCHAR(255) generated always as (item->>'author') stored",
        generated: "ALWAYS",
    )]
    private ?string $author = null;

    #[ORM\Column(nullable: true)]
    private ?int $currentPrice = null;

    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    private array $item;

    public function __construct(
        array $item
    ) {
        $this->item = $item;
    }

    public function getId(): int
    {
        if (!isset($this->id)) {
            throw new \LogicException('Do not request id before persisting');
        }
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getCurrentPrice(): ?int
    {
        return $this->currentPrice;
    }

    public function setCurrentPrice(?int $currentPrice): static
    {
        $this->currentPrice = $currentPrice;

        return $this;
    }

    public function getItemType(): ItemType
    {
        return $this->itemType;
    }

    public function getItem(): array
    {
        return $this->item;
    }

    public function setItem(array $item): static
    {
        $this->item = $item;

        return $this;
    }
}
