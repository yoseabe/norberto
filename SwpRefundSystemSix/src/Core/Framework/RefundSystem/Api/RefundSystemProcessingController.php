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
 * @license    http://www.module-factory.com/eula
 */

namespace Swp\RefundSystemSix\Core\Framework\RefundSystem\Api;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class RefundSystemProcessingController extends AbstractController
{
    private EntityRepository $languageRepository;

    private EntityRepository $categoryRepository;

    private EntityRepository $productRepository;

    private EntityRepository $customFieldRepository;

    public function __construct(
        EntityRepository $languageRepository,
        EntityRepository $categoryRepository,
        EntityRepository $productRepository,
        EntityRepository $customFieldRepository
    ) {
        $this->languageRepository = $languageRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->customFieldRepository = $customFieldRepository;
    }

    #[Route(path: '/api/_action/refundsystemtoproducts/processing', name: 'api.action.processing.refundsystem', methods: ['POST'])]
    public function RefundSystemProcessingProducts(Request $post): JsonResponse
    {
        $categoryId = $post->request->getAlnum('categoryId');
        $fieldset = $post->request->getString('fieldset');
        $productIds = [];

        try {
            if ('' !== $categoryId && '' !== $fieldset) {
                $languages = $this->languageRepository->search(new Criteria(), Context::createDefaultContext())->getEntities()->getElements();

                $languageChains = $this->fetchLanguageChains($languages);

                foreach ($languageChains as $chain) {
                    $context = new Context(new SystemSource(), [], Defaults::CURRENCY, $chain);

                    $productIds = $this->getProductIds($categoryId, $context);

                    if ($productIds) {
                        $customFields = $this->getCustomFields($fieldset, $context);
                        $mainCategory = $this->getMainCategory($categoryId, $context);

                        if (!$mainCategory instanceof CategoryEntity) {
                            return new JsonResponse(['valid' => false]);
                        }

                        $newPath = $this->buildNewPath($mainCategory);
                        $childIds = $this->getChildCategoryIds($newPath, $context);

                        $filterarray = [];
                        if (is_iterable($mainCategory->getCustomFields())) {
                            foreach ($mainCategory->getCustomFields() as $customFieldKey => $customFieldValue) {
                                if (\in_array($customFieldKey, $customFields)) {
                                    $filterarray[$customFieldKey] = $customFieldValue;
                                }
                            }

                            $this->writeFieldsToCategory($childIds, $filterarray, $context);
                            $this->writeFieldsToProducts($productIds, $filterarray, $context);
                        }
                    }
                }
            } else {
                return new JsonResponse(['valid' => false]);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'valid' => false,
                'error' => $e->getTraceAsString(),
            ]);
        }

        return new JsonResponse([
            'valid' => true,
            'count' => count($productIds),
        ]);
    }

    private function fetchLanguageChains(array $languages): array
    {
        $languageChains = [];
        foreach ($languages as $language) {
            $languageId = $language->getId();
            $languageChains[$languageId] = array_filter([
                $languageId,
                $language->getParentId(),
                Defaults::LANGUAGE_SYSTEM,
            ]);
        }

        return $languageChains;
    }

    private function getCustomFields(
        string $fieldset,
        Context $context
    ): array {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFieldSet.name', $fieldset));

        $fields = $this->customFieldRepository->search(
            $criteria,
            $context
        )->getElements();

        return array_column($fields, 'name');
    }

    private function getProductIds(
        string $categoryId,
        Context $context
    ): array {
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('categoryTree', $categoryId));

        return $this->productRepository->searchIds(
            $criteria,
            $context
        )->getIds();
    }

    private function getMainCategory(
        string $categoryId,
        Context $context
    ): ?CategoryEntity {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $categoryId));

        $categoryCollection = $this->categoryRepository->search(
            $criteria,
            $context
        );

        /** @var CategoryEntity|null $category */
        $category = $categoryCollection->first();

        return $category;
    }

    private function buildNewPath(
        CategoryEntity $mainCategory
    ): string {
        $path = $mainCategory->getPath();

        if ('string' !== gettype($path)) {
            $path = '';
        }

        $pathArray = array_filter(explode('|', $path));

        $pathArray[] = $mainCategory->getId();

        $newPath = implode('|', $pathArray);

        return '|'.$newPath.'|';
    }

    private function getChildCategoryIds(
        string $newPath,
        Context $context
    ): array {
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('path', $newPath));

        return $this->categoryRepository->search(
            $criteria,
            $context
        )->getIds();
    }

    private function writeFieldsToProducts(
        array $productIds,
        array $fields,
        Context $context
    ): void {
        $data = $this->prepareWriteDataToProducts($productIds, $fields);

        $this->productRepository->upsert(
            $data,
            $context
        );
    }

    private function prepareWriteDataToProducts(
        array $productIds,
        array $fields
    ): array {
        $data = [];
        foreach ($productIds as $id) {
            $data[] = [
                'id' => $id,
                'customFields' => $fields,
            ];
        }

        return $data;
    }

    private function writeFieldsToCategory(
        array $childIds,
        array $fields,
        Context $context
    ): void {
        $data = $this->prepareWriteDataToCategory($childIds, $fields);

        $this->categoryRepository->upsert(
            $data,
            $context
        );
    }

    private function prepareWriteDataToCategory(
        array $childIds,
        array $fields
    ): array {
        $data = [];
        foreach ($childIds as $id) {
            $data[] = [
                'id' => $id,
                'customFields' => $fields,
            ];
        }

        return $data;
    }
}
