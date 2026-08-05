<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\AvailabilityCartConnector\Communication\Plugin\Quote;

use Codeception\Test\Unit;
use ReflectionClass;
use Spryker\Zed\Availability\Communication\Plugin\Cart\ProductConcreteBatchAvailabilityStrategyPlugin;
use Spryker\Zed\AvailabilityCartConnector\Business\Reader\SellableItemsReader;
use Spryker\Zed\AvailabilityCartConnector\Communication\Plugin\Quote\AvailabilityLockedQuoteExpandBeforeCreatePlugin;
use SprykerTest\Zed\AvailabilityCartConnector\AvailabilityCartConnectorCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group AvailabilityCartConnector
 * @group Communication
 * @group Plugin
 * @group Quote
 * @group AvailabilityLockedQuoteExpandBeforeCreatePluginTest
 * Add your own group annotations below this line
 */
class AvailabilityLockedQuoteExpandBeforeCreatePluginTest extends Unit
{
    /**
     * @uses \Spryker\Zed\Availability\AvailabilityDependencyProvider::PLUGINS_BATCH_AVAILABILITY_STRATEGY
     */
    protected const string PLUGINS_BATCH_AVAILABILITY_STRATEGY = 'PLUGINS_BATCH_AVAILABILITY_STRATEGY';

    protected const string NOT_IMPORTED_PRODUCT_SKU = 'not-imported-product-sku';

    protected const float AVAILABLE_QUANTITY = 10.0;

    protected const float NEVER_OUT_OF_STOCK_QUANTITY = 999.0;

    protected const bool IS_NOT_LOCKED = false;

    protected AvailabilityCartConnectorCommunicationTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearStaticCache();
    }

    /**
     * A locked quote, e.g. a quote converted from a quote request, never goes through a cart operation,
     * so this plugin is the only place where its items receive availability data.
     *
     * @return void
     */
    public function testExpandsLockedQuoteItemsWithAvailabilityDataOfQuoteStore(): void
    {
        // Arrange
        $this->tester->setDependency(
            static::PLUGINS_BATCH_AVAILABILITY_STRATEGY,
            [new ProductConcreteBatchAvailabilityStrategyPlugin()],
        );
        $storeTransfer = $this->tester->haveStore();
        $quoteTransfer = $this->tester->createQuoteTransferWithItemSkus(
            [
                $this->tester->createProductWithAvailabilityForStore($storeTransfer, (int)static::AVAILABLE_QUANTITY)->getSkuOrFail(),
                $this->tester->createProductWithAvailabilityForStore($storeTransfer, (int)static::NEVER_OUT_OF_STOCK_QUANTITY, true)->getSkuOrFail(),
            ],
            $storeTransfer,
        );

        // Act
        $quoteTransfer = (new AvailabilityLockedQuoteExpandBeforeCreatePlugin())->expand($quoteTransfer);

        // Assert
        $this->tester->assertItemHasAvailabilityData(
            $quoteTransfer->getItems()->offsetGet(0),
            static::AVAILABLE_QUANTITY,
            false,
        );
        $this->tester->assertItemHasAvailabilityData(
            $quoteTransfer->getItems()->offsetGet(1),
            static::NEVER_OUT_OF_STOCK_QUANTITY,
            true,
        );
    }

    public function testReturnsNotLockedQuoteUnchanged(): void
    {
        // Arrange
        $this->tester->setDependency(
            static::PLUGINS_BATCH_AVAILABILITY_STRATEGY,
            [new ProductConcreteBatchAvailabilityStrategyPlugin()],
        );
        $storeTransfer = $this->tester->haveStore();
        $quoteTransfer = $this->tester->createQuoteTransferWithItemSkus(
            [$this->tester->createProductWithAvailabilityForStore($storeTransfer, (int)static::AVAILABLE_QUANTITY)->getSkuOrFail()],
            $storeTransfer,
            static::IS_NOT_LOCKED,
        );

        // Act
        $quoteTransfer = (new AvailabilityLockedQuoteExpandBeforeCreatePlugin())->expand($quoteTransfer);

        // Assert
        $this->assertNull($quoteTransfer->getItems()->offsetGet(0)->getStockQuantity());
        $this->assertNull($quoteTransfer->getItems()->offsetGet(0)->getIsNeverOutOfStock());
    }

    public function testReturnsQuoteWithoutStoreUnchanged(): void
    {
        // Arrange
        $quoteTransfer = $this->tester->createQuoteTransferWithItemSkus([static::NOT_IMPORTED_PRODUCT_SKU]);

        // Act
        $quoteTransfer = (new AvailabilityLockedQuoteExpandBeforeCreatePlugin())->expand($quoteTransfer);

        // Assert
        $this->assertNull($quoteTransfer->getItems()->offsetGet(0)->getStockQuantity());
        $this->assertNull($quoteTransfer->getItems()->offsetGet(0)->getIsNeverOutOfStock());
    }

    public function testExpandsItemWithZeroStockQuantityWhenAvailabilityDoesNotResolveIt(): void
    {
        // Arrange
        $this->tester->setDependency(static::PLUGINS_BATCH_AVAILABILITY_STRATEGY, []);
        $quoteTransfer = $this->tester->createQuoteTransferWithItemSkus(
            [static::NOT_IMPORTED_PRODUCT_SKU],
            $this->tester->haveStore(),
        );

        // Act
        $quoteTransfer = (new AvailabilityLockedQuoteExpandBeforeCreatePlugin())->expand($quoteTransfer);

        // Assert
        $this->tester->assertItemHasAvailabilityData($quoteTransfer->getItems()->offsetGet(0), 0.0, false);
    }

    protected function clearStaticCache(): void
    {
        $reflection = new ReflectionClass(SellableItemsReader::class);
        $property = $reflection->getProperty('sellableItemsCache');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}
