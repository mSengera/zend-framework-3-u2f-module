<?php
namespace SengeraU2F;

use Zend\Router\Http\Literal;
use Zend\ServiceManager\Factory\InvokableFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

return [
    'controllers' => [
        'factories' => [
            Controller\U2FController::class => InvokableFactory::class,
            Controller\RegisterController::class => Controller\ControllerFactory::class,
        ],
        'aliases' => [
            'Controller\Register' => Controller\RegisterController::class,
        ],
    ],
    'router' => [
        'routes' => [
            'register-normal' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/register',
                    'defaults' => [
                        'controller' => Controller\RegisterController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'register-u2f' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/register-u2f',
                    'defaults' => [
                        'controller' => Controller\RegisterController::class,
                        'action'     => 'u2f',
                    ],
                ],
            ],
            'u2f-do' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/register-u2f-do',
                    'defaults' => [
                        'controller' => Controller\RegisterController::class,
                        'action'     => 'do',
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'sengera-u2-f/register/index' => __DIR__ . '/../view/register/register.phtml',
            'sengera-u2-f/register/u2f' => __DIR__ . '/../view/register/register-u2f.phtml',
        ],
    ],
    'service_manager' => [
        'services' => [
            'U2fConstant' => new Service\ConstantService(),
            'U2fLoginResponse' => new Service\U2fLoginResponseService(),
            'U2fRegisterRequest' => new Service\U2fRegisterRequestService(),
            'U2fRegisterResponse' => new Service\U2fRegisterResponseService(),
            'U2fRegistration' => new Service\U2fRegistrationService(),
            'U2fServer' => new Service\U2fServerService(),
            'U2fSignRequest' => new Service\U2fSignRequestService(),
        ]
    ],
    'session_containers' => [
        'user_session'
    ],
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Entity']
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver'
                ]
            ]
        ]
    ]
];