<?php
namespace SengeraU2F\Form;

use Zend\Form\Form;

class RegisterForm extends Form
{
    public function __construct()
    {
        parent::__construct('register-form');
        $this->setAttribute('method', 'post');
        $this->setAttribute('action', '/fido-to-zend/public/register-u2f');

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

        $this->add([
            'type'  => 'password',
            'name' => 'repeat-password',
            'attributes' => [
                'id'  => 'repeat-password',
                'placeholder' => 'Repeat password',
                'class' => 'form-control',
                'required' => 'required'
            ]
        ]);

        $this->add([
            'type'  => 'submit',
            'name' => 'submit',
            'attributes' => [
                'id'  => 'submit',
                'value' => 'Register now',
                'class' => 'btn btn-primary'
            ]
        ]);
    }
}