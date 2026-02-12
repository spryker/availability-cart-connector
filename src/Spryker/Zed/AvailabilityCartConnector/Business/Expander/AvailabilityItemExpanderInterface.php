<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\AvailabilityCartConnector\Business\Expander;

use Generated\Shared\Transfer\CartChangeTransfer;

interface AvailabilityItemExpanderInterface
{
    public function expandItems(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer;
}
