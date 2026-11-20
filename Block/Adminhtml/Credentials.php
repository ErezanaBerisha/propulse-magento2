<?php

declare(strict_types=1);

namespace Solu1\ProPulse\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Solu1\ProPulse\Model\CredentialProvisioner;

class Credentials extends Template
{
    public function __construct(
        Context $context,
        private readonly CredentialProvisioner $provisioner,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array{shopUrl: string, username: string, password: string, platformType: string}
     */
    public function getCredentials(): array
    {
        return $this->provisioner->credentials();
    }

    public function getJsConfigJson(): string
    {
        $credentials = $this->getCredentials();

        return (string) json_encode(
            [
                'username' => $credentials['username'],
                'password' => $credentials['password'],
                'shopUrl' => $credentials['shopUrl'],
                'platformType' => $credentials['platformType'],
                'copied' => (string) __('Copied'),
            ],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    public function isHttps(): bool
    {
        return parse_url($this->getCredentials()['shopUrl'], PHP_URL_SCHEME) === 'https';
    }
}
