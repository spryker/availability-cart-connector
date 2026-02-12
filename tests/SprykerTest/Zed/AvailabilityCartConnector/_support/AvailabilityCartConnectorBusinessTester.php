<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\AvailabilityCartConnector;

use ArrayObject;
use Codeception\Actor;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\SellableItemResponseTransfer;
use Generated\Shared\Transfer\SellableItemsResponseTransfer;
use Generated\Shared\Transfer\StockProductTransfer;
use Generated\Shared\Transfer\StoreTransfer;

/**
 * Inherited Methods
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 * @method \Spryker\Zed\AvailabilityCartConnector\Business\AvailabilityCartConnectorFacadeInterface getFacade()
 *
 * @SuppressWarnings(PHPMD)
 */
class AvailabilityCartConnectorBusinessTester extends Actor
{
    use _generated\AvailabilityCartConnectorBusinessTesterActions;

    /**
     * @param \Generated\Shared\Transfer\StoreTransfer $storeTransfer
     * @param int $availableQuantity
     * @param bool $isNeverOutOfStock
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    public function createProductWithAvailabilityForStore(
        StoreTransfer $storeTransfer,
        int $availableQuantity,
        bool $isNeverOutOfStock = false
    ): ProductConcreteTransfer {
        $productConcreteTransfer = $this->haveFullProduct();

        $this->haveProductInStockForStore(
            $storeTransfer,
            [
                StockProductTransfer::SKU => $productConcreteTransfer->getSkuOrFail(),
                StockProductTransfer::IS_NEVER_OUT_OF_STOCK => $isNeverOutOfStock,
                StockProductTransfer::QUANTITY => $availableQuantity,
            ],
        );
        $this->haveAvailabilityConcrete($productConcreteTransfer->getSkuOrFail(), $storeTransfer, $availableQuantity);

        return $productConcreteTransfer;
    }

    /**
     * @param string $storeName
     * @param array<int, array<string, mixed>> $cartItems
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function createCartChangeTransfer(string $storeName, array $cartItems): CartChangeTransfer
    {
        $storeTransfer = (new StoreTransfer())->setName($storeName);

        $quoteTransfer = (new QuoteTransfer())
            ->setStore($storeTransfer)
            ->setItems(new ArrayObject());

        $items = new ArrayObject();

        foreach ($cartItems as $itemData) {
            $itemTransfer = (new ItemTransfer())
                ->setSku($itemData['sku'])
                ->setQuantity($itemData['quantity']);

            if (isset($itemData['amount'])) {
                $itemTransfer->setAmount($itemData['amount']);
            }

            $items[$itemData['entityIdentifier']] = $itemTransfer;
            $quoteTransfer->addItem(clone $itemTransfer);
        }

        $cartChangeTransfer = (new CartChangeTransfer())
            ->setQuote($quoteTransfer)
            ->setItems($items);

        return $cartChangeTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\SellableItemsResponseTransfer $result
     * @param array<int, array<string, mixed>> $cartItems
     * @param array<int, array<string, mixed>> $expectedItemsData
     *
     * @return void
     */
    public function assertSellableItemsMatchExpectedData(
        SellableItemsResponseTransfer $result,
        array $cartItems,
        array $expectedItemsData
    ): void {
        $this->assertCount(count($expectedItemsData), $result->getSellableItemResponses());

        $sellableItemResponsesIndexedByEntityIdentifier = $this->getSellableItemResponsesIndexedByEntityIdentifier($result);

        foreach ($expectedItemsData as $expectedData) {
            $entityIdentifier = $expectedData['entityIdentifier'] ?? null;

            if ($entityIdentifier === null) {
                $entityIdentifier = $this->getEntityIdentifierFromCartItemsByIndex($cartItems, $expectedItemsData, $expectedData);
            }

            $this->assertArrayHasKey($entityIdentifier, $sellableItemResponsesIndexedByEntityIdentifier);

            $sellableItemResponse = $sellableItemResponsesIndexedByEntityIdentifier[$entityIdentifier];

            $this->assertSellableItemResponseMatchesExpectedData($sellableItemResponse, $expectedData, $entityIdentifier);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $cartItems
     * @param array<int, array<string, mixed>> $expectedItemsData
     * @param array<string, mixed> $currentExpectedData
     *
     * @return string
     */
    protected function getEntityIdentifierFromCartItemsByIndex(
        array $cartItems,
        array $expectedItemsData,
        array $currentExpectedData
    ): string {
        $index = array_search($currentExpectedData, $expectedItemsData, true);

        return $cartItems[$index]['entityIdentifier'];
    }

    /**
     * @param \Generated\Shared\Transfer\SellableItemsResponseTransfer $result
     *
     * @return array<string, \Generated\Shared\Transfer\SellableItemResponseTransfer>
     */
    protected function getSellableItemResponsesIndexedByEntityIdentifier(SellableItemsResponseTransfer $result): array
    {
        $sellableItemResponsesIndexedByEntityIdentifier = [];

        foreach ($result->getSellableItemResponses() as $sellableItemResponse) {
            $entityIdentifier = $sellableItemResponse->getProductAvailabilityCriteriaOrFail()->getEntityIdentifierOrFail();
            $sellableItemResponsesIndexedByEntityIdentifier[$entityIdentifier] = $sellableItemResponse;
        }

        return $sellableItemResponsesIndexedByEntityIdentifier;
    }

    /**
     * @param \Generated\Shared\Transfer\SellableItemResponseTransfer $sellableItemResponse
     * @param array<string, mixed> $expectedData
     * @param string $entityIdentifier
     *
     * @return void
     */
    protected function assertSellableItemResponseMatchesExpectedData(
        SellableItemResponseTransfer $sellableItemResponse,
        array $expectedData,
        string $entityIdentifier
    ): void {
        $this->assertSame(
            $expectedData['availableQuantity'],
            $sellableItemResponse->getAvailableQuantity()->toFloat(),
            sprintf('Item with entity identifier %s should have available quantity %s', $entityIdentifier, $expectedData['availableQuantity']),
        );
        $this->assertSame(
            $expectedData['isNeverOutOfStock'],
            $sellableItemResponse->getIsNeverOutOfStock(),
            sprintf('Item with entity identifier %s should have isNeverOutOfStock %s', $entityIdentifier, $expectedData['isNeverOutOfStock'] ? 'true' : 'false'),
        );
    }
}
