<?php
namespace SengeraU2F\Controller;

use SengeraU2F\Entity\User;
use SengeraU2F\Form\LoginForm;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\ServiceManager\ServiceManager;
use Zend\Validator;

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
    public function indexAction()
    {
        $form = new LoginForm();

        return new ViewModel([
            'form' => $form,
            'messages' => $this->flashMessenger()->getMessages()
        ]);
    }

    /**
     * @return ViewModel
     */
    public function u2fAction() {
        if($this->getRequest()->isPost()) {
            $validator = new Validator\EmailAddress();
            $data = $this->params()->fromPost();

            if(!$validator->isValid($data['email']) || $data['email'] == '') {
                $this->flashMessenger()->addMessage('Wrong login credentials. Please try again.');
                return $this->redirect()->toRoute('login-normal');
            }

            $entityManager = $this->getServiceManager()->get('doctrine.entitymanager.orm_default');

            $user = $entityManager->getRepository(User::class)->findByUsername($data['email']);

            if($data['password'] != $user[0]->getPassword()) {
                $this->flashMessenger()->addMessage('Wrong login credentials. Please try again.');
                return $this->redirect()->toRoute('login-normal');
            }

            $sessionContainer = $this->getServiceManager()->get('user_session');

            $sessionContainer->logged_in = true;
            $sessionContainer->username = $user[0]->getUsername();
            $sessionContainer->publicKey = $user[0]->getPublicKey();
            $sessionContainer->keyhandle = $user[0]->getKeyhandle();
            $sessionContainer->certificate = $user[0]->getCertificate();
            $sessionContainer->counter = $user[0]->getCounter();

            $this->u2fServerService = $this->getServiceManager()->get('U2fServer');
            $this->u2fServerService->init('https://localhost');

            $data = $this->u2fServerService->getRegisterData($this->getServiceManager()->get('U2fRegisterRequest'));

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

            echo $counter->counter;

            /*
             * Hurray Logged In!
             */
            echo 'Perfect! You are now logged in.';

            return $this->getResponse();
        }

        return $this->redirect()->toRoute('login-normal');
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