<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\AvailabilityCartConnector\Business\Reader;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\SellableItemsResponseTransfer;

interface SellableItemsReaderInterface
{
    public function getSellableItems(CartChangeTransfer $cartChangeTransfer, bool $skipItemsWithAmount = true): SellableItemsResponseTransfer;
}
