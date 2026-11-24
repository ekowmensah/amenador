<?php

namespace Modules\Storefront\Services;

use App\Business;
use App\Utils\TransactionUtil;

class GuestTransactionUtil extends TransactionUtil
{
    public $storeBusinessId;

    public function allModulesEnabled($business_id = null)
    {
        // A staff login in another tab must never change the public store's tenant.
        return Business::findOrFail($business_id ?: $this->storeBusinessId)->enabled_modules ?? [];
    }

    public function calculateRewardPoints($business_id, $total)
    {
        // Guest checkout is not an authenticated loyalty account.
        return 0;
    }
}
