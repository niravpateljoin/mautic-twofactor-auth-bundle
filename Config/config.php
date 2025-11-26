<?php

use MauticPlugin\MauticTwoFactorAuthBundle\Controller\AjaxController;
use MauticPlugin\MauticTwoFactorAuthBundle\Controller\TwoFactorSetupController;
use MauticPlugin\MauticTwoFactorAuthBundle\Controller\TwoFactorVerifyController;

return [
    'name'        => 'Mautic Two Factor Auth Bundle',
    'description' => 'Adds Two-Factor Authentication (2FA) for Mautic user logins.',
    'version'     => '1.0.0',
    'author'      => 'Drashti Kanani',
    'bundle'      => [
        'namespace' => 'MauticPlugin\\MauticTwoFactorAuthBundle',
        'path'      => 'plugins/MauticTwoFactorAuthBundle',
    ],
    'routes' => [
        'main' => [
            'mautic_twofactor_toggle' => [
                'path'       => '/mautic-twofactor/toggle',
                'controller' => AjaxController::class . '::toggleAction',
            ],
            'mautic_twofactor_setup' => [
                'path'       => '/mautic-twofactor/setup',
                'controller' => TwoFactorSetupController::class . '::showAction',
            ],
            'mautic_twofactor_verify' => [
                'path'       => '/mautic-twofactor/verify',
                'controller' => TwoFactorVerifyController::class . '::showAction',
            ],
            'mautic_twofactor_reset' => [
                'path'       => '/mautic-twofactor/reset',
                'controller' => TwoFactorVerifyController::class . '::resetAction',
            ],
            // 'mautic_twofactor_challenge' => [
            //     'path'       => '/mautic-twofactor/challenge',
            //     'controller' => ChallengeController::class . '::showAction',
            // ],
        ],
    ],

    'assets' => [
        'js' => [
            'plugins/MauticTwoFactorAuthBundle/Assets/js/twofactor-toggle.js',
        ],
    ],
    'views' => [
        'paths' => [
            'MauticTwoFactorAuth' => __DIR__.'/../Resources/views',
        ],
    ],
];
