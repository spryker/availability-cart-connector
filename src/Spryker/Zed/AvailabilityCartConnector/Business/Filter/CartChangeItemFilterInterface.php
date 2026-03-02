<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\AvailabilityCartConnector\Business\Filter;

use Generated\Shared\Transfer\CartChangeTransfer;

interface CartChangeItemFilterInterface
{
    public function filterOutUnavailableItems(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer;
}
