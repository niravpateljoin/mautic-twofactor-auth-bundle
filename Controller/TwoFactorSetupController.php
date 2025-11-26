<?php
declare(strict_types=1);

namespace MauticPlugin\MauticTwoFactorAuthBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticTwoFactorAuthBundle\Helper\TwoFactorHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorSetupController extends CommonController
{
    /**
     * Shows the QR setup page for Two-Factor Authentication
     *
     * @return Response
     */
    public function showAction(
        Request $request,
        UserHelper $userHelper,
        TwoFactorHelper $twoFactorHelper
    ): Response {
        /** @var User|null $user */
        $user = $userHelper->getUser();

        // Must be logged in
        if (!$user instanceof User) {
            return $this->redirectToRoute('mautic_user_login');
        }

        // User does not need setup, redirect
        if (!$twoFactorHelper->needsSetup($user)) {
            return $this->redirectToRoute('mautic_dashboard_index');
        }

        $step = (string) $request->query->get('step', 'intro');
        if ($step !== 'qr') {
            return $this->delegateView([
                'viewParameters' => [
                    'setupNextUrl' => $this->generateUrl('mautic_twofactor_setup', ['step' => 'qr']),
                ],
                'contentTemplate' => '@MauticTwoFactorAuth/Security/setup_intro.html.twig',
            ]);
        }

        // Generate QR code for authenticator apps
        $qrCodeUrl = $twoFactorHelper->getSetupQrCodeUrl($user);

        // Render using the correct Mautic 7 method
        return $this->delegateView([
            'viewParameters' => [
                'qrCodeUrl' => $qrCodeUrl,
                'user'      => $user,
            ],
            'contentTemplate' => '@MauticTwoFactorAuth/Security/qr.html.twig',
        ]);
    }
}
