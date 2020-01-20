<?php

namespace LogBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use CoreBundle\Services\MongoConnector;
use LogBundle\Document\LogDocument;
use LogBundle\Lib\Utility;
use MongoDB\Collection;

class MaksymLogManager
{
    /**
     * @var ArrayCollection|LogDocument[]
     */
    private $logStorage;

    /**
     * @var Collection
     */
    private $collection;

    public function __construct(MongoConnector $connector)
    {
        $this->logStorage = new ArrayCollection();
        $this->collection = $connector->getCollection(Utility::LOG_DATABASE, Utility::LOG_COLLECTION);
    }

    public function add(LogDocument $logDocument)
    {
        foreach ($this->logStorage as $item) {
            if (
                $item->getLogTransactionId() === $logDocument->getLogTransactionId()
            ) {
                $item->mergeDetails($logDocument->getDetails());

                return;
            }
        }

        $this->logStorage->add($logDocument);
    }

    public function commit()
    {
        if (!$this->logStorage->isEmpty()) {
            $this->collection->insertMany($this->logStorage->toArray());
        }
    }

}