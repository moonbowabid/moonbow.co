<?php

namespace GoDaddy\WordPress\OAuth;

use RuntimeException;

/**
 * Version management for multi-plugin compatibility.
 *
 * Allows multiple versions to register but only initializes the highest version to prevent conflicts.
 *
 * IMPORTANT: This class does not get the same load-highest-version treatment as the rest of the OAuth package.
 * The Versions.php file that gets required could be from any version, depending on which plugin WordPress
 * loads first (alphabetical order), and may not even be from the same OAuth package version that ultimately
 * gets loaded. In practice this means Versions.php needs to remain mostly unchanged after initial release.
 */
class Versions
{
    /**
     * Registered package versions.
     *
     * @var array<string, mixed>
     */
    private static array $versions = [];

    /**
     * Whether initialization has already occurred.
     *
     * @var bool
     */
    private static bool $initialized = false;

    /**
     * Registers a given version.
     *
     * @param string $version version to register
     * @param mixed $callback initialization callback for the version being registered
     * @return void
     */
    public static function register(string $version, $callback) : void
    {
        if (empty(self::$versions[$version])) {
            self::$versions[$version] = $callback;
        }
    }

    /**
     * Gets the latest registered version.
     *
     * @return string
     * @throws RuntimeException if no versions are registered
     */
    public static function getLatestVersion() : string
    {
        if (empty(self::$versions)) {
            throw new RuntimeException('No versions registered');
        }

        $versions = array_keys(self::$versions);

        usort($versions, static function (string $a, string $b) : int {
            return version_compare($a, $b);
        });

        return end($versions);
    }

    /**
     * Initializes the latest registered version.
     *
     * @return void
     */
    public static function initializeLatestVersion() : void
    {
        if (self::$initialized || empty(self::$versions)) {
            return;
        }

        try {
            $latestVersion = self::getLatestVersion();
            if (! empty(self::$versions[$latestVersion])) {
                $callback = self::$versions[$latestVersion];
                if (is_callable($callback)) {
                    call_user_func($callback);
                    self::$initialized = true;
                }
            }
        } catch (RuntimeException $e) {
            // No versions registered, skip initialization
        }
    }
}
