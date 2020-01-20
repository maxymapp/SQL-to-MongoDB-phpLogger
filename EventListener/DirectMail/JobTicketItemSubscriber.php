<?php

namespace LogBundle\EventListener\DirectMail;

use Doctrine\Common\EventSubscriber;
use JobsBundle\Entity\DirectMail\JobTicketItem;
use LogBundle\Document\LogDocument;
use LogBundle\Service\MaksymLogger;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use LogBundle\Lib\Utility;

class JobTicketItemSubscriber implements EventSubscriber
{

    /** @var MaksymLogger $logger */
    private $logger;

    /** @var JobTicketItem|null $job */
    private $ticketItem;

    /** @var array $updatedFields */
    private $updatedFields;

    /** @var string|null */
    private $eventType;


    public function __construct(MaksymLogger $logger)
    {
        $this->logger        = $logger;
        $this->ticketItem    = null;
        $this->updatedFields = [];
        $this->eventType     = null;
    }

    public function getSubscribedEvents()
    {
        return [

            'preUpdate',
            'postFlush'
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof JobTicketItem) {
            $this->ticketItem = $entity;
            $this->updatedFields = $args->getEntityChangeSet();
            $this->eventType = 'preUpdate';
        }

    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if (!is_null($this->ticketItem) && !empty($this->updatedFields) && $this->eventType === 'preUpdate') {
            $this->logUpdated();
        }

        $this->eventType = null;
    }

    private function logUpdated() {
        $ticketItem          = $this->ticketItem;
        $this->ticketItem    = null;
        $fields              = Utility::normalizeFields($this->updatedFields);
        $this->updatedFields = [];
//        $job = $ticketItem->getTicket()->getJob();

        $this->logger->log(
            LogDocument::createInstance(
                $job->getLogTransactionId(), $job->getId(),
            Utility::LOG_TYPE_GENERIC,
            'Job ' . $job->getFriendlyId() . ' updated.',
            $fields
        ));

    }
}