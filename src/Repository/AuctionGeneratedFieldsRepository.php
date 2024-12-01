<?php

namespace App\Repository;

use App\Entity\AuctionGeneratedFields;
use App\Model\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Statement;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @extends ServiceEntityRepository<AuctionGeneratedFields>
 */
class AuctionGeneratedFieldsRepository extends ServiceEntityRepository implements AuctionInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly SerializerInterface $serializer,
        private readonly ItemExtractor $itemExtractor,
    ) {
        parent::__construct($registry, AuctionGeneratedFields::class);
    }

    public function truncate(): void
    {
        $cmd = $this->getClassMetadata();
        $connection = $this->getEntityManager()->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
        $connection->executeStatement($q);
    }

    public function createAuction(Item $item): AuctionGeneratedFields
    {
        $data = $this->serializer->normalize($item);
        $auction = new AuctionGeneratedFields($data);
        $this->getEntityManager()->persist($auction);
        $this->getEntityManager()->flush();

        return $auction;
    }

    public function getInsertStatement(int $batchSize): Statement
    {
        $statement = 'INSERT INTO '.$this->getClassMetadata()->getTableName().' (item) VALUES ';
        $values = [];
        for ($i = 0; $i<$batchSize; $i++) {
            $values[] = "(:item$i)";
        }
        $values = implode(',', $values);

        return $this->getEntityManager()
            ->getConnection()
            ->prepare($statement.$values)
            ;
    }

    public function updateAuction(AuctionGeneratedFields $auction, Item $item): void
    {
        $data = $this->serializer->normalize($item);
        $auction->setItem($data);
        $this->getEntityManager()->flush();
    }
    
    public function extractItem(AuctionGeneratedFields $auction): Item
    {
        return $this->itemExtractor->extractItem($auction->getItem());
    }
}
