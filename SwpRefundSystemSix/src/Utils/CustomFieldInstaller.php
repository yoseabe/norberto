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

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomFieldInstaller
{
    public const REFUNDSYSTEM_SET = 'refund_system';
    public const REFUNDSYSTEM = 'refund_system';
    public const REFUNDSYSTEMPRICE = 'refund_system_price';

    public const REFUNDSYSTEM_PRODUCT = 'refund_system_to_product';
    public const REFUNDSYSTEM_CATEGORY = 'refund_system_to_category';

    private EntityRepository $customFieldRepository;

    private EntityRepository $customFieldSetRepository;

    public function __construct(ContainerInterface $container)
    {
        $this->customFieldSetRepository = $container->get('custom_field_set.repository');
        $this->customFieldRepository = $container->get('custom_field.repository');
    }

    /**
     * @return array[]
     */
    private function getFieldSet()
    {
        return [
            [
                'id' => md5(self::REFUNDSYSTEM_SET),
                'name' => self::REFUNDSYSTEM_SET,
                'config' => [
                    'label' => [
                        'de-DE' => 'Pfand',
                        'en-GB' => 'Refund',
                    ],
                    'translated' => true,
                ],
                'relations' => [
                    [
                        'id' => md5(self::REFUNDSYSTEM_PRODUCT),
                        'entityName' => 'product',
                    ],
                    [
                        'id' => md5(self::REFUNDSYSTEM_CATEGORY),
                        'entityName' => 'category',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array[]
     */
    private function getCustomFields()
    {
        return [
            [
                'id' => md5(self::REFUNDSYSTEM),
                'name' => self::REFUNDSYSTEM,
                'type' => CustomFieldTypes::SELECT,
                'customFieldSetId' => md5(self::REFUNDSYSTEM_SET),
                'config' => [
                    'componentName' => 'sw-entity-single-select',
                    'customFieldType' => CustomFieldTypes::ENTITY, // 'select',
                    'entity' => 'refund_system',
                    'customFieldPosition' => 1,
                    'label' => [
                        'de-DE' => 'Pfandart',
                        'en-GB' => 'Refund-Type',
                    ],
                ],
            ],
            [
                'id' => md5(self::REFUNDSYSTEMPRICE),
                'name' => self::REFUNDSYSTEMPRICE,
                'type' => CustomFieldTypes::FLOAT,
                'customFieldSetId' => md5(self::REFUNDSYSTEM_SET),
                'config' => [
                    'label' => [
                        'de-DE' => 'Pfandpreis',
                        'en-GB' => 'Refund-Price',
                    ],
                ],
            ],
            [
                'id' => md5('swp_refund_price_back'),
                'name' => 'swp_refund_price_back',
                'type' => CustomFieldTypes::BOOL,
                'customFieldSetId' => md5(self::REFUNDSYSTEM_SET),
                'config' => [
                    'label' => [
                        'de-DE' => 'Rückgabe',
                        'en-GB' => 'CashBack',
                    ],
                ],
            ],
        ];
    }

    public function activate(Context $context): void
    {
        foreach ($this->getFieldSet() as $customFieldSet) {
            $this->upsertCustomFieldSet($customFieldSet, $context);
        }
        foreach ($this->getCustomFields() as $customField) {
            $this->upsertCustomField($customField, $context);
        }
    }

    public function deactivate(Context $context): void
    {
        foreach ($this->getFieldSet() as $customFieldSet) {
            $this->upsertCustomFieldSet($customFieldSet, $context, false);
        }
        foreach ($this->getCustomFields() as $customField) {
            $this->upsertCustomField($customField, $context, false);
        }
    }

    public function uninstall(Context $context): void
    {
        foreach ($this->getFieldSet() as $customFieldSet) {
            $this->deleteCustomFieldSet($customFieldSet, $context);
        }
        foreach ($this->getCustomFields() as $customField) {
            $this->deleteCustomField($customField, $context);
        }
    }

    private function upsertCustomFieldSet(array $customFieldSet, Context $context, bool $activate = true): void
    {
        $customFieldSet['active'] = $activate;

        $this->customFieldSetRepository->upsert([$customFieldSet], $context);
    }

    private function upsertCustomField(array $customField, Context $context, bool $activate = true): void
    {
        $customField['active'] = $activate;

        $this->customFieldRepository->upsert([$customField], $context);
    }

    private function deleteCustomFieldSet(array $customFieldSet, Context $context): void
    {
        $this->customFieldSetRepository->delete([$customFieldSet], $context);
    }

    private function deleteCustomField(array $customField, Context $context): void
    {
        $this->customFieldRepository->delete([$customField], $context);
    }
}
