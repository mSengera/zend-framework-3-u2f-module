<?php
namespace SengeraU2F\Service;

class U2fServerService {
    /** @var string  */
    private $appId;

    /** @var null|string */
    private $attestDir;

    /** @internal */
    private $FIXCERTS = array(
        '349bca1031f8c82c4ceca38b9cebf1a69df9fb3b94eed99eb3fb9aa3822d26e8',
        'dd574527df608e47ae45fbba75a2afdd5c20fd94a02419381813cd55a2a3398f',
        '1d8764f0f7cd1352df6150045c8f638e517270e8b5dda1c63ade9c2280240cae',
        'd0edc9a91a1677435a953390865d208c55b3183c6759c9b5a7ff494c322558eb',
        '6073c436dcd064a48127ddbf6032ac1a66fd59a0c24434f070d4e564c124c897',
        'ca993121846c464d666096d35f13bf44c1b05af205f9b4a1e00cf6cc10c5e511'
    );

    /** @var  \SengeraU2F\Controller\RegisterController */
    public $controller;

    /**
     * @param string $appId Application id for the running application
     * @param string|null $attestDir Directory where trusted attestation roots may be found
     * @throws Error If OpenSSL older than 1.0.0 is used
     */
    public function init($appId, $attestDir = null) {
        if(OPENSSL_VERSION_NUMBER < 0x10000000) {
            $this->controller->addMessage('OpenSSL has to be at least version 1.0.0, this is '. OPENSSL_VERSION_TEXT);
        }

        $this->appId = $appId;
        $this->attestDir = $attestDir;
    }

    /**
     * Called to get a registration request to send to a user.
     * Returns an array of one registration request and a array of sign requests.
     *
     * @param array $registrations List of current registrations for this
     * user, to prevent the user from registering the same authenticator several
     * times.
     * @return array An array of two elements, the first containing a
     * RegisterRequest the second being an array of SignRequest
     * @throws Error
     */
    public function getRegisterData($u2fRegisterRequestService, array $registrations = array())
    {
        $challenge = $this->createChallenge();
        $u2fRegisterRequestService->init($challenge, $this->appId);
        $signs = $this->getAuthenticateData($registrations);

        return array($u2fRegisterRequestService, $signs);
    }

    /**
     * Called to verify and unpack a registration message.
     *
     * @param RegisterRequest $request this is a reply to
     * @param object $response response from a user
     * @param bool $includeCert set to true if the attestation certificate should be
     * included in the returned Registration object
     * @return Registration | Bool
     * @throws Error
     */
    public function doRegister($request, $response, $u2fRegistrationService, $includeCert = true)
    {
        if( !is_object( $request ) ) {
            return false;
        }

        if( !is_object( $response ) ) {
            return false;
        }

        if( property_exists( $response, 'errorCode') && $response->errorCode !== 0 ) {
            return false;
        }

        if( !is_bool( $includeCert ) ) {
            return false;
        }

        $rawReg = $this->base64u_decode($response->registrationData);
        $regData = array_values(unpack('C*', $rawReg));
        $clientData = $this->base64u_decode($response->clientData);
        $cli = json_decode($clientData);

        if($cli->challenge !== $request->challenge) {
            $this->controller->addMessage('Registration challenge does not match.');
            return false;
        }

        $registration = $u2fRegistrationService;
        $offs = 1;
        $pubKey = substr($rawReg, $offs, PUBKEY_LEN);
        $offs += PUBKEY_LEN;

        // decode the pubKey to make sure it's good
        $tmpKey = $this->pubkey_to_pem($pubKey);

        if($tmpKey === null) {
            $this->controller->addMessage('Decoding of public key failed');
            return false;
        }

        $registration->publicKey = base64_encode($pubKey);
        $khLen = $regData[$offs++];
        $kh = substr($rawReg, $offs, $khLen);
        $offs += $khLen;
        $registration->keyHandle = $this->base64u_encode($kh);

        // length of certificate is stored in byte 3 and 4 (excluding the first 4 bytes)
        $certLen = 4;
        $certLen += ($regData[$offs + 2] << 8);
        $certLen += $regData[$offs + 3];

        $rawCert = $this->fixSignatureUnusedBits(substr($rawReg, $offs, $certLen));
        $offs += $certLen;
        $pemCert  = "-----BEGIN CERTIFICATE-----\r\n";
        $pemCert .= chunk_split(base64_encode($rawCert), 64);
        $pemCert .= "-----END CERTIFICATE-----";

        if($includeCert) {
            $registration->certificate = base64_encode($rawCert);
        }

        if($this->attestDir) {
            if(openssl_x509_checkpurpose($pemCert, -1, $this->get_certs()) !== true) {
                $this->controller->addMessage('Attestation certificate can not be validated');
                return false;
            }
        }

        if(!openssl_pkey_get_public($pemCert)) {
            $this->controller->addMessage('Decoding of public key failed');
            return false;
        }

        $signature = substr($rawReg, $offs);

        $dataToVerify  = chr(0);
        $dataToVerify .= hash('sha256', $request->appId, true);
        $dataToVerify .= hash('sha256', $clientData, true);
        $dataToVerify .= $kh;
        $dataToVerify .= $pubKey;

        if(openssl_verify($dataToVerify, $signature, $pemCert, 'sha256') === 1) {
            return $registration;
        } else {
            $this->controller->addMessage('Attestation signature does not match');
            return false;
        }
    }

    /**
     * Called to get an authentication request.
     *
     * @param array $registrations An array of the registrations to create authentication requests for.
     * @return array An array of SignRequest | Bool
     * @throws Error
     */
    public function getAuthenticateData(array $registrations)
    {
        $sigs = array();
        $challenge = $this->createChallenge();
        foreach ($registrations as $reg) {
            if( !is_object( $reg ) ) {
                return false;
            }

            $sig = new SignRequest();
            $sig->appId = $this->appId;
            $sig->keyHandle = $reg->keyHandle;
            $sig->challenge = $challenge;
            $sigs[] = $sig;
        }

        return $sigs;
    }

    /**
     * Called to verify an authentication response
     *
     * @param array $requests An array of outstanding authentication requests
     * @param array $registrations An array of current registrations
     * @param object $response A response from the authenticator
     *
     * The Registration object returned on success contains an updated counter
     * that should be saved for future authentications.
     * If the Error returned is ERR_COUNTER_TOO_LOW this is an indication of
     * token cloning or similar and appropriate action should be taken.
     */
    public function doAuthenticate(array $requests, array $registrations, $response)
    {
        if( !is_object( $response ) ) {
            return false;
        }

        if( property_exists( $response, 'errorCode') && $response->errorCode !== 0 ) {
            return false;
        }

        /** @var object|null $req */
        $req = null;

        /** @var object|null $reg */
        $reg = null;

        $clientData = $this->base64u_decode($response->clientData);
        $decodedClient = json_decode($clientData);

        foreach ($requests as $req) {
            if( !is_object( $req ) ) {
                return false;
            }

            if($req->keyHandle === $response->keyHandle && $req->challenge === $decodedClient->challenge) {
                break;
            }

            $req = null;
        }

        if($req === null) {
            return false;
        }

        foreach ($registrations as $reg) {
            if( !is_object( $reg ) ) {
                return false;
            }

            if($reg->keyHandle === $response->keyHandle) {
                break;
            }
            $reg = null;
        }
        if($reg === null) {
            return false;
        }
        $pemKey = $this->pubkey_to_pem($this->base64u_decode($reg->publicKey));
        if($pemKey === null) {
            return false;
        }

        $signData = $this->base64u_decode($response->signatureData);
        $dataToVerify  = hash('sha256', $req->appId, true);
        $dataToVerify .= substr($signData, 0, 5);
        $dataToVerify .= hash('sha256', $clientData, true);
        $signature = substr($signData, 5);

        if(openssl_verify($dataToVerify, $signature, $pemKey, 'sha256') === 1) {
            $ctr = unpack("Nctr", substr($signData, 1, 4));
            $counter = $ctr['ctr'];

            if($counter > $reg->counter) {
                $reg->counter = $counter;
                return $reg;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    private function get_certs()
    {
        $files = array();
        $dir = $this->attestDir;
        if($dir && $handle = opendir($dir)) {
            while(false !== ($entry = readdir($handle))) {
                if(is_file("$dir/$entry")) {
                    $files[] = "$dir/$entry";
                }
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     * @param string $data
     * @return string
     */
    private function base64u_encode($data)
    {
        return trim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param string $data
     * @return string
     */
    private function base64u_decode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * @param string $key
     * @return null|string
     */
    private function pubkey_to_pem($key)
    {
        if(strlen($key) !== PUBKEY_LEN || $key[0] !== "\x04") {
            return null;
        }

        /*
         * Convert the public key to binary DER format first
         * Using the ECC SubjectPublicKeyInfo OIDs from RFC 5480
         *
         *  SEQUENCE(2 elem)                        30 59
         *   SEQUENCE(2 elem)                       30 13
         *    OID1.2.840.10045.2.1 (id-ecPublicKey) 06 07 2a 86 48 ce 3d 02 01
         *    OID1.2.840.10045.3.1.7 (secp256r1)    06 08 2a 86 48 ce 3d 03 01 07
         *   BIT STRING(520 bit)                    03 42 ..key..
         */
        $der  = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01";
        $der .= "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42";
        $der .= "\0".$key;

        $pem  = "-----BEGIN PUBLIC KEY-----\r\n";
        $pem .= chunk_split(base64_encode($der), 64);
        $pem .= "-----END PUBLIC KEY-----";

        return $pem;
    }

    /**
     * @return string
     * @throws Error
     */
    private function createChallenge()
    {
        $challenge = openssl_random_pseudo_bytes(32, $crypto_strong );
        if( $crypto_strong !== true ) {
            return false;
        }

        $challenge = $this->base64u_encode( $challenge );

        return $challenge;
    }

    /**
     * Fixes a certificate where the signature contains unused bits.
     *
     * @param string $cert
     * @return mixed
     */
    private function fixSignatureUnusedBits($cert)
    {
        if(in_array(hash('sha256', $cert), $this->FIXCERTS)) {
            $cert[strlen($cert) - 257] = "\0";
        }
        return $cert;
    }
}