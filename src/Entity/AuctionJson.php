<?php

namespace App\Entity;

use App\Repository\AuctionJsonbRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Plain json field.
 *
 * This is less efficient to query.
 */
#[ORM\Entity(repositoryClass: AuctionJsonbRepository::class)]
class AuctionJson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column]
    private \DateTimeImmutable $startDate;

    #[ORM\Column]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(nullable: true)]
    private ?int $currentPrice = null;

    #[ORM\Column(type: Types::JSON)]
    private array $item;

    public function __construct(
        string $title, \DateTimeImmutable $start, \DateTimeImmutable $end, array $item
    ) {
        $this->title = $title;
        $this->startDate = $start;
        $this->endDate = $end;
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

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
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
