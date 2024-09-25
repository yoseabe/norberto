<?php declare(strict_types=1);

namespace Swp\RefundSystemSix\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1576078490RefundSystem extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1576078490;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
                CREATE TABLE IF NOT EXISTS`refund_system` (
              `id` binary(16) NOT NULL,
              `active` tinyint(1) NOT NULL DEFAULT \'0\',
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $value = "INSERT INTO refund_system (id, active, created_at) VALUES
                    ('".md5('Einweg')."', 1, '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".md5('Mehrweg')."', 1, '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".md5('Pfandfrei')."', 1, '".(date('Y-m-d H:i.s').'.000')."')
                    ON DUPLICATE KEY UPDATE active = active";
        //$connection->executeStatement($value);

        $connection->executeStatement("
                CREATE TABLE  IF NOT EXISTS  `refund_system_translation` (
              `refund_system_id` binary(16) NOT NULL,
              `language_id` binary(16) NOT NULL,
              `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `description` longtext COLLATE utf8mb4_unicode_ci,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`refund_system_id`,`language_id`),
              KEY `fk.refund_system_translation.language_id` (`language_id`),
              CONSTRAINT `fk.refund_system_translation.refund_system_id` FOREIGN KEY (`refund_system_id`) REFERENCES `refund_system` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.refund_system_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $value = "INSERT INTO refund_system_translation (refund_system_id, language_id, `name`, description, created_at) VALUES
                    ('".md5('Einweg')."', (SLECT id FROM language WHERE name = 'Deutsch'), 'EINWEG',  'Einweg', '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".md5('Mehrweg')."', (SLECT id FROM language WHERE name = 'Deutsch'), 'MEHRWEG' , 'Mehrweg', '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".md5('Pfandfrei')."', (SLECT id FROM language WHERE name = 'Deutsch'), 'PFANDFREI', 'Pfandfrei', '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".md5('Einweg')."', (SLECT id FROM language WHERE name = 'English'), 'ONE-WAY',  'One-way-Refund', '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".md5('Mehrweg')."', (SLECT id FROM language WHERE name = 'English'), 'REUSABLE' , 'Reusable-Refund', '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".md5('Pfandfrei')."', (SLECT id FROM language WHERE name = 'English'), 'DEPOSIT FREE', 'Deposit free', '".(date('Y-m-d H:i.s').'.000')."')
                    ON DUPLICATE KEY UPDATE description = description";
        //$connection->executeStatement($value);

    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
