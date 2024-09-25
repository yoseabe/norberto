<?php declare(strict_types=1);
/**
 * Shopware
 * Copyright © 2020
 *
 * @category   Shopware
 * @package    SwpRefundSystemSix
 * @subpackage RefundSystemCollection.php
 *
 * @copyright  2020 Iguana-Labs GmbH
 * @author     Module Factory <info at module-factory.com>
 * @license    https://www.module-factory.com/eula
 */

namespace Swp\RefundSystemSix\Core\RefundSystem\Aggregate\RefundSystemTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                    add(RefundSystemTranslationEntity $entity)
 * @method void                    set(string $key, RefundSystemTranslationEntity $entity)
 * @method RefundSystemTranslationEntity[]    getIterator()
 * @method RefundSystemTranslationEntity[]    getElements()
 * @method RefundSystemTranslationEntity|null get(string $key)
 * @method RefundSystemTranslationEntity|null first()
 * @method RefundSystemTranslationEntity|null last()
 */
class RefundSystemTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return RefundSystemTranslationEntity::class;
    }
}
