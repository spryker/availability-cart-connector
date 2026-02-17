<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\AvailabilityCartConnector\Business\Reader;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ProductAvailabilityCriteriaTransfer;
use Generated\Shared\Transfer\SellableItemRequestTransfer;
use Generated\Shared\Transfer\SellableItemsRequestTransfer;
use Generated\Shared\Transfer\SellableItemsResponseTransfer;
use Spryker\Zed\AvailabilityCartConnector\AvailabilityCartConnectorConfig;
use Spryker\Zed\AvailabilityCartConnector\Business\Calculator\ItemQuantityCalculatorInterface;
use Spryker\Zed\AvailabilityCartConnector\Dependency\Facade\AvailabilityCartConnectorToAvailabilityInterface;

class SellableItemsReader implements SellableItemsReaderInterface
{
    /**
     * @var array<string, \Generated\Shared\Transfer\SellableItemResponseTransfer>
     */
    protected static array $sellableItemsCache = [];

    /**
     * @var \Spryker\Zed\AvailabilityCartConnector\Business\Calculator\ItemQuantityCalculatorInterface
     */
    protected ItemQuantityCalculatorInterface $itemQuantityCalculator;

    /**
     * @var \Spryker\Zed\AvailabilityCartConnector\Dependency\Facade\AvailabilityCartConnectorToAvailabilityInterface
     */
    protected AvailabilityCartConnectorToAvailabilityInterface $availabilityFacade;

    /**
     * @var \Spryker\Zed\AvailabilityCartConnector\AvailabilityCartConnectorConfig
     */
    protected AvailabilityCartConnectorConfig $availabilityCartConnectorConfig;

    /**
     * @param \Spryker\Zed\AvailabilityCartConnector\Business\Calculator\ItemQuantityCalculatorInterface $itemQuantityCalculator
     * @param \Spryker\Zed\AvailabilityCartConnector\Dependency\Facade\AvailabilityCartConnectorToAvailabilityInterface $availabilityFacade
     * @param \Spryker\Zed\AvailabilityCartConnector\AvailabilityCartConnectorConfig $availabilityCartConnectorConfig
     */
    public function __construct(
        ItemQuantityCalculatorInterface $itemQuantityCalculator,
        AvailabilityCartConnectorToAvailabilityInterface $availabilityFacade,
        AvailabilityCartConnectorConfig $availabilityCartConnectorConfig
    ) {
        $this->itemQuantityCalculator = $itemQuantityCalculator;
        $this->availabilityFacade = $availabilityFacade;
        $this->availabilityCartConnectorConfig = $availabilityCartConnectorConfig;
    }

    public function getSellableItems(CartChangeTransfer $cartChangeTransfer, bool $skipItemsWithAmount = true): SellableItemsResponseTransfer
    {
        $sellableItemsRequestTransfer = $this->createSellableItemsRequestTransfer($cartChangeTransfer, $skipItemsWithAmount);
        $filteredSellableItemsRequestTransfer = $this->filterCachedRequests($sellableItemsRequestTransfer);
        $fetchedSellableItemsResponseTransfer = new SellableItemsResponseTransfer();

        if (count($filteredSellableItemsRequestTransfer->getSellableItemRequests()) > 0) {
            $fetchedSellableItemsResponseTransfer = $this->availabilityFacade->areProductsSellableForStore($filteredSellableItemsRequestTransfer);
        }

        return $this->expandSellableItemsResponseWithCachedSellableItemResponses($sellableItemsRequestTransfer, $fetchedSellableItemsResponseTransfer);
    }

    protected function createSellableItemsRequestTransfer(
        CartChangeTransfer $cartChangeTransfer,
        bool $skipItemsWithAmount = true
    ): SellableItemsRequestTransfer {
        $cartChangeTransfer->getQuote()->requireStore();
        $storeTransfer = $cartChangeTransfer->getQuoteOrFail()->getStoreOrFail();

        $itemsInCart = clone $cartChangeTransfer->getQuote()->getItems();
        $sellableItemsRequestTransfer = (new SellableItemsRequestTransfer())->setStore($storeTransfer);
        foreach ($cartChangeTransfer->getItems() as $entityIdentifier => $itemTransfer) {
            if ($skipItemsWithAmount && $itemTransfer->getAmount() !== null) {
                continue;
            }

            $sellableItemRequestTransfer = new SellableItemRequestTransfer();
            $sellableItemRequestTransfer->setQuantity(
                $this->itemQuantityCalculator->calculateTotalItemQuantity($itemsInCart, $itemTransfer),
            );
            $sellableItemRequestTransfer->setProductAvailabilityCriteria(
                (new ProductAvailabilityCriteriaTransfer())
                    ->setEntityIdentifier($entityIdentifier)
                    ->fromArray($itemTransfer->toArray(), true),
            );
            $itemsInCart->append($itemTransfer);
            $sellableItemRequestTransfer->setSku($itemTransfer->getSku());
            $sellableItemsRequestTransfer->addSellableItemRequest($sellableItemRequestTransfer);
        }

        $sellableItemsRequestTransfer->setQuote($cartChangeTransfer->getQuoteOrFail());

        return $sellableItemsRequestTransfer;
    }

    protected function filterCachedRequests(SellableItemsRequestTransfer $sellableItemsRequestTransfer): SellableItemsRequestTransfer
    {
        if (!$this->availabilityCartConnectorConfig->isSellableItemsCacheEnabled()) {
            return $sellableItemsRequestTransfer;
        }

        $filteredSellableItemsRequestTransfer = (new SellableItemsRequestTransfer())
            ->setStore($sellableItemsRequestTransfer->getStoreOrFail())
            ->setQuote($sellableItemsRequestTransfer->getQuoteOrFail());

        foreach ($sellableItemsRequestTransfer->getSellableItemRequests() as $sellableItemRequestTransfer) {
            $cacheKey = $this->availabilityCartConnectorConfig->generateSellableItemsCacheKey($sellableItemRequestTransfer);

            if (isset(static::$sellableItemsCache[$cacheKey])) {
                continue;
            }

            $filteredSellableItemsRequestTransfer->addSellableItemRequest($sellableItemRequestTransfer);
        }

        return $filteredSellableItemsRequestTransfer;
    }

    protected function expandSellableItemsResponseWithCachedSellableItemResponses(
        SellableItemsRequestTransfer $sellableItemsRequestTransfer,
        SellableItemsResponseTransfer $fetchedSellableItemsResponseTransfer
    ): SellableItemsResponseTransfer {
        if (!$this->availabilityCartConnectorConfig->isSellableItemsCacheEnabled()) {
            return $fetchedSellableItemsResponseTransfer;
        }

        $mergedSellableItemsResponseTransfer = new SellableItemsResponseTransfer();
        $sellableItemResponsesIndexedByEntityIdentifier = $this->getSellableItemResponsesIndexedByEntityIdentifier(
            $fetchedSellableItemsResponseTransfer,
        );

        foreach ($sellableItemsRequestTransfer->getSellableItemRequests() as $sellableItemRequestTransfer) {
            $cacheKey = $this->availabilityCartConnectorConfig->generateSellableItemsCacheKey($sellableItemRequestTransfer);

            if (isset(static::$sellableItemsCache[$cacheKey])) {
                $mergedSellableItemsResponseTransfer->addSellableItemResponse(static::$sellableItemsCache[$cacheKey]);

                continue;
            }

            $entityIdentifier = $sellableItemRequestTransfer->getProductAvailabilityCriteriaOrFail()->getEntityIdentifierOrFail();

            if (isset($sellableItemResponsesIndexedByEntityIdentifier[$entityIdentifier])) {
                $sellableItemResponseTransfer = $sellableItemResponsesIndexedByEntityIdentifier[$entityIdentifier];
                static::$sellableItemsCache[$cacheKey] = $sellableItemResponseTransfer;
                $mergedSellableItemsResponseTransfer->addSellableItemResponse($sellableItemResponseTransfer);
            }
        }

        return $mergedSellableItemsResponseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\SellableItemsResponseTransfer $sellableItemsResponseTransfer
     *
     * @return array<string, \Generated\Shared\Transfer\SellableItemResponseTransfer>
     */
    protected function getSellableItemResponsesIndexedByEntityIdentifier(
        SellableItemsResponseTransfer $sellableItemsResponseTransfer
    ): array {
        $sellableItemResponsesIndexedByEntityIdentifier = [];

        foreach ($sellableItemsResponseTransfer->getSellableItemResponses() as $sellableItemResponseTransfer) {
            $entityIdentifier = $sellableItemResponseTransfer->getProductAvailabilityCriteriaOrFail()->getEntityIdentifierOrFail();
            $sellableItemResponsesIndexedByEntityIdentifier[$entityIdentifier] = $sellableItemResponseTransfer;
        }

        return $sellableItemResponsesIndexedByEntityIdentifier;
    }
}
