<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\AvailabilityCartConnector\Communication\Plugin\Quote;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteExpandBeforeCreatePluginInterface;

/**
 * @method \Spryker\Zed\AvailabilityCartConnector\Business\AvailabilityCartConnectorBusinessFactory getBusinessFactory()
 * @method \Spryker\Zed\AvailabilityCartConnector\Business\AvailabilityCartConnectorFacadeInterface getFacade()
 * @method \Spryker\Zed\AvailabilityCartConnector\Communication\AvailabilityCartConnectorCommunicationFactory getFactory()
 */
class AvailabilityLockedQuoteExpandBeforeCreatePlugin extends AbstractPlugin implements QuoteExpandBeforeCreatePluginInterface
{
    /**
     * {@inheritDoc}
     * - Does nothing when `QuoteTransfer.isLocked` or `QuoteTransfer.store` is not set.
     * - Expands `QuoteTransfer.items` with `ItemTransfer.stockQuantity` and `ItemTransfer.isNeverOutOfStock`.
     * - Runs before the quote is persisted, so the availability data is stored with the quote and is returned
     *   on every read of it.
     * - A locked quote, e.g. a quote converted from a quote request, never goes through a cart operation, so
     *   this is the only place its items receive the availability data.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function expand(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        return $this->getBusinessFactory()
            ->createLockedQuoteItemsAvailabilityExpander()
            ->expandLockedQuoteItemsWithAvailability($quoteTransfer);
    }
}
