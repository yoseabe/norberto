<?php

declare(strict_types=1);

namespace Swp\RefundSystemSix\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Migration\MigrationStep;
use Swp\RefundSystemSix\Core\RefundSystem\RefundSystemDefinition;

class Migration1702385566RefundSystem extends MigrationStep
{
    public const MEDIAID_COLUMN = 'media_id';

    public function getCreationTimestamp(): int
    {
        return 1702385566;
    }

    public function update(Connection $connection): void
    {
        if (!$this->checkIfColumnExist($connection, RefundSystemDefinition::ENTITY_NAME, self::MEDIAID_COLUMN)) {
            $sql = \str_replace(
                ['#table#', '#column#'],
                [RefundSystemDefinition::ENTITY_NAME, self::MEDIAID_COLUMN],
                'ALTER TABLE `#table#` ADD COLUMN `#column#` BINARY(16) DEFAULT NULL AFTER `active`'
            );
            $connection->executeStatement($sql);

            $sql = \str_replace(
                ['#table#', '#column#', '#references_column#'],
                [RefundSystemDefinition::ENTITY_NAME, self::MEDIAID_COLUMN, MediaDefinition::ENTITY_NAME],
                'ALTER TABLE `#table#`
                        ADD CONSTRAINT `fk.#table#.#column#` FOREIGN KEY (`#column#`)
                            REFERENCES `#references_column#` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
            ');
            $connection->executeStatement($sql);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    /**
     * Prüfe, ob Feld schon existiert.
     *
     * @throws Exception
     */
    private function checkIfColumnExist(Connection $connection, string $tableName, string $columnName): bool
    {
        $sql = "SELECT column_name
                FROM information_schema.columns
                WHERE table_name = '{$tableName}'
                    AND column_name = '{$columnName}'
                    AND table_schema = DATABASE();
                ";

        $columnNameInDb = $connection->executeQuery($sql)
            ->fetchOne();

        return $columnNameInDb === $columnName;
    }
}
