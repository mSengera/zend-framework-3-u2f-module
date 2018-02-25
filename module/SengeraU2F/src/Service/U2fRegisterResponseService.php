<?php
namespace SengeraU2F\Service;

class U2fRegisterResponseService {
    var $errorCode;
    var $registrationData;
    var $clientData;

    public function init($registrationData, $clientData, $errorCode) {
        $this->registrationData = $registrationData;
        $this->clientData = $clientData;

        if($errorCode == '') {
            $this->errorCode = 0;
        } else {
            $this->errorCode = $errorCode;
        }
    }
}