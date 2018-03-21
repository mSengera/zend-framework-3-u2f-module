<?php
namespace SengeraU2F\Form;

use Zend\Form\Form;

class LoginForm extends Form
{

    // CSRF Protection
    private $csrfToken;

    public function __construct($csrfToken)
    {
        parent::__construct('login-form');
        $this->setAttribute('method', 'post');
        $this->setAttribute('action', '/fido-to-zend/public/login-u2f');

        $this->csrfToken = $csrfToken;
        $this->_addElements();
    }

    private function _addElements() {
        $this->add([
            'type'  => 'text',
            'name' => 'email',
            'attributes' => [
                'id'  => 'email',
                'placeholder' => 'E-Mail',
                'class' => 'form-control',
                'required' => 'required'
            ],
            'options' => [
                'label' => 'E-Mail',
            ],
        ]);

        $this->add([
            'type'  => 'password',
            'name' => 'password',
            'attributes' => [
                'id'  => 'password',
                'placeholder' => 'Password',
                'class' => 'form-control',
                'required' => 'required'
            ],
            'options' => [
                'label' => 'Password',
            ],
        ]);

        // CSRF Protection
        $this->add([
            'type'  => 'hidden',
            'name' => 'token',
            'attributes' => [
                'id'  => 'token',
                'required' => 'required',
                'value' => $this->csrfToken
            ],
        ]);

        $this->add([
            'type'  => 'submit',
            'name' => 'submit',
            'attributes' => [
                'id'  => 'submit',
                'value' => 'Login',
                'class' => 'btn btn-primary'
            ]
        ]);
    }
}