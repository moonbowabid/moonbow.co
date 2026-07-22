<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Auth;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Auth\Providers\GoDaddy\Contracts\ThreeLeggedOAuthTokenProviderContract;
use GoDaddy\WordPress\MWC\Core\Auth\Providers\GoDaddy\ThreeLeggedOAuthAuthProvider;

class ThreeLeggedOAuthAuthProviderServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [ThreeLeggedOAuthTokenProviderContract::class];

    public function register() : void
    {
        $this->getContainer()->singleton(ThreeLeggedOAuthTokenProviderContract::class, ThreeLeggedOAuthAuthProvider::class);
    }
}
