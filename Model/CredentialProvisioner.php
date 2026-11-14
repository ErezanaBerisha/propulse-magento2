<?php

declare(strict_types=1);

namespace Solu1\StatHubs\Model;

use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;
use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Model\UserFactory;

/**
 * Creates the Magento admin user StatHubs uses to read orders.
 */
class CredentialProvisioner
{
    public const XML_USERNAME = 'solu1_stathubs/credentials/username';
    public const XML_PASSWORD = 'solu1_stathubs/credentials/password';
    public const XML_USER_ID = 'solu1_stathubs/credentials/user_id';
    public const XML_ROLE_ID = 'solu1_stathubs/credentials/role_id';
    public const PLATFORM_TYPE = '4';

    /**
     * Read access for orders and the store currency.
     *
     * @var list<string>
     */
    private const RESOURCES = [
        'Magento_Sales::sales',
        'Magento_Sales::sales_operation',
        'Magento_Sales::sales_order',
        'Magento_Sales::actions',
        'Magento_Sales::actions_view',
        'Magento_Backend::store',
    ];

    public function __construct(
        private readonly UserFactory $userFactory,
        private readonly RoleFactory $roleFactory,
        private readonly RulesFactory $rulesFactory,
        private readonly WriterInterface $configWriter,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ReinitableConfigInterface $reinitableConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly StoreManagerInterface $storeManager,
        private readonly TypeListInterface $cacheTypeList
    ) {
    }

    /**
     * @return array{username: string, password: string}|null
     */
    public function ensure(): ?array
    {
        $username = $this->configValue(self::XML_USERNAME);
        if ($username === '') {
            $username = $this->availableUsername();
        }

        $password = $this->readPassword();
        $createdPassword = false;
        if ($password === '') {
            $password = $this->generatePassword();
            $createdPassword = true;
        }

        try {
            $roleId = $this->ensureRole();
            $userId = $this->ensureUser($username, $password, $roleId, $createdPassword);
        } catch (LocalizedException $exception) {
            return null;
        }

        if ($userId < 1 || $roleId < 1) {
            return null;
        }

        $this->configWriter->save(self::XML_USERNAME, $username);
        $this->configWriter->save(self::XML_PASSWORD, $this->encryptor->encrypt($password));
        $this->configWriter->save(self::XML_USER_ID, (string) $userId);
        $this->configWriter->save(self::XML_ROLE_ID, (string) $roleId);
        $this->cacheTypeList->cleanType(ConfigCache::TYPE_IDENTIFIER);
        $this->reinitableConfig->reinit();

        return [
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * @return array{shopUrl: string, username: string, password: string, platformType: string}
     */
    public function credentials(): array
    {
        $ensured = $this->ensure();

        return [
            'shopUrl' => $this->shopUrl(),
            'username' => $ensured['username'] ?? '',
            'password' => $ensured['password'] ?? '',
            'platformType' => self::PLATFORM_TYPE,
        ];
    }

    public function shopUrl(): string
    {
        return rtrim($this->storeManager->getStore()->getBaseUrl(), '/');
    }

    public function isServiceUsername(string $username): bool
    {
        $stored = $this->configValue(self::XML_USERNAME);

        return $stored !== '' && strcasecmp($stored, $username) === 0;
    }

    public function isServiceUserId(int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }

        return (int) $this->configValue(self::XML_USER_ID) === $userId;
    }

    private function ensureRole(): int
    {
        $storedId = (int) $this->configValue(self::XML_ROLE_ID);
        if ($storedId > 0) {
            $role = $this->roleFactory->create()->load($storedId);
            if ($role->getId() && (string) $role->getRoleType() === 'G') {
                $this->grantResources((int) $role->getId());

                return (int) $role->getId();
            }
        }

        $role = $this->roleFactory->create();
        $role->setName($this->availableRoleName())
            ->setPid(0)
            ->setRoleType('G')
            ->setUserType(2);
        $role->save();
        $this->grantResources((int) $role->getId());

        return (int) $role->getId();
    }

    private function grantResources(int $roleId): void
    {
        $this->rulesFactory->create()
            ->setRoleId($roleId)
            ->setResources(self::RESOURCES)
            ->saveRel();
    }

    /**
     * @throws LocalizedException
     */
    private function ensureUser(string $username, string $password, int $roleId, bool $resetPassword): int
    {
        $storedId = (int) $this->configValue(self::XML_USER_ID);
        $user = $this->userFactory->create();
        if ($storedId > 0) {
            $user->load($storedId);
        }
        if (!$user->getId()) {
            $user = $this->userFactory->create()->loadByUsername($username);
        }

        if ($user->getId()) {
            $dirty = false;
            if ((int) $user->getRole()->getId() !== $roleId) {
                $user->setRoleId($roleId);
                $dirty = true;
            }
            if ((int) $user->getIsActive() !== 1) {
                $user->setIsActive(1);
                $dirty = true;
            }
            if ($resetPassword) {
                $user->setPassword($password);
                $dirty = true;
            }
            if ($dirty) {
                $user->save();
            }

            return (int) $user->getId();
        }

        $user = $this->userFactory->create();
        $user->setUsername($username);
        $user->setFirstname('StatHubs');
        $user->setLastname('API');
        $user->setEmail($this->availableEmail());
        $user->setPassword($password);
        $user->setIsActive(1);
        $user->setInterfaceLocale('en_US');
        $user->setRoleId($roleId);
        $user->save();

        return (int) $user->getId();
    }

    private function availableUsername(): string
    {
        foreach (['stathubs', 'stathubs-api'] as $username) {
            $user = $this->userFactory->create()->loadByUsername($username);
            if (!$user->getId()) {
                return $username;
            }
        }

        return 'stathubs-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }

    private function availableRoleName(): string
    {
        foreach (['StatHubs', 'StatHubs API'] as $name) {
            $role = $this->roleFactory->create()->load($name, 'role_name');
            if (!$role->getId()) {
                return $name;
            }
        }

        return 'StatHubs ' . substr(bin2hex(random_bytes(2)), 0, 4);
    }

    private function availableEmail(): string
    {
        $host = parse_url($this->shopUrl(), PHP_URL_HOST);
        if (!is_string($host) || !str_contains($host, '.')) {
            $host = 'stathubs.local';
        }

        return 'stathubs-api@' . $host;
    }

    private function generatePassword(): string
    {
        return 'Sh!' . bin2hex(random_bytes(12));
    }

    private function readPassword(): string
    {
        $stored = $this->configValue(self::XML_PASSWORD);
        if ($stored === '') {
            return '';
        }

        $plain = $this->encryptor->decrypt($stored);

        return is_string($plain) ? $plain : '';
    }

    private function configValue(string $path): string
    {
        $value = $this->scopeConfig->getValue($path);

        return is_string($value) ? trim($value) : '';
    }
}
