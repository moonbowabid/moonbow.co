<?php
declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Auth;

/**
 * Contract for authentication providers.
 *
 * Each provider detects a specific credential type in the request,
 * validates it independently, and resolves to a WordPress user ID.
 *
 * @package mcp-adapter-initializer
 * @since 1.6.0
 */
interface Auth_Provider {

	/**
	 * Check if this provider can handle the current request.
	 *
	 * Must only detect presence of credentials (e.g., specific headers),
	 * not validate them.
	 *
	 * @return bool True if this provider's credential type is present.
	 */
	public function can_handle(): bool;

	/**
	 * Validate the credential and return a WordPress user ID.
	 *
	 * Called only when can_handle() returned true. Must NOT call
	 * wp_set_current_user() — the orchestrator handles that.
	 *
	 * @return int User ID on success (> 0), 0 on failure.
	 */
	public function authenticate(): int;

	/**
	 * Return the failure reason if authenticate() returned 0.
	 *
	 * @return string|null Error message, or null if no failure occurred.
	 */
	public function get_error(): ?string;

	/**
	 * Return this provider's priority (lower = checked first).
	 *
	 * @return int Priority value.
	 */
	public function get_priority(): int;
}
