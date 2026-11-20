<?php

declare(strict_types=1);

namespace Solu1\ProPulse\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        $configTable = $setup->getTable('core_config_data');

        $username = $connection->fetchOne(
            $connection->select()
                ->from($configTable, 'value')
                ->where('path = ?', 'solu1_propulse/credentials/username')
                ->limit(1)
        );
        $roleId = $connection->fetchOne(
            $connection->select()
                ->from($configTable, 'value')
                ->where('path = ?', 'solu1_propulse/credentials/role_id')
                ->limit(1)
        );

        if (is_string($username) && $username !== '') {
            $userTable = $setup->getTable('admin_user');
            $userId = $connection->fetchOne(
                $connection->select()
                    ->from($userTable, 'user_id')
                    ->where('username = ?', $username)
                    ->limit(1)
            );
            if ($userId) {
                $connection->delete($setup->getTable('authorization_role'), ['user_id = ?' => $userId]);
                $connection->delete($userTable, ['user_id = ?' => $userId]);
            }
        }

        if ($roleId) {
            $connection->delete($setup->getTable('authorization_rule'), ['role_id = ?' => $roleId]);
            $connection->delete($setup->getTable('authorization_role'), ['role_id = ?' => $roleId, 'user_type = ?' => 2]);
        }

        $connection->delete($configTable, ['path LIKE ?' => 'solu1_propulse/%']);
        $setup->endSetup();
    }
}
