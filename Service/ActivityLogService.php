<?php

namespace LogBundle\Service;

use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Doctrine\UserManager;
use CoreBundle\Lib\StaticResources;
use CoreBundle\Services\MongoConnector;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Cursor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use LogBundle\Document\ActivityLogDocument;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;

class ActivityLogService
{
    /** @var \MongoCollection $collection */
    private $collection;
    /** @var AuthorizationChecker $authChecker */
    private $authChecker;
    /** @var TokenStorageInterface $tokenStorage */
    private $tokenStorage;
    /** @var RoleHierarchyInterface $roleHierarchy */
    private $roleHierarchy;
    /** @var UserManager $userManager */
    private $userManager;
    /** @var  EntityManager */
    private $em;
    /** @var string */
    private $logsPath;

    public function __construct(
        MongoConnector $mongo_connector,
        AuthorizationChecker $authChecker,
        TokenStorageInterface $tokenStorage,
        RoleHierarchyInterface $roleHierarchy,
        UserManager $userManager,
        EntityManager $em,
        $logsPath
    ) {
        $this->collection    = $mongo_connector->getCollection(ActivityLogDocument::DB,
            ActivityLogDocument::COLLECTION);
        $this->authChecker   = $authChecker;
        $this->tokenStorage  = $tokenStorage;
        $this->roleHierarchy = $roleHierarchy;
        $this->userManager   = $userManager;
        $this->em            = $em;
        $this->logsPath      = $logsPath;
    }

    public function log($action)
    {
        $userData                         = new \stdClass();
        $userData->user_id                = null;
        $userData->user_type              = 'anonymous';
        $userData->impersonated_user_id   = null;
        $userData->impersonated_user_type = null;

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

                        $userData->user_id   = $impersonator->getId();
                        $userData->user_type = $impersonator->getUserType();

                        break;
                    }
                }

                $userData->impersonated_user_id   = $token->getUser()->getId();
                $userData->impersonated_user_type = $token->getUser()->getUserType();
            } else {
                /** @var User $user */
                $user = $this->userManager->findUserBy([
                    'id' => $token->getUser()->getId(),
                ]);

                $userData->user_id   = $user->getId();
                $userData->user_type = $user->getUserType();
            }
        }

        /** @var ActivityLogDocument $logRecord */
        $logRecord = ActivityLogDocument::generateLogRecord($action, $userData);
        $this->collection->insertOne($logRecord);
    }

    /**
     * Returns an array of ids of users filtered by user type, active within the last {$period} minutes.
     *
     * @param string $userType
     * @param integer $period
     * @return array
     */
    public function getActiveUserIdsByType($userType, $period = 5)
    {
        $inactiveUsers = [];
        $activeUsers   = [];
        if (gettype($userType) === 'string' && array_key_exists($userType, StaticResources::getUserRoles())) {
            /** @var Cursor $activityRecords */
            $activityRecords = $this->collection->aggregate([
                [
                    '$sort' => ['timestamp' => -1],
                ],
                [
                    '$match' => [
                        'user_type' => $userType,
                        'timestamp' => [
                            '$gt' => new UTCDateTime((time() - intval($period) * 60) * 1000),
                        ],
                    ],
                ],
                [
                    '$group' => [
                        '_id'    => '$_id',
                        'result' => ['$first' => '$$ROOT'],
                    ],
                ],
                [
                    '$sort' => ['result.timestamp' => -1],
                ],
            ]);

            foreach ($activityRecords as $value) {
                /** @var ActivityLogDocument $record */
                $record = $value['result'];

                if (
                    array_key_exists($record->getUserId(), $inactiveUsers) ||
                    array_key_exists($record->getUserId(), $activeUsers)
                ) {
                    continue;
                }

                if ($record->getAction() === ActivityLogDocument::ACTION_LOGOUT) {
                    $inactiveUsers[$record->getUserId()] = $record->getUserId();
                    continue;
                }

                if (!array_key_exists($record->getUserId(), $activeUsers)) {
                    $activeUsers[$record->getUserId()] = $record->getUserId();
                }
            }
        }

        return $activeUsers;
    }


    public function getCoordinatingLogsByPeriod(
        \DateTimeImmutable $dateStart,
        \DateTimeImmutable $dateEnd,
        $abbrName = false
    ) {
        $dateArray  = $this->getArrayDate($dateStart, $dateEnd);
        $logContent = [];
        $userIdList = [];

        /** @var User[] $coordinators */
        $coordinators = $this->em->getRepository(User::class)->findAllCoordinators()->getResult();
        /** @var User[] $administrator */
        $administrator = $this->em->getRepository(User::class)->findAllAdmins()->getResult();

        $listCoordinator = [];
        foreach ($coordinators as $coordinator) {
            $listCoordinator[] = $coordinator->getId();
            $userIdList[]      = $coordinator->getId();
        }

        foreach ($administrator as $admin) {
            $listCoordinator[] = $admin->getId();
            $userIdList[]      = $admin->getId();
        }

        $finder = new Finder();
        foreach ($dateArray as $year => $dateList) {
            $dir = $this->logsPath . '/' . $year . '/';
            $finder->files()->in($dir);
            foreach ($finder as $file) {
                $handle = fopen($file->getRealpath(), 'r');

                if ($handle) {
                    while (($buffer = fgets($handle)) !== false) {
                        $new = explode("]:[", $buffer);
                        foreach ($new as $key => $value) {
                            $new[$key] = str_replace(["[", "]"], "", $value);
                        }
                        $date = \DateTime::createFromFormat('Y-d-m H:i:s', $new[0]);
                        if (in_array($date->format("Y-d-m"), $dateList)) {

                            $description = strpos($new[5], "Awaiting");
                            $userId      = $new[3];
                            if (!key_exists($userId, $logContent)) {
                                $logContent[$userId] = [
                                    "upload"   => 0,
                                    "awaiting" => 0,
                                    "unique"   => 0,
                                    "count"    => 0,
                                    "order"    => [],
                                ];
                            }
                            $url = $new[4];
                            if (!empty($userId)) {
                                $newUrl = explode("/", $url);
                                if (isset($newUrl[4])) {
                                    if ($newUrl[4] == 'upload') {
                                        $logContent[$userId]['upload'] += 1;
                                    } elseif ($newUrl[4] == 'edit') {
                                        if (!in_array($newUrl[5], $logContent[$userId]['order'])) {
                                            array_push($logContent[$userId]['order'], $newUrl[5]);
                                            $logContent[$userId]['unique'] += 1;
                                            $logContent[$userId]['count']  += 1;
                                        } else {
                                            $logContent[$userId]['count'] += 1;

                                        }
                                        if ($description !== false) {
                                            $logContent[$userId]['awaiting'] += 1;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!feof($handle)) {
                        throw new \RuntimeException('Error: unexpected fgets() fail when reading log file.');
                    }
                    fclose($handle);
                }
            }
        }

        /** @var User[] $users */
        $users = $this->em->getRepository('UserBundle:User')->findAllInList($userIdList);
        if (null === $users) {
            throw new \RuntimeException('No users were found.');
        }

        $records     = [];
        $excludeUsers = [
            659,
            660,
            1256,
            1733,
        ];
        foreach ($users as $user) {
            if (!in_array($user->getId(), $excludeUsers)) {
                foreach ($logContent as $key => &$line) {
                    if ($key == $user->getId()) {
                        if ($user->getCurrentRole() == 'ROLE_CLIENT') {
                            $line['userName'] = $user->getCompany();
                        } else {
                            $firstName        = $user->getFirstName();
                            $lastName         = $abbrName ? $user->getLastName()[0] : $user->getLastName();
                            $line['userName'] = $firstName . ' ' . $lastName;
                        }

                        switch ($user->getCurrentRole()) {
                            case 'ROLE_DATA_PROCESSOR' : {
                                $currentUserRole = 'Data Processor';
                                break;
                            }
                            case 'ROLE_PRODUCTION_MGR' : {
                                $currentUserRole = 'Production Mgr';
                                break;
                            }
                            case 'ROLE_ENVELOPE_MGR'   : {
                                $currentUserRole = 'Envelope Mgr';
                                break;
                            }
                            case 'ROLE_OFFSET_MGR'     : {
                                $currentUserRole = 'Offset Mgr';
                                break;
                            }
                            case 'ROLE_CLIENT'         : {
                                $currentUserRole = 'Client';
                                break;
                            }
                            case 'ROLE_SALES_REP'      : {
                                $currentUserRole = 'Sales Rep';
                                break;
                            }
                            case 'ROLE_SALES_REP_MGR'  : {
                                $currentUserRole = 'Sales Rep Mgr';
                                break;
                            }
                            case 'ROLE_COORDINATOR'    : {
                                $currentUserRole = 'Coordinator';
                                break;
                            }
                            case 'ROLE_ADMIN'          : {
                                $currentUserRole = 'Admin';
                                break;
                            }
                            case 'ROLE_SUPER_ADMIN'    : {
                                $currentUserRole = 'Super Admin';
                                break;
                            }
                            case 'ROLE_TECH_SUPPORT'   : {
                                $currentUserRole = 'Tech Support';
                                break;
                            }
                            default                    : {
                                $currentUserRole = 'unknown';
                                break;
                            }
                        }

                        $line['userRole'] = $currentUserRole;
                        if (isset($line['userName']) && ($currentUserRole == 'Coordinator' || $currentUserRole == 'Admin')) {
                            $records[$line['userName']] = $line;
                        }
                    }
                }

            }
        }

        return $records;
    }

    private function getArrayDate(\DateTimeImmutable $dateStart, \DateTimeImmutable $dateEnd)
    {
        $dates = [];
        while ($dateStart <= $dateEnd) {
            $dates[$dateStart->format("Y")][] = $dateStart->format("Y-d-m");
            $dateStart                        = $dateStart->modify("+1 day");
        }

        return $dates;
    }

}