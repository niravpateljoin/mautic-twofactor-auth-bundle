<?php
declare(strict_types=1);

namespace MauticPlugin\MauticTwoFactorAuthBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticTwoFactorAuthBundle\Entity\UserTwoFactor;
use OTPHP\TOTP;

class TwoFactorHelper
{
    private const QR_CODE_SIZE   = 240;
    private const QR_CODE_MARGIN = 0;

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Enable or disable 2FA for a user and reset stored secrets; the actual secret is created later during setup.
     */
    public function setEnabled(User $user, bool $enabled): ?string
    {
        $record = $this->findUserTwoFactor($user);

        if (!$record) {
            $record = new UserTwoFactor();
            $record->setUserId($user->getId());
        }

        $record->setTwofactorEnabled($enabled);
        if ($enabled) {
            $record->setTwofactorVerified(false);
            $record->setTotpSecret(null);
            $record->setTwofactorTempSecret(null);
        } else {
            $record->setTwofactorVerified(false);
            $record->setTotpSecret(null);
            $record->setTwofactorTempSecret(null);
        }

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return null;
    }

    /**
     * Check if 2FA is enabled for the given user.
     */
    public function isEnabled(User $user): bool
    {
        $record = $this->findUserTwoFactor($user);
        return $record ? $record->isTwofactorEnabled() : false;
    }

    /**
     * Check if the current user needs to complete the 2FA setup.
     */
    public function needsSetup(User $user): bool
    {
        $record = $this->findUserTwoFactor($user);
        return $record ? ($record->isTwofactorEnabled() && !$record->isTwofactorVerified()) : false;
    }

    /**
     * Get the QR code data URI to render on the user's setup screen.
     */
    public function getSetupQrCodeUrl(User $user): string
    {
        $record = $this->findUserTwoFactor($user);
        if (!$record || !$record->isTwofactorEnabled()) {
            return '';
        }

        $secret = $this->getPendingSecret($record);
        return $this->getQrCodeUrl($record, $secret);
    }

    private function getPendingSecret(UserTwoFactor $record): string
    {
        $secret = $record->getTwofactorTempSecret();
        if ($secret) {
            return $secret;
        }

        $secret = $this->generateSecret();
        $record->setTwofactorTempSecret($secret);
        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $secret;
    }

    /**
     * Generate a new TOTP secret.
     */
    public function generateSecret(): string
    {
        $totp = TOTP::create();
        return $totp->getSecret();
    }

    /**
     * Get the QR Code data URI (for Google Authenticator scan).
     */
    public function getQrCodeUrl(UserTwoFactor $record, ?string $secret = null): string
    {
        $secret = $secret ?? $record->getTotpSecret();
        if (!$secret) {
            return '';
        }

        $totp = TOTP::create($secret);
        $label = 'Mautic User #' . ($record->getUserId() ?? '0');
        $totp->setLabel($label);
        $totp->setIssuer('Mautic');

        $qrCode = new QrCode(
            $totp->getProvisioningUri(),
            new Encoding('UTF-8'),
            ErrorCorrectionLevel::Low,
            self::QR_CODE_SIZE,
            self::QR_CODE_MARGIN
        );

        return (new PngWriter())->write($qrCode)->getDataUri();
    }

    /**
     * Verify an OTP code entered by the user.
     */
    public function verifyCode(User $user, string $code): bool
    {
        $record = $this->findUserTwoFactor($user);
        if (!$record) {
            return false;
        }

        $secret = $this->getActiveSecret($record);
        if (!$secret) {
            return false;
        }

        $totp = TOTP::create($secret);
        return $totp->verify($code);
    }

    public function markSetupComplete(User $user): void
    {
        $record = $this->findUserTwoFactor($user);
        if (!$record) {
            return;
        }

        $record->setTwofactorVerified(true);
        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }

    public function finalizeSetup(User $user): void
    {
        $record = $this->findUserTwoFactor($user);
        if (!$record) {
            return;
        }

        $pendingSecret = $record->getTwofactorTempSecret();
        if (!$pendingSecret) {
            return;
        }

        $record->setTotpSecret($pendingSecret);
        $record->setTwofactorTempSecret(null);
        $record->setTwofactorVerified(true);
        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }

    public function hasPendingSecret(User $user): bool
    {
        $record = $this->findUserTwoFactor($user);
        return $record ? (!$record->isTwofactorVerified() && $record->getTwofactorTempSecret() !== null) : false;
    }

    public function isActive(User $user): bool
    {
        $record = $this->findUserTwoFactor($user);
        return $record ? ($record->isTwofactorEnabled() && $record->isTwofactorVerified() && null !== $record->getTotpSecret()) : false;
    }

    private function getActiveSecret(UserTwoFactor $record): ?string
    {
        return $record->getTwofactorTempSecret() ?? $record->getTotpSecret();
    }

    /**
     * Find the user’s 2FA record.
     */
    private function findUserTwoFactor(User $user): ?UserTwoFactor
    {
        return $this->entityManager
            ->getRepository(UserTwoFactor::class)
            ->findOneBy(['userId' => $user->getId()]);
    }
}
