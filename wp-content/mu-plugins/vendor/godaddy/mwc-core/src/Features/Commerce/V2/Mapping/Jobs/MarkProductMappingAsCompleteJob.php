<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Jobs;

use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Traits\QueueableJobTrait;

class MarkProductMappingAsCompleteJob implements QueueableJobContract
{
    use QueueableJobTrait;

    /** @var string */
    public const JOB_KEY = 'markProductMappingAsCompleteJob';

    public function handle() : void
    {
        update_option('mwc_v2_product_mapping_completed_at', date('Y-m-d H:i:s'));
    }
}
