<?php

namespace LogBundle\EventListener\DirectMail;

use Doctrine\Common\EventSubscriber;
use JobsBundle\Entity\DirectMail\JobTicket;
use LogBundle\Document\LogDocument;
use LogBundle\Lib\Utility;
use LogBundle\Service\MaksymLogger;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

class JobTicketSubscriber implements EventSubscriber
{
    /** @var MaksymLogger $logger */
    private $logger;

    /** @var JobTicket|null $job */
    private $jobTicket;

    /** @var array $updatedFields */
    private $updatedFields;

    /** @var string|null */
    private $eventType;


    public function __construct(MaksymLogger $logger)
    {
        $this->logger        = $logger;
        $this->jobTicket     = null;
        $this->updatedFields = [];
        $this->eventType     = null;
    }

    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'preUpdate',
            'postFlush'
//            ,'postRemove',
        ];
    }

    public function postPersist(LifecycleEventArgs $args)
    {

        $entity = $args->getObject();
        if ($entity instanceof JobTicket) {
            $this->jobTicket = $entity;
            $this->eventType = 'postPersist';
        }
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof JobTicket) {
            $this->jobTicket = $entity;
            $this->updatedFields = $args->getEntityChangeSet();
            $this->eventType = 'preUpdate';
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if (!is_null($this->jobTicket) && $this->eventType === 'postPersist') {
            $this->logCreated();
        }

        if (!is_null($this->jobTicket) && !empty($this->updatedFields) && $this->eventType === 'preUpdate') {
            $this->logUpdated();
        }

        $this->eventType = null;
    }

    private function logCreated() {
        $jobTicket = $this->jobTicket;
        $this->jobTicket = null;
        $job = $jobTicket->getJob();
        $this->logger->log(
            LogDocument::createInstance($job->getLogTransactionId(),
            $job->getId(),
        Utility::LOG_TYPE_DIRECT_MAIL_CREATE,
        'Job ' . $job->getFriendlyId() . ' updated.',
            Utility::normalizeFields(

                $jobTicket->getItems()

            )));
    }

    private function logUpdated() {
        $jobTicket              = $this->jobTicket;
        $this->jobTicket    = null;
        $fields              = Utility::normalizeFields($this->updatedFields);
        $this->updatedFields = [];
        $job = $jobTicket->getJob();

        $this->logger->log(
            LogDocument::createInstance($job->getLogTransactionId(), $job->getId(),
        Utility::LOG_TYPE_GENERIC,
        'Job ' . $job->getFriendlyId() . ' updated.',
            $fields
        ));

    }


}