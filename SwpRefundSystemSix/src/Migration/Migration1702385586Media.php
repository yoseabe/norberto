<?php

declare(strict_types=1);

namespace Swp\RefundSystemSix\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Swp\RefundSystemSix\Core\RefundSystem\RefundSystemDefinition;

class Migration1702385586Media extends MigrationStep
{
    public const MEDIA_NAME = 'Refund System';

    public function getCreationTimestamp(): int
    {
        return 1702385586;
    }

    public function update(Connection $connection): void
    {
        $mediaFolderConfigIds = $this->getMediaFolderConfigIds($connection);

        $connection->executeStatement('
            INSERT INTO `media_folder`
                (`id`, `default_folder_id`, `name`, `media_folder_configuration_id`, `use_parent_configuration`, `created_at`)
            VALUES
                (:id, :defaultFolderId, :name, :mediaFolderConfigurationId, :useParentConfiguration, :createdAt)
            ON DUPLICATE KEY UPDATE `name` = `name`
            ', [
                'id' => Uuid::fromHexToBytes(\md5(self::MEDIA_NAME)),
                'defaultFolderId' => null,
                'name' => self::MEDIA_NAME,
                'mediaFolderConfigurationId' => null,
                'useParentConfiguration' => 0,
                'createdAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );

        $defaultFolderId = $this->getId($mediaFolderConfigIds, 'default_folder_id');

        $connection->executeStatement('
            INSERT INTO `media_default_folder`
                (`id`, `entity`, `created_at`)
            VALUES
                (:id, :entity, :createdAt)
            ON DUPLICATE KEY UPDATE `id` = :id
            ', [
                'id' => $defaultFolderId,
                'entity' => RefundSystemDefinition::ENTITY_NAME,
                'createdAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );

        $mediaFolderConfigurationId = $this->getId($mediaFolderConfigIds, 'media_folder_configuration_id');

        $connection->executeStatement('
            INSERT INTO `media_folder_configuration`
                (`id`, `thumbnail_quality`, `created_at`)
            VALUES
                (:id, :thumbnailQuality, :createdAt)
            ON DUPLICATE KEY UPDATE `id` = :id
            ', [
                'id' => $mediaFolderConfigurationId,
                'thumbnailQuality' => 80,
                'createdAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );

        foreach ($this->getThumbnailSizes($connection) as $thumbnailSize) {
            $connection->executeStatement('
                REPLACE INTO `media_folder_configuration_media_thumbnail_size` (`media_folder_configuration_id`, `media_thumbnail_size_id`)
                VALUES (:mediaFolderConfigurationId, :thumbnailSizeId)
                ', [
                    'mediaFolderConfigurationId' => $mediaFolderConfigurationId,
                    'thumbnailSizeId' => $thumbnailSize['id'],
                ]
            );
        }

        $connection->executeStatement('
            UPDATE `media_folder`
            SET `default_folder_id` = :defaultFolderId,
                `media_folder_configuration_id` = :mediaFolderConfigurationId
            WHERE `id` = :id
            ', [
                'defaultFolderId' => $defaultFolderId,
                'mediaFolderConfigurationId' => $mediaFolderConfigurationId,
                'id' => Uuid::fromHexToBytes(\md5(self::MEDIA_NAME)),
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    /**
     * @throws \Exception
     * @throws Exception
     */
    public static function getMediaFolderConfigIds(Connection $connection): array
    {
        $sql = 'SELECT `default_folder_id`, `media_folder_configuration_id` FROM `media_folder` WHERE `id` = :id';

        $result = $connection->executeQuery($sql,
            [
                'id' => Uuid::fromHexToBytes(\md5(self::MEDIA_NAME)),
            ]
        )->fetchAssociative();

        return $result ?: [];
    }

    private function getId(array $mediaFolderConfigIds, string $key): string
    {
        if (\array_key_exists($key, $mediaFolderConfigIds) && !empty($mediaFolderConfigIds[$key])) {
            return $mediaFolderConfigIds[$key];
        } else {
            return Uuid::randomBytes();
        }
    }

    /**
     * @throws \Exception
     * @throws Exception
     */
    private function getThumbnailSizes(Connection $connection): array
    {
        $thumbnailSizes = [
            ['width' => 280, 'height' => 280],
        ];

        $sql = 'SELECT `id` FROM `media_thumbnail_size` WHERE `width` = :width AND `height` = :height';
        foreach ($thumbnailSizes as $i => $thumbnailSize) {
            $id = $connection->executeQuery($sql,
                [
                    'width' => $thumbnailSize['width'],
                    'height' => $thumbnailSize['height'],
                ]
            )->fetchOne();

            if (!$id) {
                $id = Uuid::randomBytes();
                $connection->executeStatement('
                        INSERT INTO `media_thumbnail_size` (`id`, `width`, `height`, `created_at`)
                        VALUES (:id, :width, :height, :createdAt)
                    ', [
                    'id' => $id,
                    'width' => $thumbnailSize['width'],
                    'height' => $thumbnailSize['height'],
                    'createdAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]);
            }

            /* @var string $id */
            $thumbnailSizes[$i]['id'] = $id;
        }

        /* @var array */
        return $thumbnailSizes;
    }
}
