<?php
declare(strict_types=1);

namespace MauticPlugin\MauticTwoFactorAuthBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use MauticPlugin\MauticTwoFactorAuthBundle\Helper\TwoFactorHelper;

class AjaxController extends CommonController
{
    public function toggleAction(Request $request, TwoFactorHelper $helper): JsonResponse
    {
        $user = $this->get('mautic.helper.user')->getUser();
        // Get toggle value from AJAX request
        $enabled = $request->get('enabled') === '1';

        // Save via your existing helper
        $helper->setEnabled($user, $enabled);
        $setupRequired = $enabled && $helper->needsSetup($user);

        return new JsonResponse([
            'success' => true,
            'enabled' => $enabled,
            'setupRequired' => $setupRequired,
            'message' => $enabled
                ? 'Two-Factor Authentication has been enabled.'
                : 'Two-Factor Authentication has been disabled.',
        ]);
    }
}
