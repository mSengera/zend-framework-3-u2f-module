<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonModule for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace SengeraU2F\Controller;

use SengeraU2F\Entity\User;
use SengeraU2F\Form\RegisterForm;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\ServiceManager\ServiceManager;

use Zend\Validator;

class RegisterController extends AbstractActionController
{

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var \SengeraU2F\Service\U2fServerService
     */
    public $u2fServerService;

    /**
     * @var \SengeraU2F\Service\U2fRegisterRequestService
     */
    public $u2fRegisterRequestService;

    /**
     * @var \SengeraU2F\Service\U2fRegisterResponseService
     */
    public $u2fRegisterResponseService;

    /**
     * @return ViewModel
     */
    public function indexAction()
    {
        $form = new RegisterForm();

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
                $this->flashMessenger()->addMessage('Your emailaddress is invalid.');
                return $this->redirect()->toRoute('register-normal');
            }

            if($data['password'] != $data['repeat-password']) {
                $this->flashMessenger()->addMessage('The passwords do not match.');
                return $this->redirect()->toRoute('register-normal');
            }

            if(strlen($data['password']) < 8) {
                $this->flashMessenger()->addMessage('Your password is too short.');
                return $this->redirect()->toRoute('register-normal');
            }

            $sessionContainer = $this->getServiceManager()->get('user_session');
            $sessionContainer->username = $data['email'];
            $sessionContainer->password = $data['password'];

            $this->u2fServerService = $this->getServiceManager()->get('U2fServer');
            $this->u2fServerService->init('https://localhost');
            $this->u2fServerService->controller = $this;

            if(!$data = $this->u2fServerService->getRegisterData($this->getServiceManager()->get('U2fRegisterRequest'))) {
                $this->flashMessenger()->addMessage('Something went wrong. Please try again.');
                return $this->redirect()->toRoute('register-normal');
            }

            $view = new ViewModel([
                'u2f_data' => $data
            ]);

            return $view;
        } else {
            return $this->redirect()->toRoute('register-normal');
        }
    }

    /**
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function doAction() {
        if($this->getRequest()->isPost()) {
            $this->u2fServerService = $this->getServiceManager()->get('U2fServer');
            $this->u2fServerService->init('https://localhost');

            $this->u2fRegisterRequestService = $this->getServiceManager()->get('U2fRegisterRequest');
            $this->u2fRegisterRequestService->init($_POST['challenge'], $_POST['appId']);

            $this->u2fRegisterResponseService = $this->getServiceManager()->get('U2fRegisterResponse');
            $this->u2fRegisterResponseService->init($_POST['registrationData'], $_POST['clientData'], $_POST['errorCode']);

            $registration = $this->u2fServerService->doRegister($this->u2fRegisterRequestService, $this->u2fRegisterResponseService, $this->getServiceManager()->get('U2fRegistration'));

            $entityManager = $this->getServiceManager()->get('doctrine.entitymanager.orm_default');

            $sessionContainer = $this->getServiceManager()->get('user_session');

            $user = new User();
            $user->setUsername($sessionContainer->username);
            $user->setPassword($sessionContainer->password);
            $user->setKeyhandle($registration->keyHandle);
            $user->setPublicKey($registration->publicKey);
            $user->setCertificate($registration->certificate);
            $user->setCounter($registration->counter);

            $entityManager->persist($user);
            $entityManager->flush();

            /*
             * Hurray registrated!
             */
            echo 'Perfect! You are now registrated.';

            return $this->getResponse();
        }

        return $this->redirect()->toRoute('register-normal');
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