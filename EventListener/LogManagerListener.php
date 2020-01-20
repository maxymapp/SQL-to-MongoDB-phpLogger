<?php

namespace LogBundle\EventListener;

use LogBundle\Service\MaksymLogManager;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class LogManagerListener
{
    /** @var MaksymLogManager $logManager */
    private $logManager;

    public function __construct(MaksymLogManager $logManager)
    {
        $this->logManager = $logManager;
    }

    public function onTerminateHandler(PostResponseEvent $event)
    {
        $this->logManager->commit();
    }
}
