<?php

declare(strict_types=1);

namespace Solu1\StatHubs\Plugin;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Backend\Model\Auth;
use Solu1\StatHubs\Model\CredentialProvisioner;

/**
 * The API user is not a person and cannot open the admin.
 */
class BlockServiceLogin
{
    public function __construct(
        private readonly CredentialProvisioner $provisioner
    ) {
    }

    /**
     * @param mixed $username
     * @param mixed $password
     * @return mixed
     * @throws AuthenticationException
     */
    public function aroundLogin(Auth $subject, callable $proceed, $username, $password)
    {
        if ($this->provisioner->isServiceUsername((string) $username)) {
            throw new AuthenticationException(
                __('This account is used by StatHubs and cannot sign in.')
            );
        }

        return $proceed($username, $password);
    }
}
