<?php
namespace SengeraU2F\Service;

class U2fSignRequestService {
    /** Protocol version */
    public $version = U2F_VERSION;

    /** Authentication challenge */
    public $challenge;

    /** Key handle of a registered authenticator */
    public $keyHandle;

    /** Application id */
    public $appId;
}