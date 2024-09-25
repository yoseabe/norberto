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

namespace Swp\RefundSystemSix\Storefront\Subscriber;

use Shopware\Core\Checkout\Cart\Price\CashRounding;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Content\Cms\SalesChannel\Struct\CrossSellingStruct;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductBoxStruct;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductSliderStruct;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Content\Product\Events\ProductSuggestResultEvent;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Swp\RefundSystemSix\Core\RefundSystem\RefundSystemEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Frontend implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    protected EntityRepository $RefundSystemRepository;

    /**
     * Frontend constructor.
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository $RefundSystemRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->RefundSystemRepository = $RefundSystemRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
            CmsPageLoadedEvent::class => 'onCmsPageLoaded',

            ProductListingResultEvent::class => 'onProductListingResult',
            ProductSuggestResultEvent::class => 'onSuggestLoaded',
            SearchPageLoadedEvent::class => 'onSearchPageLoaded',
        ];
    }

    public function onCmsPageLoaded(CmsPageLoadedEvent $event): void
    {
        if (!$this->isCannelActive($event->getSalesChannelContext())) {
            return;
        }

        /** @var CmsPageCollection $cms */
        $cms = $event->getResult();

        /** @var CmsPageEntity $cmsEntity */
        foreach ($cms->getIterator() as $cmsEntity) {
            // Product
            $productBoxes = $cmsEntity->getElementsOfType('product-box');

            /** @var CmsSlotEntity $productBox */
            foreach ($productBoxes as $productBox) {
                /** @var ProductBoxStruct $box */
                $box = $productBox->getData();

                $product = $box->getProduct();

                if ($product instanceof SalesChannelProductEntity) {
                    $refund = $this->productHasRefund($product, $event->getSalesChannelContext());

                    if (count($refund) > 0) {
                        $product->__set('hasRefund', $refund);
                    }
                }
            }

            // Slider
            $productBoxes = $cmsEntity->getElementsOfType('product-slider');
            /** @var CmsSlotEntity $productBox */
            foreach ($productBoxes as $productBox) {
                /** @var ProductSliderStruct $slider */
                $slider = $productBox->getData();

                $sliderProducts = $slider->getProducts();

                if (null === $sliderProducts) {
                    continue;
                }

                /** @var SalesChannelProductEntity $product */
                foreach ($sliderProducts->getElements() as $product) {
                    $refund = $this->productHasRefund($product, $event->getSalesChannelContext());

                    if (count($refund) > 0) {
                        $product->__set('hasRefund', $refund);
                    }
                }
            }

            // product-description-reviews
            $productBoxes = $cmsEntity->getElementsOfType('product-description-reviews');

            /** @var CmsSlotEntity $productBox */
            foreach ($productBoxes as $productBox) {
                /** @var ProductBoxStruct $box */
                $box = $productBox->getData();
                /** @var SalesChannelProductEntity $product */
                $product = $box->getProduct();

                $refund = $this->productHasRefund($product, $event->getSalesChannelContext());

                if (count($refund) > 0) {
                    $product->__set('hasRefund', $refund);
                }
            }

            // gallery-buybox
            $productBoxes = $cmsEntity->getElementsOfType('buy-box');

            /** @var CmsSlotEntity $productBox */
            foreach ($productBoxes as $productBox) {
                /** @var ProductBoxStruct $box */
                $box = $productBox->getData();
                /** @var SalesChannelProductEntity $product */
                $product = $box->getProduct();

                $refund = $this->productHasRefund($product, $event->getSalesChannelContext());

                if (count($refund) > 0) {
                    $product->__set('hasRefund', $refund);
                }
            }

            // CrossSelling
            $productBoxes = $cmsEntity->getElementsOfType('cross-selling');

            /** @var CmsSlotEntity $productBox */
            foreach ($productBoxes as $productBox) {
                if (!$productBox->getData() instanceof CrossSellingStruct) {
                    continue;
                }

                $crossSellings = $productBox->getData()->getCrossSellings();

                if (null === $crossSellings) {
                    continue;
                }

                foreach ($crossSellings->getElements() as $crossSelling) {
                    $products = $crossSelling->getProducts()->getElements();

                    /** @var SalesChannelProductEntity $product */
                    foreach ($products as $product) {
                        $refund = $this->productHasRefund($product, $event->getSalesChannelContext());

                        if (count($refund) > 0) {
                            $product->__set('hasRefund', $refund);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    public function onProductPageLoaded(ProductPageLoadedEvent $event)
    {
        if (!$this->isCannelActive($event->getSalesChannelContext())) {
            return;
        }
        $product = $event->getPage()->getProduct();

        $refund = $this->productHasRefund($product, $event->getSalesChannelContext());

        if (count($refund) > 0) {
            $product->__set('hasRefund', $refund);
        }
    }

    public function onSuggestLoaded(ProductSuggestResultEvent $event): void
    {
        if (!$this->isCannelActive($event->getSalesChannelContext())) {
            return;
        }

        $result = $event->getResult();
        $elements = $result->getElements();

        /** @var SalesChannelProductEntity $product */
        foreach ($elements as $id => &$product) {
            $refund = $this->productHasRefund($product, $event->getSalesChannelContext());
            if (count($refund) > 0) {
                $product->setTranslated(
                    [
                        'name' => $this->generateNewProductsName($product, $refund),
                    ]
                );
                $product->__set('hasRefund', $refund);
            }
        }
    }

    public function onSearchPageLoaded(SearchPageLoadedEvent $event): void
    {
        if (!$this->isCannelActive($event->getSalesChannelContext())) {
            return;
        }

        $products = $event->getPage()->getListing()->getElements();

        /** @var SalesChannelProductEntity $product */
        foreach ($products as $product) {
            $refund = $this->productHasRefund($product, $event->getSalesChannelContext());

            if (count($refund) > 0) {
                $product->__set('hasRefund', $refund);
            }
        }
    }

    public function onProductListingResult(ProductListingResultEvent $event): void
    {
        if (!$this->isCannelActive($event->getSalesChannelContext())) {
            return;
        }

        $result = $event->getResult();
        $elements = $result->getElements();

        /** @var SalesChannelProductEntity $product */
        foreach ($elements as &$product) {
            $refund = $this->productHasRefund($product, $event->getSalesChannelContext());

            if (count($refund) > 0) {
                $product->__set('hasRefund', $refund);
            }
        }
    }

    private function productHasRefund(
        SalesChannelProductEntity $product,
        SalesChannelContext $salesChannelContext
    ): array {
        $customFields = $product->getTranslation('customFields');

        if (!array_key_exists('refund_system', $customFields)) {
            return [];
        }

        $data = $this->RefundSystemRepository->search(
            new Criteria([$customFields['refund_system']]),
            $salesChannelContext->getContext()
        );

        $refundName = '';
        $active = false;

        /** @var RefundSystemEntity $val */
        foreach ($data->getElements() as $val) {
            $active = $val->isActive();
            $refundName = $val->getTranslation('name');
        }

        if (!$active) {
            return [];
        }

        if (isset($customFields['refund_system_price'])) {
            $return['price'] = $customFields['refund_system_price'];
            if (isset($customFields['swp_refund_price_back'])) {
                if (1 == $customFields['swp_refund_price_back']) {
                    $return['price'] = $return['price'] * (-1);
                }
            }
        } else {
            $return['price'] = 0;
        }

        $return['name'] = $refundName;
        $return['calculated_price'] = $this->calculateRefundPrice(
            $salesChannelContext, $return['price']
        );

        return $return;
    }

    private function generateNewProductsName(
        SalesChannelProductEntity $product,
        array $refund
    ): string {
        $productsName = $product->getTranslation('name');

        return $productsName.' - '.strtoupper($refund['name']);
    }

    private function calculateRefundPrice(
        SalesChannelContext $saleschannelContext,
        float $refundPrice
    ): float {
        $currencyFactor = $saleschannelContext->getContext()->getCurrencyFactor();

        return (new CashRounding())->cashRound(
            $refundPrice * $currencyFactor,
            $saleschannelContext->getItemRounding()
        );
    }

    private function isCannelActive(SalesChannelContext $context): bool
    {
        return $this->systemConfigService->getBool(
            'SwpRefundSystemSix.config.channelActive',
            $context->getSalesChannel()->getId()
        );
    }
}
