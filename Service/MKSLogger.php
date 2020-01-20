<?php

namespace LogBundle\Service;

use CoreBundle\Services\MongoConnector;
use LogBundle\Lib\Utility;
use UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use LogBundle\Document\LogDocument;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use FOS\UserBundle\Doctrine\UserManager;
use Symfony\Component\VarDumper\VarDumper;

class MaksymLogger
{

    /** @var MongoConnector $mongoConnector */
    private $mongoConnector;

    /** @var Request|null */
    private $request;

    /** @var AuthorizationChecker $authChecker */
    private $authChecker;

    /** @var TokenStorageInterface $tokenStorage */
    private $tokenStorage;

    /** @var RoleHierarchyInterface $roleHierarchy */
    private $roleHierarchy;

    /** @var UserManager $userManager */
    private $userManager;

    /** @var MaksymLogManager $logManager */
    private $logManager;

    public function __construct(
        MongoConnector $mongoConnector,
        RequestStack $requestStack,
        AuthorizationChecker $authChecker,
        TokenStorageInterface $tokenStorage,
        RoleHierarchyInterface $roleHierarchy,
        UserManager $userManager,
        MaksymLogManager $logManager
    ) {
        $this->mongoConnector = $mongoConnector;
        $this->request        = $requestStack->getCurrentRequest();
        $this->authChecker    = $authChecker;
        $this->tokenStorage   = $tokenStorage;
        $this->roleHierarchy  = $roleHierarchy;
        $this->userManager    = $userManager;
        $this->logManager     = $logManager;
    }

    public function log(LogDocument $document)
    {
        if (!is_null($this->request)) {
            $document
                ->setIp($this->request->getClientIp())
                ->setUri($this->request->getUri());
        }

        /** @var TokenInterface $token */
        $token = $this->tokenStorage->getToken();
        if ($token instanceof UsernamePasswordToken) {
            // if impersonating
            if ($this->authChecker->isGranted('ROLE_PREVIOUS_ADMIN')) {
                foreach ($token->getRoles() as $role) {
                    if ($role instanceof SwitchUserRole) {
                        /** @var User $impersonator */
                        $impersonator = $role->getSource()->getUser();
                        $impersonator = $this->userManager->findUserBy([
                            'id' => $impersonator->getId(),
                        ]);

                        $document
                            ->setUserId($impersonator->getId())
                            ->setUserName($impersonator->getName())
                            ->setUserRole($impersonator->getCurrentRole());

                        break;
                    }
                }

                $document->setImpersonatedUserId($token->getUser()->getId());
                $document->setImpersonatedUserRole($token->getUser()->getCurrentRole());
                $document->setImpersonatedUserName($token->getUser()->isClient() ? $token->getUser()->getCompany() : $token->getUser()->getName());
            } else {
                /** @var User $user */
                $user = $this->userManager->findUserBy([
                    'id' => $token->getUser()->getId(),
                ]);

                $document
                    ->setUserId($user->getId())
                    ->setUserName($user->isClient() ? $user->getCompany() : $user->getName())
                    ->setUserRole($user->getCurrentRole());
            }
        }

        $this->logManager->add($document);
    }



    public function getLogs($id, $types)
    {
        if (!$this->isValidType($types)) {
            throw new \RuntimeException('unsupported type');
        }
        $collection = $this->mongoConnector
            ->getCollection(Utility::LOG_DATABASE, Utility::LOG_COLLECTION);

        $records = $collection->find([
            'identifier' => intval($id),
            'type'       => [
                '$in' => array_map('strval', $types),
            ],
        ], [
            'typeMap' => [
                'array' => 'array'
            ],
            'sort' => [
                '_id' => -1,
            ],
        ]);
        return $records->toArray();

    }

    public function isValidType($types)
    {
        foreach ($types as $type) {
            if (!in_array($type, Utility::LOG_DETAILS_CLASS)) {
                return false;
            }
        }

        return true;
    }

//    public function isRecordInDB($id) {
//        $collection = $this->mongoConnector->getCollection(Utility::LOG_DATABASE, Utility::LOG_COLLECTION);
//        $result = $collection->findOne(array("identifier" => $id));
////        VarDumper::dump($result);die();
//        return $result;
//    }


}