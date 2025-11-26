<?php
declare(strict_types=1);

namespace MauticPlugin\MauticTwoFactorAuthBundle\EventSubscriber;

use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticTwoFactorAuthBundle\Helper\TwoFactorHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class TwoFactorLoginSubscriber implements EventSubscriberInterface
{
    public const SETUP_SESSION_KEY   = 'mautic.twofactor.setup_required';
    public const VERIFY_SESSION_KEY  = 'mautic.twofactor.verify_required';

    public function __construct(private TwoFactorHelper $twoFactorHelper, private RouterInterface $router)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => ['onSecurityInteractiveLogin', 0],
            KernelEvents::REQUEST            => ['onKernelRequest', 0],
        ];
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        if (defined('MAUTIC_INSTALLER')) {
            return;
        }

        $token = $event->getAuthenticationToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        $session = $event->getRequest()->getSession();
        if (!$session) {
            return;
        }

        if ($this->twoFactorHelper->needsSetup($user)) {
            $session->set(self::SETUP_SESSION_KEY, true);
            return;
        }

        if ($this->twoFactorHelper->isActive($user)) {
            $session->set(self::VERIFY_SESSION_KEY, true);
        }

    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->isMainRequest($event)) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if ($session->get(self::SETUP_SESSION_KEY)) {
            $currentRoute = $request->attributes->get('_route');
            if (\in_array($currentRoute, ['mautic_twofactor_setup', 'mautic_twofactor_verify'], true)) {
                return;
            }

            try {
                $targetUrl = $this->router->generate('mautic_twofactor_setup');
            } catch (RouteNotFoundException $e) {
                $targetUrl = '/s/mautic-twofactor/setup';
            }

            $event->setResponse(new RedirectResponse($targetUrl));
            return;
        }

        if ($session->get(self::VERIFY_SESSION_KEY)) {
            $currentRoute = $request->attributes->get('_route');
            if ('mautic_twofactor_verify' === $currentRoute) {
                return;
            }

            try {
                $targetUrl = $this->router->generate('mautic_twofactor_verify');
            } catch (RouteNotFoundException $e) {
                $targetUrl = '/s/mautic-twofactor/verify';
            }

            $event->setResponse(new RedirectResponse($targetUrl));
        }
    }

    private function isMainRequest(RequestEvent $event): bool
    {
        if (method_exists($event, 'isMainRequest')) {
            return $event->isMainRequest();
        }

        return $event->isMasterRequest();
    }
}
