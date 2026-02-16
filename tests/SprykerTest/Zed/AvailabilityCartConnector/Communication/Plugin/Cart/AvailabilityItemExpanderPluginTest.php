<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\AvailabilityCartConnector\Communication\Plugin\Cart;

use Codeception\Test\Unit;
use ReflectionClass;
use Spryker\Zed\Availability\Communication\Plugin\Cart\ProductConcreteBatchAvailabilityStrategyPlugin;
use Spryker\Zed\AvailabilityCartConnector\Business\Reader\SellableItemsReader;
use Spryker\Zed\AvailabilityCartConnector\Communication\Plugin\Cart\AvailabilityItemExpanderPlugin;
use SprykerTest\Zed\AvailabilityCartConnector\AvailabilityCartConnectorCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group AvailabilityCartConnector
 * @group Communication
 * @group Plugin
 * @group Cart
 * @group AvailabilityItemExpanderPluginTest
 * Add your own group annotations below this line
 */
class AvailabilityItemExpanderPluginTest extends Unit
{
    /**
     * @uses \Spryker\Zed\Availability\AvailabilityDependencyProvider::PLUGINS_BATCH_AVAILABILITY_STRATEGY
     */
    protected const string PLUGINS_BATCH_AVAILABILITY_STRATEGY = 'PLUGINS_BATCH_AVAILABILITY_STRATEGY';

    protected AvailabilityCartConnectorCommunicationTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearStaticCache();
    }

    /**
     * @dataProvider expandItemsDataProvider
     *
     * @param array<int, array<string, mixed>> $productsData
     * @param array<int, array<string, mixed>> $expectedItemsData
     *
     * @return void
     */
    public function testExpandItems(array $productsData, array $expectedItemsData): void
    {
        $this->tester->setDependency(
            static::PLUGINS_BATCH_AVAILABILITY_STRATEGY,
            [new ProductConcreteBatchAvailabilityStrategyPlugin()],
        );

        // Arrange
        $cartChangeTransfer = $this->tester->createCartChangeTransferWithProducts($productsData);

        // Act
        $resultTransfer = (new AvailabilityItemExpanderPlugin())->expandItems($cartChangeTransfer);

        // Assert
        $this->tester->assertExpandedItemsMatchExpectedData($resultTransfer, $expectedItemsData);
    }

    /**
     * @return array<string, array<string, array<int, array<string, mixed>>>>
     */
    public function expandItemsDataProvider(): array
    {
        return [
            'single product with standard availability' => [
                'productsData' => [
                    ['availability' => 100, 'quantity' => 5],
                ],
                'expectedItemsData' => [
                    ['stockQuantity' => 100.0, 'isNeverOutOfStock' => false],
                ],
            ],
            'single product with zero availability' => [
                'productsData' => [
                    ['availability' => 0, 'quantity' => 1],
                ],
                'expectedItemsData' => [
                    ['stockQuantity' => 0.0, 'isNeverOutOfStock' => false],
                ],
            ],
            'single product with never out of stock' => [
                'productsData' => [
                    ['availability' => 999, 'quantity' => 100, 'isNeverOutOfStock' => true],
                ],
                'expectedItemsData' => [
                    ['stockQuantity' => 999.0, 'isNeverOutOfStock' => true],
                ],
            ],
            'multiple products with mixed availability' => [
                'productsData' => [
                    ['availability' => 50, 'quantity' => 2],
                    ['availability' => 0, 'quantity' => 1],
                    ['availability' => 999, 'quantity' => 10, 'isNeverOutOfStock' => true],
                ],
                'expectedItemsData' => [
                    ['stockQuantity' => 50.0, 'isNeverOutOfStock' => false],
                    ['stockQuantity' => 0.0, 'isNeverOutOfStock' => false],
                    ['stockQuantity' => 999.0, 'isNeverOutOfStock' => true],
                ],
            ],
        ];
    }

    protected function clearStaticCache(): void
    {
        $reflection = new ReflectionClass(SellableItemsReader::class);
        $property = $reflection->getProperty('sellableItemsCache');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}
