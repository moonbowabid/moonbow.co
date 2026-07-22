<?php

declare (strict_types=1);
namespace GoDaddy\WordPress\MWC\Common\Vendor;

use Rector\Config\RectorConfig;
return RectorConfig::configure()->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])->withPhpSets()->withTypeCoverageLevel(50)->withDeadCodeLevel(48)->withCodeQualityLevel(71);
