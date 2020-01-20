<?php

namespace LogBundle\EventListener\DirectMail;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use JobsBundle\Entity\DirectMail\DirectMail;
use LogBundle\Document\LogDocument;
use LogBundle\Lib\Utility;
use LogBundle\Service\MaksymLogger;

class DirectMailSubscriber implements EventSubscriber
{
    /** @var MaksymLogger $logger */
    private $logger;

    /** @var DirectMail|null $job */
    private $job;

    /** @var array $updatedFields */
    private $updatedFields;

    /** @var string|null */
    private $eventType;



    public function __construct(MaksymLogger $logger)
    {
        $this->logger        = $logger;
        $this->job           = null;
        $this->updatedFields = [];
        $this->eventType     = null;
    }

    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'preUpdate',
            'postFlush',
        ];
    }

    public function postPersist(LifecycleEventArgs $args)
    {

        $entity = $args->getObject();
        if ($entity instanceof DirectMail) {
            $this->job       = $entity;
            $this->eventType = 'postPersist';
        }
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof DirectMail) {
            $this->job           = $entity;
            $this->updatedFields = $args->getEntityChangeSet();

//            if ($args->hasChangedField('jobTicket')) {
//                stub - why???
//            }

            $this->eventType = 'preUpdate';
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if (!is_null($this->job) && $this->eventType === 'postPersist') {
            $this->logCreated();
        }

        if (!is_null($this->job) && !empty($this->updatedFields) && $this->eventType === 'preUpdate') {

            $this->logUpdated();
        }

        $this->eventType = null;
    }

    private function logCreated()
    {
        $job       = $this->job;
        $this->job = null;
        $fieldsNew = [
            'campaign_name' => [
                $job->getCampaign(),
            ],
            'mailer_type'   => [
                $job->getInfo()->getMailer(),
            ],
            'postage_type'  => [
                $job->getInfo()->getPostage(),
            ],
            'due_date'      => [
                $job->getInfo()->getDueDate(),
            ],
        ];

        $this->logger->log(
            LogDocument::createInstance(
            $job->getLogTransactionId(),
            $job->getId(),
        Utility::LOG_TYPE_DIRECT_MAIL_CREATE,
        'Job ' . $job->getFriendlyId() . ' uploaded.',
            Utility::normalizeFields($fieldsNew)
        ));
    }

    private function logUpdated()
    {
        $job                 = $this->job;
        $this->job           = null;
        $fields              = Utility::normalizeFields($this->updatedFields);
        $this->updatedFields = [];

        $this->logger->log(
            LogDocument::createInstance(
            $job->getLogTransactionId(),
            $job->getId(),
        Utility::LOG_TYPE_DIRECT_MAIL_UPDATE,
        'Job ' . $job->getFriendlyId() . ' updated.',
            $this->updatedFields
        ));
    }
}