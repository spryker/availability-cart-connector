<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\AvailabilityCartConnector;

use Generated\Shared\Transfer\SellableItemRequestTransfer;
use Spryker\Zed\Kernel\AbstractBundleConfig;

class AvailabilityCartConnectorConfig extends AbstractBundleConfig
{
    /**
     * Specification:
     * - Determines whether sellable items cache is enabled.
     * - When disabled, availability checks are performed without caching.
     * - When enabled, availability results are cached in memory for the request lifecycle.
     *
     * @api
     *
     * @return bool
     */
    public function isSellableItemsCacheEnabled(): bool
    {
        return false;
    }

    /**
     * Specification:
     * - Generates a cache key for sellable items based on sellable item request.
     * - Uses MD5 hash of serialized request to ensure uniqueness.
     * - Can be overridden at project level to customize cache key generation logic.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SellableItemRequestTransfer $sellableItemRequestTransfer
     *
     * @return string
     */
    public function generateSellableItemsCacheKey(SellableItemRequestTransfer $sellableItemRequestTransfer): string
    {
        return md5(serialize($sellableItemRequestTransfer->toArray()));
    }
}
