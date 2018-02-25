<?php
namespace SengeraU2F\Service;

class U2fLoginResponseService {
    var $errorCode;
    var $clientData;
    var $keyHandle;
    var $signatureData;

    public function init($clientData, $keyHandle, $signatureData, $errorCode) {
        $this->clientData = $clientData;
        $this->keyHandle = $keyHandle;
        $this->signatureData = $signatureData;

        if($errorCode == '') {
            $this->errorCode = 0;
        } else {
            $this->errorCode = $errorCode;
        }
    }
}