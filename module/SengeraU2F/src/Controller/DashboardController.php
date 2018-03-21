<?php
namespace SengeraU2F\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class DashboardController extends AbstractActionController {

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    public function indexAction() {
        $this->_redirectUserIfNotLoggedIn();
    }

    private function _redirectUserIfNotLoggedIn() {
        $sessionContainer = $this->getServiceManager()->get('user_session');

        if($sessionContainer->logged_in == true) {
            return true;
        } else {
            $this->flashMessenger()->addMessage('Please log in to use this service.');
            return $this->redirect()->toRoute('login-normal');
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