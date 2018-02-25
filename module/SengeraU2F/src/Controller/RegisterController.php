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

class RegisterController extends AbstractActionController
{

    protected $serviceManager;
    public $u2fServerService;
    public $u2fRegisterRequestService;
    public $u2fRegisterResponseService;

    public function indexAction()
    {
        $form = new RegisterForm();

        return new ViewModel([
            'form' => $form
        ]);
    }

    public function u2fAction() {
        if($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();

            if(($data['password'] == $data['repeat-password']) && ($data['email']) != '') {
                $sessionContainer = $this->getServiceManager()->get('user_session');

                $sessionContainer->username = $data['email'];
                $sessionContainer->password = $data['password'];

                $this->u2fServerService = $this->getServiceManager()->get('U2fServer');
                $this->u2fServerService->init('https://localhost');

                $data = $this->u2fServerService->getRegisterData($this->getServiceManager()->get('U2fRegisterRequest'));

                $view = new ViewModel([
                    'u2f_data' => $data
                ]);

                return $view;
            } else {
                $this->redirect('/');
                // Error
            }
        }
    }

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

            echo 'Perfect! You are now registered.';
        }

        return $this->getResponse();
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