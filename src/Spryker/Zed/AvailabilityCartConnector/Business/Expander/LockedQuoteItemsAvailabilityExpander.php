<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\AvailabilityCartConnector\Business\Expander;

use ArrayObject;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

class LockedQuoteItemsAvailabilityExpander implements LockedQuoteItemsAvailabilityExpanderInterface
{
    public function __construct(protected AvailabilityItemExpanderInterface $availabilityItemExpander)
    {
    }

    public function expandLockedQuoteItemsWithAvailability(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        if (!$quoteTransfer->getIsLocked() || $quoteTransfer->getStore() === null) {
            return $quoteTransfer;
        }

        $this->availabilityItemExpander->expandItems($this->createCartChangeTransfer($quoteTransfer));

        return $quoteTransfer;
    }

    protected function createCartChangeTransfer(QuoteTransfer $quoteTransfer): CartChangeTransfer
    {
        return (new CartChangeTransfer())
            ->setQuote((clone $quoteTransfer)->setItems(new ArrayObject()))
            ->setItems($quoteTransfer->getItems());
    }
}
