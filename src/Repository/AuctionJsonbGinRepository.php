<?php

namespace App\Repository;

use App\Entity\AuctionJsonbGin;
use App\Model\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @extends ServiceEntityRepository<AuctionJsonbGin>
 */
class AuctionJsonbGinRepository extends ServiceEntityRepository implements AuctionInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly SerializerInterface $serializer,
        private readonly ItemExtractor $itemExtractor,
    ) {
        parent::__construct($registry, AuctionJsonbGin::class);
    }

    public function truncate(): void
    {
        $cmd = $this->getClassMetadata();
        $connection = $this->getEntityManager()->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
        $connection->executeStatement($q);
    }

    public function createAuction(Item $item): AuctionJsonbGin
    {
        $data = $this->serializer->normalize($item);
        $auction = new AuctionJsonbGin($item->title, $item->startDate, $item->endDate, $data);
        $this->getEntityManager()->persist($auction);

        return $auction;
    }

    public function getInsertStatement(int $batchSize): Statement
    {
        $statement = 'INSERT INTO '.$this->getClassMetadata()->getTableName().' (title, start_date, end_date, item) VALUES ';
        $values = [];
        for ($i = 0; $i<$batchSize; $i++) {
            $values[] = "(:title$i, :startDate$i, :endDate$i, :item$i)";
        }
        $values = implode(',', $values);

        return $this->getEntityManager()
            ->getConnection()
            ->prepare($statement.$values)
        ;
    }

    public function updateAuction(AuctionJsonbGin $auction, Item $item): void
    {
        $data = $this->serializer->normalize($item);
        $auction->setTitle($item->title);
        $auction->setStartDate($item->startDate);
        $auction->setEndDate($item->endDate);
        $auction->setItem($data);
    }

    public function extractItem(AuctionJsonbGin $auction): Item
    {
        return $this->itemExtractor->extractItem($auction->getItem());
    }

    public function countAuthor(string $authorName): int
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('COUNT(a)')
            // A parameter needs to be the whole JSON object
            ->where("JSONB_CONTAINS(a.item, :criteria) = true")
            // With the type, Doctrine will encode and escape correctly
            ->setParameter('criteria', ['author' => $authorName], Types::JSON)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }
}
