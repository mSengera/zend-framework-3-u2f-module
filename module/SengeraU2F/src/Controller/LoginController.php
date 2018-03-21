<?php
namespace SengeraU2F\Controller;

use SengeraU2F\Entity\User;
use SengeraU2F\Form\LoginForm;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\ServiceManager\ServiceManager;
use Zend\Validator;
use Zend\Escaper\Escaper;
use Zend\Crypt\Password\Bcrypt;

class LoginController extends AbstractActionController {

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var \SengeraU2F\Service\U2fServerService
     */
    public $u2fServerService;

    /**
     * @return ViewModel
     */
    public function indexAction() {
        $this->_redirectUserIfLoggedIn();

        // CSRF Protection
        $csrfToken = bin2hex(random_bytes(32));

        $sessionContainer = $this->getServiceManager()->get('user_session');
        $sessionContainer->csrfToken = $csrfToken;

        $form = new LoginForm($csrfToken);

        return new ViewModel([
            'form' => $form,
            'messages' => $this->flashMessenger()->getMessages()
        ]);
    }

    /**
     * @return ViewModel
     */
    public function u2fAction() {
        $this->_redirectUserIfLoggedIn();

        if($this->getRequest()->isPost()) {
            $validator = new Validator\EmailAddress();
            $data = $this->params()->fromPost();
            $sessionContainer = $this->getServiceManager()->get('user_session');

            // Check CSRF Token
            if($data['token'] != $sessionContainer->csrfToken) {
                $this->flashMessenger()->addMessage('Wrong session key. Please reload and try again.');
                return $this->redirect()->toRoute('login-normal');
            }

            if(!$validator->isValid($data['email']) || $data['email'] == '') {
                $this->flashMessenger()->addMessage('Wrong login credentials. Please try again.');
                return $this->redirect()->toRoute('login-normal');
            }

            $entityManager = $this->getServiceManager()->get('doctrine.entitymanager.orm_default');

            $user = $entityManager->getRepository(User::class)->findByUsername($data['email']);

            $bcrypt = new Bcrypt();
            $securePass = $user[0]->getPassword();
            $password = $data['password'];

            if (!$bcrypt->verify($password, $securePass)) {
                $this->flashMessenger()->addMessage('Wrong login credentials. Please try again.');
                return $this->redirect()->toRoute('login-normal');
            }

            $sessionContainer->username = $user[0]->getUsername();
            $sessionContainer->publicKey = $user[0]->getPublicKey();
            $sessionContainer->keyhandle = $user[0]->getKeyhandle();
            $sessionContainer->certificate = $user[0]->getCertificate();
            $sessionContainer->counter = $user[0]->getCounter();

            $this->u2fServerService = $this->getServiceManager()->get('U2fServer');
            $this->u2fServerService->init('https://localhost');

            $data = $this->u2fServerService->getRegisterData($this->getServiceManager()->get('U2fRegisterRequest'));

            /*
             * Escape all data for safe output
             */
            $escaper = new Escaper();

            // Escape $sessionContainer->keyhandle variable
            $sessionContainer->keyhandle = $escaper->escapeJs($sessionContainer->keyhandle);

            $view = new ViewModel([
                'data' => $data,
                'keyhandle' => $sessionContainer->keyhandle
            ]);

            return $view;
        }

        return $this->redirect()->toRoute('login-normal');
    }

    /**
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function doAction() {
        if($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();

            if(isset($data['errorCode'])) {
                $this->flashMessenger()->addMessage('Something with your token went wront. Please try again with right token.');
                echo '<script>window.location.replace("/fido-to-zend/public/login");</script>';
                return $this->getResponse();
            }

            $this->u2fServerService = $this->getServiceManager()->get('U2fServer');
            $this->u2fServerService->init('https://localhost');

            $sessionContainer = $this->getServiceManager()->get('user_session');

            $registrationService = $this->getServiceManager()->get('U2fRegistration');
            $registrationService->keyHandle = $data['keyHandle'];
            $registrationService->publicKey = $sessionContainer->publicKey;
            $registrationService->certificate = $sessionContainer->certificate;
            $registrationService->counter = $sessionContainer->counter;

            $signRequestService = $this->getServiceManager()->get('U2fSignRequest');
            $signRequestService->keyHandle = $data['keyHandle'];
            $signRequestService->challenge = $data['challenge'];
            $signRequestService->appId = $data['appId'];

            $loginResponseService = $this->getServiceManager()->get('U2fLoginResponse');
            $loginResponseService->init($data['clientData'], $data['keyHandle'], $data['signatureData'], $data['errorCode']);

            if(!$counter = $this->u2fServerService->doAuthenticate(array($signRequestService), array($registrationService), $loginResponseService)) {
                $this->flashMessenger()->addMessage('Something with your token went wront. Please try again with right token.');
                return $this->redirect()->toRoute('login-normal');
            }

            if($counter->counter <= $sessionContainer->counter) {
                $this->flashMessenger()->addMessage('Something with your token went wront. Your token isnt longer safe. It was duplicated. Please contact the support.');
                return $this->redirect()->toRoute('login-normal');
            }

            /*
             * Hurray Logged In!
             */
            $sessionContainer->logged_in = true;
            echo '<script>window.location.replace("/fido-to-zend/public/dashboard");</script>';

            return $this->getResponse();
        }

        return $this->redirect()->toRoute('login-normal');
    }

    public function logoutAction() {
        $sessionContainer = $this->getServiceManager()->get('user_session');
        $sessionContainer->exchangeArray(array());

        $this->flashMessenger()->addMessage('Logged out successfully.');
        return $this->redirect()->toRoute('home');
    }

    private function _redirectUserIfLoggedIn() {
        $sessionContainer = $this->getServiceManager()->get('user_session');

        if($sessionContainer->logged_in != true) {
            return true;
        } else {
            return $this->redirect()->toRoute('dashboard');
        }
    }

    /* =================================================================================================================
     * Getter and setter methods =======================================================================================
     * ===============================================================================================================*/

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }
    /**
     * @param mixed $serviceManager
     * @return $this
     */
    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }
}