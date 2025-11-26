<?php
declare(strict_types=1);

namespace MauticPlugin\MauticTwoFactorAuthBundle\Form\Extension;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Type\UserType;
use MauticPlugin\MauticTwoFactorAuthBundle\Helper\TwoFactorHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class UserTypeExtension extends AbstractTypeExtension
{
    private TwoFactorHelper $twoFactorHelper;

    public function __construct(TwoFactorHelper $twoFactorHelper)
    {
        $this->twoFactorHelper = $twoFactorHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $builder->getData();
        $enabled = $user instanceof User ? $this->twoFactorHelper->isEnabled($user) : false;

        // Add the toggle
        $builder->add('twofactor_enabled', YesNoButtonGroupType::class, [
            'label'       => 'mautic.twofactor.enable',
            'mapped'      => false,
            'required'    => false,
            'help'        => 'mautic.twofactor.Security.description',
            'yes_label'   => 'mautic.twofactor.toggle.on',
            'no_label'    => 'mautic.twofactor.toggle.off',
            'data'        => $enabled,
            'row_attr'    => [
                'class'               => 'twofactor-toggle',
                'data-twofactor-state' => $enabled ? '1' : '0',
            ],
        ]);

        // Save toggle on submit
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var User $user */
            $user = $event->getData();
            $form = $event->getForm();

            if ($user instanceof User && $form->has('twofactor_enabled')) {
                $enabled = (bool) $form->get('twofactor_enabled')->getData();
                $this->twoFactorHelper->setEnabled($user, $enabled);
            }
        });
    }

    public static function getExtendedTypes(): iterable
    {
        return [UserType::class];
    }
}
