<?php

namespace MauticPlugin\MauticTwoFactorAuthBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;

class MauticTwoFactorAuthBundle extends PluginBundleBase
{
    public function getEntityNamespace(): string
    {
        return 'MauticPlugin\MauticTwoFactorAuthBundle\Entity';
    }
    
}
