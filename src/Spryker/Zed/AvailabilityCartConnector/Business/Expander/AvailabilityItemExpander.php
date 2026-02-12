<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\AvailabilityCartConnector\Business\Expander;

use ArrayObject;
use Generated\Shared\Transfer\CartChangeTransfer;
use Spryker\Zed\AvailabilityCartConnector\Business\Reader\SellableItemsReaderInterface;

class AvailabilityItemExpander implements AvailabilityItemExpanderInterface
{
    public function __construct(
        protected SellableItemsReaderInterface $sellableItemsReader,
    ) {
    }

    public function expandItems(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer
    {
        $sellableItemsResponseTransfer = $this->sellableItemsReader->getSellableItems($cartChangeTransfer, false);
        $sellableItemResponsesIndexedByEntityIdentifier = $this->getSellableItemResponsesIndexedByEntityIdentifier($sellableItemsResponseTransfer->getSellableItemResponses());

        foreach ($cartChangeTransfer->getItems() as $entityIdentifier => $itemTransfer) {
            if (!isset($sellableItemResponsesIndexedByEntityIdentifier[$entityIdentifier])) {
                $itemTransfer->setStockQuantity(0);
                $itemTransfer->setIsNeverOutOfStock(false);

                continue;
            }

            $sellableItemResponseTransfer = $sellableItemResponsesIndexedByEntityIdentifier[$entityIdentifier];

            $itemTransfer->setStockQuantity($sellableItemResponseTransfer->getAvailableQuantity()?->toFloat() ?? 0);
            $itemTransfer->setIsNeverOutOfStock($sellableItemResponseTransfer->getIsNeverOutOfStock() ?? false);
        }

        return $cartChangeTransfer;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\SellableItemResponseTransfer> $sellableItemResponseTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\SellableItemResponseTransfer>
     */
    protected function getSellableItemResponsesIndexedByEntityIdentifier(ArrayObject $sellableItemResponseTransfers): array
    {
        $sellableItemResponsesIndexedByEntityIdentifier = [];

        foreach ($sellableItemResponseTransfers as $sellableItemResponseTransfer) {
            $productAvailabilityCriteriaTransfer = $sellableItemResponseTransfer->getProductAvailabilityCriteriaOrFail();
            $entityIdentifier = $productAvailabilityCriteriaTransfer->getEntityIdentifierOrFail();

            $sellableItemResponsesIndexedByEntityIdentifier[$entityIdentifier] = $sellableItemResponseTransfer;
        }

        return $sellableItemResponsesIndexedByEntityIdentifier;
    }
}
