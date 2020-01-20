<?php

namespace LogBundle\EventListener\DirectMail;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use JobsBundle\Entity\DirectMail\DirectMailInfo;
use LogBundle\Document\LogDocument;
use LogBundle\Lib\Utility;
use LogBundle\Service\MaksymLogger;

class DirectMailInfoSubscriber implements EventSubscriber
{
    /** @var MaksymLogger $maksymLogger */
    private $maksymLogger;

    /** @var DirectMailInfo|null $jobInfo */
    private $jobInfo;

    /** @var array $updatedFields */
    private $updatedFields;

    /** @var string|null  */
    private $eventType;

    public function __construct(MaksymLogger $maksymLogger)
    {
        $this->maksymLogger     = $maksymLogger;
        $this->jobInfo       = null;
        $this->updatedFields = [];
        $this->eventType     = null;
    }

    public function getSubscribedEvents()
    {
        return [
            'preUpdate',
            'postFlush',
        ];
    }



    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof DirectMailInfo) {
            $this->jobInfo       = $entity;
            $this->updatedFields = $args->getEntityChangeSet();
            $this->eventType = 'preUpdate';
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if (!is_null($this->jobInfo) && !empty($this->updatedFields) && $this->eventType === 'preUpdate') {
            $this->logUpdated();
        }
        $this->eventType = null;
    }



    private function logUpdated()
    {
        $jobInfo             = $this->jobInfo;
        $this->jobInfo       = null;
        $fields              = Utility::normalizeFields($this->updatedFields);
        $this->updatedFields = [];

        $this->maksymLogger->log(
            LogDocument::createInstance(
                $jobInfo->getJobDM()->getLogTransactionId(),
                $jobInfo->getJobDM()->getId(),
                Utility::LOG_TYPE_DIRECT_MAIL_UPDATE,
                'Job ' . $jobInfo->getJobDM()->getFriendlyId() . ' updated.',
                $fields
        ));
    }
}