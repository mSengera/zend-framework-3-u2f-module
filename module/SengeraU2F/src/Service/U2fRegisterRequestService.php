<?php
namespace SengeraU2F\Service;

class U2fRegisterRequestService {
    /** Protocol version */
    public $version = U2F_VERSION;

    /** Registration challenge */
    public $challenge;

    /** Application id */
    public $appId;

    public function init($challenge, $appId) {
        $this->challenge = $challenge;
        $this->appId = $appId;
    }
}