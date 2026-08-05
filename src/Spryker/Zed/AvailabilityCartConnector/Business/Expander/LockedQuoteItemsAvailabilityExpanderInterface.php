<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\AvailabilityCartConnector\Business\Expander;

use Generated\Shared\Transfer\QuoteTransfer;

interface LockedQuoteItemsAvailabilityExpanderInterface
{
    /**
     * Specification:
     * - Does nothing when `QuoteTransfer.isLocked` is not set.
     * - Does nothing when `QuoteTransfer.store` is not set.
     * - Expands `QuoteTransfer.items` with `ItemTransfer.stockQuantity` and `ItemTransfer.isNeverOutOfStock`.
     * - Sets `ItemTransfer.stockQuantity` to `0` and `ItemTransfer.isNeverOutOfStock` to `false` for the items
     *   the availability check does not return a response for.
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function expandLockedQuoteItemsWithAvailability(QuoteTransfer $quoteTransfer): QuoteTransfer;
}
