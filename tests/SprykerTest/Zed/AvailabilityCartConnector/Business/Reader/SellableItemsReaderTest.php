<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\AvailabilityCartConnector\Business\Reader;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductAvailabilityCriteriaTransfer;
use Generated\Shared\Transfer\SellableItemResponseTransfer;
use Generated\Shared\Transfer\SellableItemsRequestTransfer;
use Generated\Shared\Transfer\SellableItemsResponseTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\AvailabilityCartConnector\Business\Calculator\ItemQuantityCalculatorInterface;
use Spryker\Zed\AvailabilityCartConnector\Business\Reader\SellableItemsReader;
use Spryker\Zed\AvailabilityCartConnector\Dependency\Facade\AvailabilityCartConnectorToAvailabilityInterface;
use SprykerTest\Zed\AvailabilityCartConnector\AvailabilityCartConnectorBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group AvailabilityCartConnector
 * @group Business
 * @group Reader
 * @group SellableItemsReaderTest
 * Add your own group annotations below this line
 */
class SellableItemsReaderTest extends Unit
{
    protected const string STORE_NAME = 'DE';

    protected const string ENTITY_IDENTIFIER_1 = 'entity-1';

    protected const string ENTITY_IDENTIFIER_2 = 'entity-2';

    protected const string ENTITY_IDENTIFIER_3 = 'entity-3';

    protected const string SKU_1 = 'SKU-001';

    protected const string SKU_2 = 'SKU-002';

    protected const string SKU_3 = 'SKU-003';

    protected AvailabilityCartConnectorBusinessTester $tester;

    /**
     * @dataProvider getSellableItemsDataProvider
     *
     * @param array<int, array<string, mixed>> $cartItems
     * @param array<string, array<string, mixed>> $cachedItems
     * @param array<string, array<string, mixed>> $facadeResponse
     * @param bool $shouldCallFacade
     * @param bool $skipItemsWithAmount
     * @param array<int, array<string, mixed>> $expectedItemsData
     *
     * @return void
     */
    public function testGetSellableItems(
        array $cartItems,
        array $cachedItems,
        array $facadeResponse,
        bool $shouldCallFacade,
        bool $skipItemsWithAmount,
        array $expectedItemsData
    ): void {
        $this->clearStaticCache();

        // Arrange
        $sellableItemsReader = $this->createSellableItemsReaderWithMocksAndCache($cachedItems, $facadeResponse, $shouldCallFacade);
        $cartChangeTransfer = $this->tester->createCartChangeTransfer(static::STORE_NAME, $cartItems);

        // Act
        $result = $sellableItemsReader->getSellableItems($cartChangeTransfer, $skipItemsWithAmount);

        // Assert
        $this->tester->assertSellableItemsMatchExpectedData($result, $cartItems, $expectedItemsData);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getSellableItemsDataProvider(): array
    {
        return [
            'all items cached - facade should not be called' => [
                'cartItems' => [
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'sku' => static::SKU_1, 'quantity' => 5],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_2, 'sku' => static::SKU_2, 'quantity' => 10],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'sku' => static::SKU_3, 'quantity' => 3],
                ],
                'cachedItems' => [
                    static::SKU_1 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'quantity' => 5, 'availableQuantity' => 100.0, 'isNeverOutOfStock' => false],
                    static::SKU_2 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_2, 'quantity' => 10, 'availableQuantity' => 50.0, 'isNeverOutOfStock' => true],
                    static::SKU_3 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'quantity' => 3, 'availableQuantity' => 20.0, 'isNeverOutOfStock' => false],
                ],
                'facadeResponse' => [],
                'shouldCallFacade' => false,
                'skipItemsWithAmount' => false,
                'expectedItemsData' => [
                    ['availableQuantity' => 100.0, 'isNeverOutOfStock' => false],
                    ['availableQuantity' => 50.0, 'isNeverOutOfStock' => true],
                    ['availableQuantity' => 20.0, 'isNeverOutOfStock' => false],
                ],
            ],
            'no items cached - facade should be called with all items' => [
                'cartItems' => [
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'sku' => static::SKU_1, 'quantity' => 2],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_2, 'sku' => static::SKU_2, 'quantity' => 4],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'sku' => static::SKU_3, 'quantity' => 1],
                ],
                'cachedItems' => [],
                'facadeResponse' => [
                    static::SKU_1 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'availableQuantity' => 15.0, 'isNeverOutOfStock' => false],
                    static::SKU_2 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_2, 'availableQuantity' => 25.0, 'isNeverOutOfStock' => true],
                    static::SKU_3 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'availableQuantity' => 5.0, 'isNeverOutOfStock' => false],
                ],
                'shouldCallFacade' => true,
                'skipItemsWithAmount' => false,
                'expectedItemsData' => [
                    ['availableQuantity' => 15.0, 'isNeverOutOfStock' => false],
                    ['availableQuantity' => 25.0, 'isNeverOutOfStock' => true],
                    ['availableQuantity' => 5.0, 'isNeverOutOfStock' => false],
                ],
            ],
            'mixed - some cached some not - facade called only for uncached items' => [
                'cartItems' => [
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'sku' => static::SKU_1, 'quantity' => 7],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_2, 'sku' => static::SKU_2, 'quantity' => 3],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'sku' => static::SKU_3, 'quantity' => 12],
                ],
                'cachedItems' => [
                    static::SKU_1 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'quantity' => 7, 'availableQuantity' => 60.0, 'isNeverOutOfStock' => true],
                ],
                'facadeResponse' => [
                    static::SKU_2 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_2, 'availableQuantity' => 8.0, 'isNeverOutOfStock' => false],
                    static::SKU_3 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'availableQuantity' => 30.0, 'isNeverOutOfStock' => false],
                ],
                'shouldCallFacade' => true,
                'skipItemsWithAmount' => false,
                'expectedItemsData' => [
                    ['availableQuantity' => 60.0, 'isNeverOutOfStock' => true],
                    ['availableQuantity' => 8.0, 'isNeverOutOfStock' => false],
                    ['availableQuantity' => 30.0, 'isNeverOutOfStock' => false],
                ],
            ],
            'different quantities for same SKU - facade called for each unique quantity' => [
                'cartItems' => [
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'sku' => static::SKU_1, 'quantity' => 1],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_2, 'sku' => static::SKU_1, 'quantity' => 5],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'sku' => static::SKU_1, 'quantity' => 10],
                ],
                'cachedItems' => [],
                'facadeResponse' => [
                    static::SKU_1 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'availableQuantity' => 50.0, 'isNeverOutOfStock' => false],
                ],
                'shouldCallFacade' => true,
                'skipItemsWithAmount' => false,
                'expectedItemsData' => [
                    ['availableQuantity' => 50.0, 'isNeverOutOfStock' => false],
                    ['availableQuantity' => 50.0, 'isNeverOutOfStock' => false],
                    ['availableQuantity' => 50.0, 'isNeverOutOfStock' => false],
                ],
            ],
            'items with amount are skipped when skipItemsWithAmount is true' => [
                'cartItems' => [
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'sku' => static::SKU_1, 'quantity' => 5, 'amount' => null],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_2, 'sku' => static::SKU_2, 'quantity' => 10, 'amount' => 100],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'sku' => static::SKU_3, 'quantity' => 3, 'amount' => null],
                ],
                'cachedItems' => [],
                'facadeResponse' => [
                    static::SKU_1 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'availableQuantity' => 100.0, 'isNeverOutOfStock' => false],
                    static::SKU_3 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'availableQuantity' => 20.0, 'isNeverOutOfStock' => false],
                ],
                'shouldCallFacade' => true,
                'skipItemsWithAmount' => true,
                'expectedItemsData' => [
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'availableQuantity' => 100.0, 'isNeverOutOfStock' => false],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'availableQuantity' => 20.0, 'isNeverOutOfStock' => false],
                ],
            ],
            'items with amount are processed when skipItemsWithAmount is false' => [
                'cartItems' => [
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'sku' => static::SKU_1, 'quantity' => 5, 'amount' => null],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_2, 'sku' => static::SKU_2, 'quantity' => 10, 'amount' => 100],
                    ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'sku' => static::SKU_3, 'quantity' => 3, 'amount' => 200],
                ],
                'cachedItems' => [],
                'facadeResponse' => [
                    static::SKU_1 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_1, 'availableQuantity' => 100.0, 'isNeverOutOfStock' => false],
                    static::SKU_2 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_2, 'availableQuantity' => 50.0, 'isNeverOutOfStock' => true],
                    static::SKU_3 => ['entityIdentifier' => static::ENTITY_IDENTIFIER_3, 'availableQuantity' => 20.0, 'isNeverOutOfStock' => false],
                ],
                'shouldCallFacade' => true,
                'skipItemsWithAmount' => false,
                'expectedItemsData' => [
                    ['availableQuantity' => 100.0, 'isNeverOutOfStock' => false],
                    ['availableQuantity' => 50.0, 'isNeverOutOfStock' => true],
                    ['availableQuantity' => 20.0, 'isNeverOutOfStock' => false],
                ],
            ],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $cachedItems
     * @param array<string, array<string, mixed>> $facadeResponse
     * @param bool $shouldCallFacade
     *
     * @return \Spryker\Zed\AvailabilityCartConnector\Business\Reader\SellableItemsReader
     */
    protected function createSellableItemsReaderWithMocksAndCache(
        array $cachedItems,
        array $facadeResponse,
        bool $shouldCallFacade
    ): SellableItemsReader {
        $availabilityFacadeMock = $this->createAvailabilityFacadeMock($facadeResponse, $shouldCallFacade);

        return $this->createSellableItemsReader($availabilityFacadeMock, $cachedItems);
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $availabilityFacadeMock
     * @param array<string, array<string, mixed>> $cachedItems
     *
     * @return \Spryker\Zed\AvailabilityCartConnector\Business\Reader\SellableItemsReader
     */
    protected function createSellableItemsReader(
        MockObject $availabilityFacadeMock,
        array $cachedItems
    ): SellableItemsReader {
        $sellableItemsReader = new SellableItemsReader(
            $this->createItemQuantityCalculatorMock(),
            $availabilityFacadeMock,
        );

        if (count($cachedItems) === 0) {
            return $sellableItemsReader;
        }

        $this->setCacheItems($sellableItemsReader, $cachedItems);

        return $sellableItemsReader;
    }

    /**
     * @param \Spryker\Zed\AvailabilityCartConnector\Business\Reader\SellableItemsReader $sellableItemsReader
     * @param array<string, array<string, mixed>> $cachedItems
     *
     * @return void
     */
    protected function setCacheItems(SellableItemsReader $sellableItemsReader, array $cachedItems): void
    {
        $cache = $this->buildCacheFromItems($cachedItems);

        $reflection = new ReflectionClass(SellableItemsReader::class);
        $property = $reflection->getProperty('sellableItemsCache');
        $property->setAccessible(true);
        $property->setValue(null, $cache);
    }

    /**
     * @param array<string, array<string, mixed>> $cachedItems
     *
     * @return array<string, \Generated\Shared\Transfer\SellableItemResponseTransfer>
     */
    protected function buildCacheFromItems(array $cachedItems): array
    {
        $cache = [];

        foreach ($cachedItems as $sku => $data) {
            $quantity = new Decimal($data['quantity']);
            $cacheKey = sprintf('%s-%s-%s', $sku, static::STORE_NAME, $quantity->toString());

            $productAvailabilityCriteriaTransfer = (new ProductAvailabilityCriteriaTransfer())
                ->setEntityIdentifier($data['entityIdentifier']);

            $sellableItemResponseTransfer = (new SellableItemResponseTransfer())
                ->setSku($sku)
                ->setAvailableQuantity(new Decimal($data['availableQuantity']))
                ->setIsNeverOutOfStock($data['isNeverOutOfStock'])
                ->setProductAvailabilityCriteria($productAvailabilityCriteriaTransfer);

            $cache[$cacheKey] = $sellableItemResponseTransfer;
        }

        return $cache;
    }

    /**
     * @param array<string, array<string, mixed>> $facadeResponse
     * @param bool $shouldCallFacade
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\AvailabilityCartConnector\Dependency\Facade\AvailabilityCartConnectorToAvailabilityInterface
     */
    protected function createAvailabilityFacadeMock(
        array $facadeResponse,
        bool $shouldCallFacade
    ): AvailabilityCartConnectorToAvailabilityInterface {
        $availabilityFacadeMock = $this->createMock(AvailabilityCartConnectorToAvailabilityInterface::class);

        if (!$shouldCallFacade) {
            $this->configureAvailabilityFacadeMockToNeverBeCalled($availabilityFacadeMock);

            return $availabilityFacadeMock;
        }

        $this->configureAvailabilityFacadeMockToReturnResponse($availabilityFacadeMock, $facadeResponse);

        return $availabilityFacadeMock;
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $availabilityFacadeMock
     * @param array<string, array<string, mixed>> $facadeResponse
     *
     * @return void
     */
    protected function configureAvailabilityFacadeMockToReturnResponse(
        MockObject $availabilityFacadeMock,
        array $facadeResponse
    ): void {
        $availabilityFacadeMock
            ->expects($this->once())
            ->method('areProductsSellableForStore')
            ->willReturnCallback(function (SellableItemsRequestTransfer $request) use ($facadeResponse) {
                return $this->buildSellableItemsResponseFromRequest($request, $facadeResponse);
            });
    }

    /**
     * @param \Generated\Shared\Transfer\SellableItemsRequestTransfer $request
     * @param array<string, array<string, mixed>> $facadeResponse
     *
     * @return \Generated\Shared\Transfer\SellableItemsResponseTransfer
     */
    protected function buildSellableItemsResponseFromRequest(
        SellableItemsRequestTransfer $request,
        array $facadeResponse
    ): SellableItemsResponseTransfer {
        $sellableItemsResponseTransfer = new SellableItemsResponseTransfer();

        foreach ($request->getSellableItemRequests() as $sellableItemRequestTransfer) {
            $sku = $sellableItemRequestTransfer->getSkuOrFail();

            if (!isset($facadeResponse[$sku])) {
                continue;
            }

            $responseData = $facadeResponse[$sku];
            $productAvailabilityCriteriaTransfer = $sellableItemRequestTransfer->getProductAvailabilityCriteriaOrFail();

            $sellableItemResponseTransfer = (new SellableItemResponseTransfer())
                ->setSku($sku)
                ->setAvailableQuantity(new Decimal($responseData['availableQuantity']))
                ->setIsNeverOutOfStock($responseData['isNeverOutOfStock'])
                ->setProductAvailabilityCriteria($productAvailabilityCriteriaTransfer);

            $sellableItemsResponseTransfer->addSellableItemResponse($sellableItemResponseTransfer);
        }

        return $sellableItemsResponseTransfer;
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $availabilityFacadeMock
     *
     * @return void
     */
    protected function configureAvailabilityFacadeMockToNeverBeCalled(MockObject $availabilityFacadeMock): void
    {
        $availabilityFacadeMock
            ->expects($this->never())
            ->method('areProductsSellableForStore');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\AvailabilityCartConnector\Business\Calculator\ItemQuantityCalculatorInterface
     */
    protected function createItemQuantityCalculatorMock(): ItemQuantityCalculatorInterface
    {
        $itemQuantityCalculatorMock = $this->createMock(ItemQuantityCalculatorInterface::class);

        $itemQuantityCalculatorMock
            ->method('calculateTotalItemQuantity')
            ->willReturnCallback(function ($itemsInCart, $itemTransfer) {
                return new Decimal($itemTransfer->getQuantity());
            });

        return $itemQuantityCalculatorMock;
    }

    /**
     * @return void
     */
    protected function clearStaticCache(): void
    {
        $reflection = new ReflectionClass(SellableItemsReader::class);
        $property = $reflection->getProperty('sellableItemsCache');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}
