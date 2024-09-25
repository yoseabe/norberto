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

namespace Swp\RefundSystemSix\Core\RefundSystem;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swp\RefundSystemSix\Core\RefundSystem\Aggregate\RefundSystemTranslation\RefundSystemTranslationDefinition;

class RefundSystemDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'refund_system';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return RefundSystemEntity::class;
    }

    public function getCollectionClass(): string
    {
        return RefundSystemCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new TranslatedField('name'),

            (new FkField('media_id', 'mediaId', MediaDefinition::class))->addFlags(new ApiAware()),

            new BoolField('active', 'active'),
            new TranslatedField('description'),
            (new TranslationsAssociationField(RefundSystemTranslationDefinition::class, 'refund_system_id'))->addFlags(new Required()),

            (new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),
        ]);
    }
}
