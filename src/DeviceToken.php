<?php

namespace gohanman\MobilePush;

/**
  Contains a platform-specific device identifier
*/
class DeviceToken
{
    const TYPE_IOS = 1;
    const TYPE_ANDROID = 2;

    private $value;
    private $type;

    public function __construct($token, $type)
    {
        $this->value = $token;
        $this->type = type;
    }

    public function get()
    {
        return $this->value;
    }

    public function isIOS()
    {
        return ($this->type === self::TYPE_IOS);
    }

    public function isAndroid()
    {
        return ($this->type === self::TYPE_ANDROID);
    }
}

