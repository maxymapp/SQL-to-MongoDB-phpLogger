<?php

namespace LogBundle\EventListener;

use LogBundle\Document\ActivityLogDocument;
use LogBundle\Service\ActivityLogService;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ActivityListener
{
    /** @var TokenStorageInterface $tokenStorage */
    private $tokenStorage;
    /** @var ActivityLogService $activityLogger */
    private $activityLogger;

    public function __construct(TokenStorageInterface $tokenStorage, ActivityLogService $activityLogger)
    {
        $this->tokenStorage   = $tokenStorage;
        $this->activityLogger = $activityLogger;
    }

    public function onRequestHandler(FilterControllerEvent $event)
    {
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        /** @var TokenInterface $token */
        $token = $this->tokenStorage->getToken();
        if ($token instanceof UsernamePasswordToken) {
            $this->activityLogger->log(ActivityLogDocument::ACTION_ACTIVITY);
        }
    }
}