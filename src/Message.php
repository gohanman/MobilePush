<?php

namespace gohanman\MobilePush;

use ZendService\Apple\Apns\Client\Message as ApnsClient;
use ZendService\Apple\Apns\Message as ApnsMessage;
use ZendService\Apple\Apns\Message\Alert as ApnsAlert;
use ZendService\Apple\Apns\Response\Message as ApnsResponse;
use ZendService\Apple\Apns\Exception\RuntimeException as ApnsException;

use ZendService\Google\Gcm\Client as GcmClient;
use ZendService\Google\Gcm\Message as GcmMessage;
use ZendService\Google\Exception\RuntimeException as GcmException;

use \Exception;

/**
  Send one message to devices on multiple platforms
*/
class Message
{
    private $ios_client = null;
    private $android_client = null;
    private $android_msg = null;
    
    private $message_string = '';
    private $badge = 0;
    private $msg_id;

    const DEV_MODE = 1;
    const PROD_MODE = 2;

    /**
      Constructor
      @param $mode [constant] dev or production
      @param $message [string] body of notification message
    */
    public function __construct($mode, $message)
    {
        $this->message_string = $message;
        switch ($mode) {
            case self::PROD_MODE:
                $this->ios_client = new ApnsClient(ApnsClient::PRODUCTION_URI, __DIR__ . '/../credentials/ios.prod.combined.pem');    
                $this->android_client = new GcmClient();
                $this->android_client->setApiKey(file_get_contents(__DIR__ . '/../credentials/gcm.api.key'));
                $this->android_msg = new GcmMessage();
                $this->android_msg->setCollapseKey($message);
                $this->android_msg->setData(array('msg'=>$message));
                break;
            case self::DEV_MODE:
                $this->ios_client = new ApnsClient(ApnsClient::SANDBOX_URI, __DIR__ . '/../credentials/dev.prod.combined.pem');    
                $this->android_client = new GcmClient();
                $this->android_client->setApiKey(file_get_contents(__DIR__ . '/../credentials/gcm.api.key'));
                $this->android_msg = new GcmMessage();
                $this->android_msg->setCollapseKey($message);
                $this->android_msg->setData(array('msg'=>$message));
                break;
            default:
                throw new Exception("Unknown mode {$mode}");
                break;
        }
    }

    /**
      IOS only
      Display number $b by app icon
    */
    public function setBadge($b)
    {
        $this->badge = $b;
    }

    /**
      @param $m [string] body of notification message
    */
    public function setMessage($m)
    {
        $this->message_string = $m;
        $this->android_msg->setCollapseKey($m);
        $this->android_msg->setData(array('msg'=>$m));
    }

    /**
      Send the message
      @param $tokens [array of DeviceToken objects]
    */
    public function send(array $tokens)
    {
        foreach ($tokens as $t) {
            if ($t->isIOS()) {
                $this->sendIOS($t);
            } elseif ($t->isAndroid()) {
                $this->sendAndroid($t);
            }
        }
        $this->flushAndroid();
    }

    /**
      Send message to IOS device identified by token
    */
    private function sendIOS(DeviceToken $token)
    {
        $msg = new ApnsMessage();
        $msg->setId(uniqid());
        $msg->setToken($token->get());
        if ($this->badge && is_numeric($this->badge)) {
            $msg->setBadge($this->badge);
        }
        try {
            return $this->ios_client->send($msg);
        } catch (ApnsException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return false;
    }

    /**
      Send (queue) message for Android device identified by token
    */
    private function sendAndroid(DeviceToken $token)
    {
        $this->android_msg->addRegistrationId($token->get());
        if (count($this->android_msg->getRegistrationIds()) >= 100) {
            $this->flushAndroid();
        }
    }

    /**
      Send queued android messages
      (Can include 100 devices per send/push)
    */
    private function flushAndroid()
    {
        if (count($this->android_msg->getRegistrationIds()) > 0) {
            try {
                $this->android_client->send($this->android_msg);
            } catch (GcmException $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        }
        $this->android_msg->clearRegistrationIds();
    }
}

