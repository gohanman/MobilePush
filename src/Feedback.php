<?php

namespace gohanman\MobilePush;

use ZendService\Apple\Apns\Client\Feedback as Client;
use ZendService\Apple\Apns\Response\Feedback as Response;
use ZendService\Apple\Apns\Exception\RuntimeException;
use \Exception;

/**
  IOS only.
  Poll feedback channel. Sends device tokens for users
  than have disabled push notifications
*/
class Feedback
{
    const DEV_MODE = 1;
    const PROD_MODE = 2;

    private $client;

    public function __construct($mode)
    {
        switch ($mode) {
            case self::PROD_MODE:
                $this->client = new ApnsClient(ApnsClient::PRODUCTION_URI, __DIR__ . '/../credentials/ios.prod.combined.pem');    
                break;
            case self::DEV_MODE:
                $this->client = new ApnsClient(ApnsClient::SANDBOX_URI, __DIR__ . '/../credentials/dev.prod.combined.pem');    
            default:
                throw new Exception("Unknown mode {$mode}");
                break;
        }
    }

    public function get()
    {
        $responses = $this->client->feedback();
        $this->client->close();
        $ret = array();
        foreach ($responses as $r) {
            $ret[] = new DeviceToken($r->getToken(), DeviceToken::TYPE_IOS);
        }

        return $ret;
    }
}

