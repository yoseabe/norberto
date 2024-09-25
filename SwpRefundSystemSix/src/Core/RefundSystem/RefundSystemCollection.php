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

namespace Swp\RefundSystemSix\Core\RefundSystem;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                    add(RefundSystemEntity $entity)
 * @method void                    set(string $key, RefundSystemEntity $entity)
 * @method RefundSystemEntity[]    getIterator()
 * @method RefundSystemEntity[]    getElements()
 * @method RefundSystemEntity|null get(string $key)
 * @method RefundSystemEntity|null first()
 * @method RefundSystemEntity|null last()
 */
class RefundSystemCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return RefundSystemEntity::class;
    }
}
