<?php declare(strict_types=1);
/**
 * Shopware
 * Copyright © 2020
 *
 * @category   Shopware
 * @package    SwpRefundSystemSix
 * @subpackage RefundSystemDefinition.php
 *
 * @copyright  2020 Iguana-Labs GmbH
 * @author     Module Factory <info at module-factory.com>
 * @license    https://www.module-factory.com/eula
 */

namespace Swp\RefundSystemSix\Core\RefundSystem\Aggregate\RefundSystemTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swp\RefundSystemSix\Core\RefundSystem\RefundSystemDefinition;

class RefundSystemTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = "refund_system_translation";

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return RefundSystemTranslationEntity::class;
    }

    public function getCollectionClass(): string
    {
        return RefundSystemTranslationCollection::class;
    }

    public function getParentDefinitionClass(): string
    {
        return RefundSystemDefinition::class;
    }


    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([

            (new StringField('name', 'name'))->addFlags(new Required()),
            (new LongTextField('description', 'description'))->addFlags(new Required()),

        ]);
    }
}
