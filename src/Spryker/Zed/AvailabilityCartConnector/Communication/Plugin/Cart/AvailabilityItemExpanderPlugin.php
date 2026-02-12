<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\AvailabilityCartConnector\Communication\Plugin\Cart;

use Generated\Shared\Transfer\CartChangeTransfer;
use Spryker\Zed\CartExtension\Dependency\Plugin\ItemExpanderPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Spryker\Zed\AvailabilityCartConnector\Business\AvailabilityCartConnectorFacadeInterface getFacade()
 * @method \Spryker\Zed\AvailabilityCartConnector\Business\AvailabilityCartConnectorBusinessFactory getBusinessFactory()
 * @method \Spryker\Zed\AvailabilityCartConnector\Communication\AvailabilityCartConnectorCommunicationFactory getFactory()
 */
class AvailabilityItemExpanderPlugin extends AbstractPlugin implements ItemExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Requires `CartChangeTransfer.quote` to be set.
     * - Requires `CartChangeTransfer.quote.store` to be set.
     * - Gets sellable items data for `CartChangeTransfer.items`.
     * - Expands `CartChangeTransfer.items` with `ItemTransfer.stockQuantity` and `ItemTransfer.isNeverOutOfStock`.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function expandItems(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer
    {
        return $this->getBusinessFactory()->createAvailabilityItemExpander()->expandItems($cartChangeTransfer);
    }
}
