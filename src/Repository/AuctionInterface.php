<?php
namespace App\Repository;

use App\Model\Item;
use Doctrine\DBAL\Statement;

interface AuctionInterface
{
    public function createAuction(Item $item): object;

    /**
     * Get the prepared statement to insert a number of rows.
     *
     * The parameters have a row number suffix (0-based).
     */
    public function getInsertStatement(int $batchSize): Statement;
}
