<?php

declare(strict_types=1);

namespace Solu1\ProPulse\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Solu1\ProPulse\Model\CredentialProvisioner;

class ProvisionApiUser implements DataPatchInterface
{
    public function __construct(
        private readonly CredentialProvisioner $provisioner
    ) {
    }

    public function apply(): void
    {
        $this->provisioner->ensure();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
