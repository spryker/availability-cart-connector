<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\AvailabilityCartConnector;

use Codeception\Actor;
use Generated\Shared\DataBuilder\CartChangeBuilder;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
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
 *
 * @SuppressWarnings(PHPMD)
 */
class AvailabilityCartConnectorCommunicationTester extends Actor
{
    use _generated\AvailabilityCartConnectorCommunicationTesterActions;

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
     * @param array<int, array<string, mixed>> $productsData
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function createCartChangeTransferWithProducts(array $productsData): CartChangeTransfer
    {
        $storeTransfer = $this->haveStore();
        $cartChangeTransfer = (new CartChangeBuilder())->withQuote([QuoteTransfer::STORE => $storeTransfer->toArray()])->build();

        foreach ($productsData as $productData) {
            $isNeverOutOfStock = $productData['isNeverOutOfStock'] ?? false;
            $productConcreteTransfer = $this->createProductWithAvailabilityForStore(
                $storeTransfer,
                $productData['availability'],
                $isNeverOutOfStock,
            );

            $cartChangeTransfer->addItem(
                (new ItemTransfer())
                    ->setSku($productConcreteTransfer->getSkuOrFail())
                    ->setQuantity($productData['quantity']),
            );
        }

        return $cartChangeTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $resultTransfer
     * @param array<int, array<string, mixed>> $expectedItemsData
     *
     * @return void
     */
    public function assertExpandedItemsMatchExpectedData(
        CartChangeTransfer $resultTransfer,
        array $expectedItemsData
    ): void {
        $this->assertCount(count($expectedItemsData), $resultTransfer->getItems());

        foreach ($expectedItemsData as $index => $expectedData) {
            $itemTransfer = $resultTransfer->getItems()->offsetGet($index);

            $this->assertSame(
                $expectedData['stockQuantity'],
                $itemTransfer->getStockQuantity(),
                sprintf('Item at index %d should have stock quantity %s', $index, $expectedData['stockQuantity']),
            );
            $this->assertSame(
                $expectedData['isNeverOutOfStock'],
                $itemTransfer->getIsNeverOutOfStock(),
                sprintf('Item at index %d should have isNeverOutOfStock %s', $index, $expectedData['isNeverOutOfStock'] ? 'true' : 'false'),
            );
        }
    }
}
