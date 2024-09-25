<?php

declare(strict_types=1);
/**
 * Shopware
 * Copyright © 2021.
 *
 * @category   Shopware
 *
 * @copyright  2021 Iguana-Labs GmbH
 * @author     Module Factory <info at module-factory.com>
 * @license    https://www.module-factory.com/eula
 */

namespace Swp\RefundSystemSix\Core\Content\Product\SalesChannel\Price;

use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPrice;
use Shopware\Core\Content\Product\SalesChannel\Price\AbstractProductPriceCalculator;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Service\ResetInterface;

class ProductPriceCalculatorDecorator extends AbstractProductPriceCalculator implements ResetInterface
{
    private AbstractProductPriceCalculator $decorated;

    private SystemConfigService $configService;

    private EntityRepository $refundSystemRepository;

    /**
     * ProductPriceCalculatorDecorator constructor.
     */
    public function __construct(
        AbstractProductPriceCalculator $decorated,
        SystemConfigService $configService,
        EntityRepository $refundSystemRepository
    ) {
        $this->decorated = $decorated;
        $this->configService = $configService;
        $this->refundSystemRepository = $refundSystemRepository;
    }

    public function getDecorated(): AbstractProductPriceCalculator
    {
        return $this->decorated->getDecorated();
    }

    public function calculate(iterable $products, SalesChannelContext $context): void
    {
        /** @var SalesChannelProductEntity $product */
        foreach ($products as $product) {
            $customFields = $product->getTranslation('customFields');

            $refundSystem = $customFields['refund_system'] ?? false;

            if (!$refundSystem) {
                continue;
            }

            $refundSystemPrice = $customFields['refund_system_price'] ?? false;
            $swpRefundPriceBack = $customFields['swp_refund_price_back'] ?? false;

            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('id', $refundSystem));
            $criteria->addFilter(new EqualsFilter('active', true));

            $data = $this->refundSystemRepository->search(
                $criteria,
                $context->getContext()
            )->first();

            if ($this->configService->getBool('SwpRefundSystemSix.config.channelActive', $context->getSalesChannel()->getId())
                && $data
                && $swpRefundPriceBack
                && $refundSystemPrice
            ) {
                $this->setPrices(
                    $product,
                    $context,
                    $refundSystemPrice
                );
            }
        }

        $this->decorated->calculate($products, $context);
    }

    private function setPrices(
        SalesChannelProductEntity $product,
        SalesChannelContext $context,
        float $refundSystemPrice
    ): void {
        $productPrice = $product->getPrice();
        $currencyFactor = $context->getCurrency()->getFactor();

        $refundPrice = $refundSystemPrice * $currencyFactor * (-1);

        if ($productPrice instanceof PriceCollection) {
            foreach ($productPrice as $price) {
                $price->setGross($refundPrice);
                $price->setNet($refundPrice);
            }

            $cheapestPrice = new CheapestPrice();
            $cheapestPrice->setPrice($productPrice);
            $cheapestPrice->setHasRange(false);
            $cheapestPrice->setVariantId('');
            $product->setCheapestPrice($cheapestPrice);
        }

        if ($product->getPrices() instanceof ProductPriceCollection) {
            foreach ($product->getPrices()->getElements() as $productPriceEntity) {
                foreach ($productPriceEntity->getPrice()->getElements() as $price) {
                    $price->setGross($refundPrice);
                    $price->setNet($refundPrice);
                }
            }
        }
    }

    public function reset(): void
    {
    }
}
