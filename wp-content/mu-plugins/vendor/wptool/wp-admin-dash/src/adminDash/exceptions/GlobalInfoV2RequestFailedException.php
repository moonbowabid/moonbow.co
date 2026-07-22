<?php

namespace Wptool\adminDash\exceptions;

/**
 * Thrown when an upstream /api/v1/admindash/* call (handled by
 * GlobalInfoV2Service) fails or returns a non-200 response.
 */
class GlobalInfoV2RequestFailedException extends AdminDashException {

	/**
	 * HTTP-like status code returned from upstream (or 0 when transport failed).
	 *
	 * @var int
	 */
	private $status;

	/**
	 * Error code string from upstream response body, if any.
	 *
	 * @var string|null
	 */
	private $upstream_code;

	/**
	 * @param string          $message       Human readable message (typically upstream `message`).
	 * @param int             $status        HTTP status (or 0 for transport failure).
	 * @param string|null     $upstream_code Optional upstream `status`/`code` field.
	 * @param \Exception|null $previous
	 */
	public function __construct( $message = '', $status = 0, $upstream_code = null, \Exception $previous = null ) {
		parent::__construct( $message, 0, $previous );

		$this->status        = (int) $status;
		$this->upstream_code = $upstream_code;
		$this->reason        = 'Admin dashboard upstream request failed.';
	}

	/**
	 * @return int
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @return string|null
	 */
	public function get_upstream_code() {
		return $this->upstream_code;
	}
}
