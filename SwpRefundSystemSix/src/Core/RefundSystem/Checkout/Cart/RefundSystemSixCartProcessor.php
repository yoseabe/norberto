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

namespace Swp\RefundSystemSix\Core\RefundSystem\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\CashRounding;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swp\RefundSystemSix\Core\RefundSystem\RefundSystemEntity;
use Symfony\Contracts\Translation\TranslatorInterface;

class RefundSystemSixCartProcessor implements CartProcessorInterface, CartDataCollectorInterface
{
    private SalesChannelRepository $salesChannelProductRepository;

    private SystemConfigService $systemConfigService;

    private EntityRepository $refundSystem;

    private TranslatorInterface $translator;

    private QuantityPriceCalculator $priceCalculator;

    /** @var array|null */
    private $products;

    public function __construct(
        SalesChannelRepository $salesChannelProductRepository,
        EntityRepository $refundSystem,
        QuantityPriceCalculator $calculator,
        SystemConfigService $systemConfigService,
        TranslatorInterface $translator
    ) {
        $this->salesChannelProductRepository = $salesChannelProductRepository;
        $this->refundSystem = $refundSystem;
        $this->priceCalculator = $calculator;
        $this->systemConfigService = $systemConfigService;
        $this->translator = $translator;
    }

    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $productItems = $original->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($productItems as $productItem) {
            if (!$productItem->getReferencedId()) {
                continue;
            }

            $product = $this->getRealProductData($productItem->getReferencedId(), $context);

            $customFields = $productItem->getPayloadValue('customFields');

            $customFields = array_merge($customFields, $product->getTranslation('customFields'));

            $productItem->setPayloadValue('customFields', $customFields);
        }
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        if (1 != $behavior->hasPermission('skipDeliveryPriceRecalculation')) {
            $cart = $original->getLineItems()->getElements();
            $cartPriceRefundTotal = [];

            foreach ($cart as $val) {
                foreach ($val->getChildren()->getElements() as $ival) {
                    if ('refund' == $ival->getType()) {
                        $val->getChildren()->removeElement($ival);
                    }
                }

                if ('refundTotal' == $val->getType() || 'refundTotal_' == $val->getType()) {
                    $original->getLineItems()->removeElement($val);
                    $toCalculate->getLineItems()->removeElement($val);
                }

                if (!$this->isCannelActive($context)) {
                    continue;
                }

                if (isset($val->getPayloadValue('customFields')['refund_system'])) {
                    $key = $val->getPayloadValue('customFields')['refund_system'];

                    $refundprice = 0;

                    if (isset($val->getPayloadValue('customFields')['refund_system_price'])) {
                        $refundprice = $val->getPayloadValue('customFields')['refund_system_price'];
                        if (isset($val->getPayloadValue('customFields')['swp_refund_price_back'])) {
                            if (1 == $val->getPayloadValue('customFields')['swp_refund_price_back']) {
                                continue;
                            }
                        }
                    }

                    $criteria = new Criteria([$key]);
                    $criteria->addAssociations(
                        ['media']
                    );

                    $refund = $this->refundSystem->search(
                        $criteria, $context->getContext()
                    );

                    $label = $active = false;

                    /** @var RefundSystemEntity $ival */
                    foreach ($refund->getElements() as $ival) {
                        $label = $ival->getTranslation('name');
                        $active = $ival->isActive();
                    }

                    if (!$label) {
                        continue;
                    }

                    if (!$active) {
                        continue;
                    }

                    $refundpriceTax = $this->calculateRefundPrice($refundprice, $context, $val);
                    $cartPriceRefundTotal = $this->calculateCartRefund($refundprice, $val->getQuantity(), $context, $val, $cartPriceRefundTotal);

                    $refundPriceDefinition = new QuantityPriceDefinition(
                        $refundpriceTax,
                        (null !== $val->getPrice()) ? $val->getPrice()->getTaxRules() : new TaxRuleCollection([]),
                        $val->getQuantity()
                    );

                    $price = $this->priceCalculator->calculate($refundPriceDefinition, $context);
                    $refundItem = new LineItem(
                        md5($val->getId()),
                        'refund',
                        $val->getId(),
                        $val->getQuantity()
                    );

                    $suffix = $this->translator->trans('refundsystem.general.additional');
                    $refundItem->setLabel($suffix.' '.$label);
                    $refundItem->setPriceDefinition($refundPriceDefinition);
                    $refundItem->setPrice($price);
                    $refundItem->setStackable(true);
                    $refundItem->setRemovable(true);

                    if (isset($ival) && $ival instanceof RefundSystemEntity) {
                        $media = $ival->getMedia();
                        $refundItem->setCover($media);
                    }

                    $refundItem->setPayloadValue('productNumber', 'RefundArticle');

                    if ($this->systemConfigService->getBool('SwpRefundSystemSix.config.showChildItems', $context->getSalesChannel()->getId())) {
                        $val->addChild(
                            $refundItem
                        );
                    }
                }
            }

            foreach ($cartPriceRefundTotal as $totalKey => $totalVal) {
                $refundTotalPriceDefinition = new QuantityPriceDefinition($totalVal['value'], $totalVal['rule'], 1);
                $priceTotal = $this->priceCalculator->calculate($refundTotalPriceDefinition, $context);

                $refundTotalItem = new LineItem(
                    md5('refundTotal_'.$totalKey),
                    'refundTotal',
                    md5('refundTotal_'.$totalKey),
                    1
                );

                $refundTotalItem->setLabel($this->translator->trans(
                    'refundsystem.general.pretotal').' '.$totalKey.' '.$this->translator->trans('refundsystem.general.posttotal')
                );
                $refundTotalItem->setPriceDefinition($refundTotalPriceDefinition);

                $refundTotalItem->setPrice($priceTotal);
                $refundTotalItem->setGood(false);
                $refundTotalItem->setStackable(true);
                $refundTotalItem->setRemovable(true);

                $toCalculate->add(
                    $refundTotalItem
                );
            }
        }
    }

    private function calculateCartRefund(
        float $price,
        int $quantity,
        SalesChannelContext $context,
        LineItem $product,
        array $cartTaxRools = []
    ): array {
        $price = $this->calculateRefundPrice($price, $context, $product);

        $taxRate = 0;
        if (null !== $product->getPrice()) {
            $tax = $product->getPrice()->getTaxRules()->getElements();

            foreach ($tax as $val) {
                $taxRate = $val->getTaxRate();
            }

            $cartTaxRools[$taxRate]['rule'] = $product->getPrice()->getTaxRules();
        }

        if (isset($cartTaxRools[$taxRate]['value'])) {
            $cartTaxRools[$taxRate]['value'] = $cartTaxRools[$taxRate]['value'] + $price * $quantity;
        } else {
            $cartTaxRools[$taxRate]['value'] = $price * $quantity;
        }

        return $cartTaxRools;
    }

    private function calculateRefundPrice(
        float $price,
        SalesChannelContext $context,
        LineItem $product
    ): float {
        $currencyFactor = $context->getContext()->getCurrencyFactor();

        $price = (new CashRounding())->cashRound(
            $price * $currencyFactor,
            $context->getItemRounding()
        );

        $taxRate = 0;
        if (null !== $product->getPrice()) {
            $tax = $product->getPrice()->getTaxRules()->getElements();

            foreach ($tax as $val) {
                $taxRate = $val->getTaxRate();
            }

            $customerTypeBrutto = $context->getCurrentCustomerGroup()->getDisplayGross();

            if (0 == $customerTypeBrutto) {
                // $price = $price / (1 + ($taxRate / 100));
            }
        }

        return (float) $price;
    }

    private function getRealProductData(
        string $id,
        SalesChannelContext $context
    ): SalesChannelProductEntity {
        $criteria = new Criteria([$id]);

        if (!isset($this->products[$id][$context->getLanguageId()])) {
            $this->products[$id][$context->getLanguageId()] = $this->salesChannelProductRepository->search(
                $criteria,
                $context
            )->get($id);
        }

        return $this->products[$id][$context->getLanguageId()];
    }

    /**
     * @return bool
     */
    private function isCannelActive(SalesChannelContext $event)
    {
        return $this->systemConfigService->getBool(
            'SwpRefundSystemSix.config.channelActive',
            $event->getSalesChannel()->getId()
        );
    }
}
