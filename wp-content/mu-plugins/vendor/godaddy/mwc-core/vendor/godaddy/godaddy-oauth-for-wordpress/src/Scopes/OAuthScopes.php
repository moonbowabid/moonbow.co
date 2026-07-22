<?php

namespace GoDaddy\WordPress\OAuth\Scopes;

/**
 * OAuth Scopes Registry.
 *
 * Manages the collection of OAuth scopes that will be requested during
 * authorization. The default scope 'profile' is always included.
 */
class OAuthScopes
{
    /**
     * Default scope that is always included.
     */
    private const DEFAULT_SCOPE = 'profile';

    /**
     * Registered scopes (keyed by scope name to prevent duplicates).
     *
     * @var array<string, bool>
     */
    private static array $scopes = [];

    /**
     * Register a scope.
     *
     * Adds a scope to the registry. The scope will be trimmed of whitespace.
     * Empty strings and the default scope are ignored.
     *
     * @param string $scope The scope to register
     * @return void
     */
    public static function register(string $scope) : void
    {
        $scope = trim($scope);

        if ($scope === '' || $scope === self::DEFAULT_SCOPE) {
            return;
        }

        self::$scopes[$scope] = true;
    }

    /**
     * Register multiple scopes.
     *
     * @param array<string> $scopes The scopes to register
     * @return void
     */
    public static function registerMany(array $scopes) : void
    {
        foreach ($scopes as $scope) {
            self::register($scope);
        }
    }

    /**
     * Get all registered scopes.
     *
     * Returns the default scope first, followed by any registered scopes.
     *
     * @return array<string> List of all scopes
     */
    public static function all() : array
    {
        return array_merge([self::DEFAULT_SCOPE], array_keys(self::$scopes));
    }

    /**
     * Check if a scope is registered or is the default scope.
     *
     * @param string $scope The scope to check
     * @return bool True if the scope is available
     */
    public static function has(string $scope) : bool
    {
        return $scope === self::DEFAULT_SCOPE || isset(self::$scopes[$scope]);
    }
}
