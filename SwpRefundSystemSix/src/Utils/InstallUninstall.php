<?php

declare(strict_types=1);
/**
 * Shopware
 * Copyright © 2020.
 *
 * @category   Shopware
 *
 * @copyright  2020 Iguana-Labs GmbH
 * @author     Module Factory <info at module-factory.com>
 * @license    https://www.module-factory.com/eula
 */

namespace Swp\RefundSystemSix\Utils;

use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;

class InstallUninstall
{
    public const SYSTEM_CONFIG_DOMAIN = 'SwpRefundSystemSix.config.';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function install(Context $context): void
    {
    }

    public function uninstall(Context $context): void
    {
        $this->removeConfiguration($context);
        $this->removeDatabaseTables();

        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('DELETE FROM snippet WHERE translation_key LIKE "%refundsystem%" OR translation_key LIKE "%refund_system%"');
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function removeConfiguration(Context $context): void
    {
        /** @var EntityRepository $systemConfigRepository */
        $systemConfigRepository = $this->container->get('system_config.repository');

        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('configurationKey', self::SYSTEM_CONFIG_DOMAIN));
        $idSearchResult = $systemConfigRepository->searchIds($criteria, $context);

        $ids = array_map(static function ($id) {
            return ['id' => $id];
        }, $idSearchResult->getIds());

        $systemConfigRepository->delete($ids, $context);
    }

    private function removeDatabaseTables(): void
    {
        $connection = $this->container->get(Connection::class);

        try {
            $connection->executeStatement('DROP TABLE IF EXISTS `refund_system_translation`');
            $connection->executeStatement('DROP TABLE IF EXISTS `refund_system`');
        } catch (\Exception $e) { /* nothing */
        }
    }
}
