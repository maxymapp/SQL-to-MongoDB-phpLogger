<?php

namespace LogBundle\Document;

use MongoDB\BSON\Persistable;
use MongoDB\BSON\UTCDateTime;

class ActivityLogDocument implements Persistable
{
    const DB         = 'maksym';
    const COLLECTION = 'user_activity_log';

    const ACTION_LOGIN    = 'login';
    const ACTION_LOGOUT   = 'logout';
    const ACTION_ACTIVITY = 'activity';

    /**
     * @var string
     */
    private $user_id;

    /**
     * @var string
     */
    private $user_type;

    /**
     * @var string
     */
    private $impersonated_user_id;

    /**
     * @var string
     */
    private $impersonated_user_type;

    /**
     * @var string
     */
    private $action;

    /**
     * @var UTCDateTime
     */
    private $timestamp;

    /**
     * @param string $action
     * @param \stdClass $userData
     * @return ActivityLogDocument
     */
    public static function generateLogRecord($action, \stdClass $userData)
    {
        $instance = new self();
        $instance
            ->setAction($action)
            ->setUserId($userData->user_id)
            ->setUserType($userData->user_type)
            ->setImpersonatedUserId($userData->impersonated_user_id)
            ->setImpersonatedUserType($userData->impersonated_user_type);

        return $instance;
    }

    public function __construct()
    {
        $this->timestamp = new UTCDateTime();
    }

    public function bsonSerialize()
    {
        return [
            'user_id'       => $this->user_id,
            'user_type'     => $this->user_type,
            'posed_as'      => $this->impersonated_user_id,
            'posed_as_type' => $this->impersonated_user_type,
            'action'        => $this->action,
            'timestamp'     => $this->timestamp,
        ];
    }

    public function bsonUnserialize(array $data)
    {
        $this->user_id                = $data['user_id'];
        $this->user_type              = $data['user_type'];
        $this->impersonated_user_id   = $data['posed_as'];
        $this->impersonated_user_type = $data['posed_as_type'];
        $this->action                 = $data['action'];
        $this->timestamp              = $data['timestamp'];
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param string $user_id
     * @return ActivityLogDocument
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserType()
    {
        return $this->user_type;
    }

    /**
     * @param string $user_type
     * @return ActivityLogDocument
     */
    public function setUserType($user_type)
    {
        $this->user_type = $user_type;

        return $this;
    }

    /**
     * @return string
     */
    public function getImpersonatedUserId()
    {
        return $this->impersonated_user_id;
    }

    /**
     * @param string $impersonated_user_id
     * @return ActivityLogDocument
     */
    public function setImpersonatedUserId($impersonated_user_id)
    {
        $this->impersonated_user_id = $impersonated_user_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getImpersonatedUserType()
    {
        return $this->impersonated_user_type;
    }

    /**
     * @param string $impersonated_user_type
     * @return ActivityLogDocument
     */
    public function setImpersonatedUserType($impersonated_user_type)
    {
        $this->impersonated_user_type = $impersonated_user_type;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return ActivityLogDocument
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return UTCDateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}