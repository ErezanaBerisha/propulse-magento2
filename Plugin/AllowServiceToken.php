<?php

declare(strict_types=1);

namespace Solu1\StatHubs\Plugin;

use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\TwoFactorAuth\Model\AdminAccessTokenService;
use Solu1\StatHubs\Model\CredentialProvisioner;

/**
 * StatHubs reads orders with a username and password.
 * Magento otherwise refuses that token endpoint until an authenticator app is set up.
 * This restores a token for the StatHubs user only. Other admins still use two-factor auth.
 */
class AllowServiceToken
{
    public function __construct(
        private readonly CredentialProvisioner $provisioner,
        private readonly AdminTokenServiceInterface $adminTokenService
    ) {
    }

    /**
     * @param mixed $username
     * @param mixed $password
     */
    public function aroundCreateAdminAccessToken(
        AdminAccessTokenService $subject,
        callable $proceed,
        $username,
        $password
    ): string {
        if ($this->provisioner->isServiceUsername((string) $username)) {
            return $this->adminTokenService->createAdminAccessToken((string) $username, (string) $password);
        }

        return $proceed($username, $password);
    }
}
