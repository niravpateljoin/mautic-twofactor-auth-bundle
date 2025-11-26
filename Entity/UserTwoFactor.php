<?php
declare(strict_types=1);

namespace MauticPlugin\MauticTwoFactorAuthBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class UserTwoFactor
{
    private ?int $id = null;
    private ?int $userId = null;
    private bool $twofactorEnabled = false;
    private bool $twofactorVerified = false;
    private ?string $twofactorTempSecret = null;
    private ?string $totpSecret = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function isTwofactorEnabled(): bool
    {
        return $this->twofactorEnabled;
    }

    public function setTwofactorEnabled(bool $enabled): self
    {
        $this->twofactorEnabled = $enabled;

        return $this;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $secret): self
    {
        $this->totpSecret = $secret;

        return $this;
    }

    public function getTwofactorTempSecret(): ?string
    {
        return $this->twofactorTempSecret;
    }

    public function setTwofactorTempSecret(?string $secret): self
    {
        $this->twofactorTempSecret = $secret;

        return $this;
    }

    public function isTwofactorVerified(): bool
    {
        return $this->twofactorVerified;
    }

    public function setTwofactorVerified(bool $verified): self
    {
        $this->twofactorVerified = $verified;

        return $this;
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('user_twofactor')
                ->addId()
                ->addField('userId', Types::INTEGER, [
                    'columnName' => 'user_id',
                    'options'    => ['unsigned' => true],
                ])
                ->addField('twofactorEnabled', Types::BOOLEAN, [
                    'columnName' => 'twofactor_enabled',
                    'options'    => ['default' => 0],
                ])
                ->addField('twofactorTempSecret', Types::TEXT, [
                    'columnName' => 'twofactor_temp_secret',
                    'nullable'   => true,
                ])
                ->addField('totpSecret', Types::STRING, [
                    'columnName' => 'totp_secret',
                    'nullable'   => true,
                    'length'     => 255,
                ])
                ->addField('twofactorVerified', Types::BOOLEAN, [
                    'columnName' => 'twofactor_verified',
                    'options'    => ['default' => 0],
                ])
                ->addUniqueConstraint(['user_id'], 'user_twofactor_user_id_unique');
    }
}
