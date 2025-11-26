<?php
declare(strict_types=1);

namespace MauticPlugin\MauticTwoFactorAuthBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticTwoFactorAuthBundle\EventSubscriber\TwoFactorLoginSubscriber;
use MauticPlugin\MauticTwoFactorAuthBundle\Helper\TwoFactorHelper;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorVerifyController extends CommonController
{
    public function __construct(
        private UserHelper $userHelper,
        private TwoFactorHelper $twoFactorHelper,
        Translator $translator
    ) {
        $this->translator = $translator;
    }

    public function showAction(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->userHelper->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('mautic_user_login');
        }

        $session = $request->getSession();
        $isSetupFlow = $session && $session->get(TwoFactorLoginSubscriber::SETUP_SESSION_KEY);
        $isVerifyFlow = $session && $session->get(TwoFactorLoginSubscriber::VERIFY_SESSION_KEY);

        if ($isSetupFlow) {
            if (!$this->twoFactorHelper->needsSetup($user)) {
                $session->remove(TwoFactorLoginSubscriber::SETUP_SESSION_KEY);
                return $this->redirectToRoute('mautic_dashboard_index');
            }

            if (!$this->twoFactorHelper->hasPendingSecret($user)) {
                return $this->redirectToRoute('mautic_twofactor_setup');
            }
        } elseif ($isVerifyFlow) {
            if (!$this->twoFactorHelper->isActive($user)) {
                $session->remove(TwoFactorLoginSubscriber::VERIFY_SESSION_KEY);
                return $this->redirectToRoute('mautic_dashboard_index');
            }
        } else {
            return $this->redirectToRoute('mautic_dashboard_index');
        }

        $verifyError = null;
        if ($request->isMethod('POST')) {
            $code = (string) $request->request->get('twofactor_code', '');

            if ($this->twoFactorHelper->verifyCode($user, $code)) {
                if ($isSetupFlow) {
                    $this->twoFactorHelper->finalizeSetup($user);
                    if ($session) {
                        $session->remove(TwoFactorLoginSubscriber::SETUP_SESSION_KEY);
                    }
                    $flashKey = 'mautic.twofactor.setup.success';
                } else {
                    if ($session) {
                        $session->remove(TwoFactorLoginSubscriber::VERIFY_SESSION_KEY);
                    }
                    $flashKey = 'mautic.twofactor.verify.success';
                }

                $this->addFlash('success', $this->translator->trans($flashKey));
                return $this->redirectToRoute('mautic_dashboard_index');
            }

            $verifyError = 'mautic.twofactor.verify.error_invalid_code';
        }

        return $this->render(
            '@MauticTwoFactorAuth/Security/verify.html.twig',
            [
                'verifyError' => $verifyError,
            ]
        );
    }

    public function resetAction(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->userHelper->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('mautic_user_login');
        }

        if (!$this->twoFactorHelper->isActive($user)) {
            return $this->redirectToRoute('mautic_dashboard_index');
        }

        $this->twoFactorHelper->setEnabled($user, true);

        $session = $request->getSession();
        if ($session) {
            $session->remove(TwoFactorLoginSubscriber::VERIFY_SESSION_KEY);
            $session->set(TwoFactorLoginSubscriber::SETUP_SESSION_KEY, true);
        }

        return $this->redirectToRoute('mautic_twofactor_setup');
    }
}
