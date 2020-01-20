<?php

namespace LogBundle\Document;

use MongoDB\BSON\Persistable;
use MongoDB\BSON\UTCDateTime;
use JMS\Serializer\Annotation as JMS;

class LogDocument implements Persistable
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     * @JMS\Type("array")
     */
    private $details;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var \DateTime
     *
     * @JMS\Type("DateTime<'n/d/Y g:i:s a T'>")
     */
    private $timestamp;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var string
     */
    private $userRole;

    /**
     * @var string
     */
    private $userName;

    /**
     * @var integer
     */
    private $impersonated_user_id;

    /**
     * @var string
     */
    private $impersonated_user_role;

    /**
     * @var string
     */
    private $impersonated_user_name;

    /**
     * @var string
     */
    private $logTransactionId;

    public function __construct()
    {
        $this->timestamp = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->details   = [];
    }

    public function bsonSerialize()
    {
        return [
            'identifier'             => $this->identifier,
            'type'                   => $this->type,
            'message'                => $this->message,
            'details'                => $this->details,
            'ip'                     => $this->ip,
            'uri'                    => $this->uri,
            'timestamp'              => new UTCDateTime($this->timestamp),
            'user_id'                => $this->userId,
            'user_role'              => $this->userRole,
            'user_name'              => $this->userName,
            'impersonated_user_id'   => $this->impersonated_user_id,
            'impersonated_user_role' => $this->impersonated_user_role,
            'impersonated_user_name' => $this->impersonated_user_name,
            'log_transaction_id'     => $this->logTransactionId,
        ];
    }

    public function bsonUnserialize(array $data)
    {
        $this->identifier = $data['identifier'];
        $this->type       = $data['type'];
        $this->message    = $data['message'];
        $this->details    = $data['details'];
        $this->ip         = $data['ip'];
        $this->uri        = $data['uri'];
        /** @var UTCDateTime $timestamp */
        $timestamp                    = $data['timestamp'];
        $this->timestamp              = $timestamp->toDateTime();
        $this->userId                 = $data['user_id'];
        $this->userRole               = $data['user_role'];
        $this->userName               = $data['user_name'];
        $this->impersonated_user_id   = $data['impersonated_user_id'];
        $this->impersonated_user_role = $data['impersonated_user_role'];
        $this->impersonated_user_name = $data['impersonated_user_name'];
        $this->logTransactionId       = $data['log_transaction_id'];
    }

    /**
     * @param string $logTransactionId
     * @param string $id
     * @param string $type
     * @param string $msg
     * @param array $details
     * @return LogDocument
     */
    public static function createInstance($logTransactionId, $id, $type, $msg, array $details = [])
    {
        $instance = new self();

        $instance
            ->setLogTransactionId($logTransactionId)
            ->setIdentifier($id)
            ->setType($type)
            ->setMessage($msg)
            ->setDetails($details);


        return $instance;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return LogDocument
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return LogDocument
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return LogDocument
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param array $details
     * @return LogDocument
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    public function mergeDetails($details)
    {
        $this->details = array_merge($this->details, $details);
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return LogDocument
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     * @return LogDocument
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return \DateTime
     *
     * @JMS\VirtualProperty()
     * @JMS\SerializedName("timestamp_pst")
     * @JMS\Type("DateTime<'n/d/Y g:i:s a T'>")
     */
    public function getTimestampPST()
    {
        $date = clone $this->timestamp;
        $date->setTimezone(new \DateTimeZone('America/Los_Angeles'));

        return $date;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return LogDocument
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserRole()
    {
        return $this->userRole;
    }

    /**
     * @param string $userRole
     * @return LogDocument
     */
    public function setUserRole($userRole)
    {
        $this->userRole = $userRole;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return LogDocument
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * @return int
     */
    public function getImpersonatedUserId()
    {
        return $this->impersonated_user_id;
    }

    /**
     * @param int $impersonated_user_id
     */
    public function setImpersonatedUserId($impersonated_user_id)
    {
        $this->impersonated_user_id = $impersonated_user_id;
    }

    /**
     * @return string
     */
    public function getImpersonatedUserRole()
    {
        return $this->impersonated_user_role;
    }

    /**
     * @param string $impersonated_user_role
     */
    public function setImpersonatedUserRole($impersonated_user_role)
    {
        $this->impersonated_user_role = $impersonated_user_role;
    }

    /**
     * @return string
     */
    public function getImpersonatedUserName()
    {
        return $this->impersonated_user_name;
    }

    /**
     * @param string $impersonated_user_name
     * @return LogDocument
     */
    public function setImpersonatedUserName($impersonated_user_name)
    {
        $this->impersonated_user_name = $impersonated_user_name;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogTransactionId()
    {
        return $this->logTransactionId;
    }

    /**
     * @param string $logTransactionId
     * @return LogDocument
     */
    public function setLogTransactionId($logTransactionId)
    {
        $this->logTransactionId = $logTransactionId;

        return $this;
    }
}