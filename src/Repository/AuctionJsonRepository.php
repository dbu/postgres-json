<?php

namespace App\Repository;

use App\Entity\AuctionJson;
use App\Model\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Statement;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @extends ServiceEntityRepository<AuctionJson>
 */
class AuctionJsonRepository extends ServiceEntityRepository implements AuctionInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly SerializerInterface $serializer,
        private readonly ItemExtractor $itemExtractor,
    ) {
        parent::__construct($registry, AuctionJson::class);
    }

    public function truncate(): void
    {
        $cmd = $this->getClassMetadata();
        $connection = $this->getEntityManager()->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
        $connection->executeStatement($q);
    }

    public function createAuction(Item $item): AuctionJson
    {
        $data = $this->serializer->normalize($item);
        $auction = new AuctionJson($item->title, $item->startDate, $item->endDate, $data);
        $this->getEntityManager()->persist($auction);
        $this->getEntityManager()->flush();

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

    public function updateAuction(AuctionJson $auction, Item $item): void
    {
        $data = $this->serializer->normalize($item);
        $auction->setTitle($item->title);
        $auction->setStartDate($item->startDate);
        $auction->setEndDate($item->endDate);
        $auction->setItem($data);
        $this->getEntityManager()->flush();
    }

    public function extractItem(AuctionJson $auction): Item
    {
        return $this->itemExtractor->extractItem($auction->getItem());
    }

    public function countAuthor(string $authorName): int
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('COUNT(a)')
            // Need to qualify with "a." in front of item
            // ->where("JSON_CONTAINS(a.item, :author, '$.author') = 1")
            // we need to compare with a JSON fragment, hence the quotes
            // ->setParameter('author', '"'.$authorName.'"')
            // alternate way of achieving the comparison
            ->where("JSON_EXTRACT(a.item, '$.author') = :author")
            ->setParameter('author', $authorName)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }
}
