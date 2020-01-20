<?php

namespace LogBundle\Lib;


use JobsBundle\Entity\DirectMail\JobTicket;

class Utility
{
    const LOG_DATABASE   = 'maksym';
    const LOG_COLLECTION = 'general_log';

    const LOG_TYPE_GENERIC            = 'generic';
    const LOG_TYPE_DIRECT_MAIL_CREATE = 'direct_mail_create';
    const LOG_TYPE_DIRECT_MAIL_UPDATE = 'direct_mail_update';

    const LOG_DETAILS_CLASS = [
        self::LOG_TYPE_GENERIC,
        self::LOG_TYPE_DIRECT_MAIL_CREATE,
        self::LOG_TYPE_DIRECT_MAIL_UPDATE,
    ];

    public static function normalizeFields($fieldsNew)
    {
        foreach ($fieldsNew as &$fields) {
            foreach ($fields as $key => $field) {
                if (is_object($field)) {
                    if (method_exists($field, 'getLogValue')) {
                        $fields[$key] = $field->{'getLogValue'}();
                    } elseif ($field instanceof \DateTime) {
                        $fields[$key] = $field->format('m/d/Y h:i:s a T');
                    } else {
                        $fields[$key] = strval($field);
                    }

                }
            }
        }
        return $fieldsNew;
    }
//     public static function extractFields($updatedFields) {
//        if()
//     }

}