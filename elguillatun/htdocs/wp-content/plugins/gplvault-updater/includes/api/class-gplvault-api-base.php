<?php

defined( 'ABSPATH' ) || exit;

abstract class GPLVault_Api_Base {
	/** @var static */
	protected static $instance;

	/**
	 * Base URL to the API server.
	 *
	 * @var string
	 */

	protected string $api_base = '';

	/** @var array */
	protected array $rawResponse = array();

	/**
	 * @var mixed
	 */
	protected $response;

	/** @var array  */
	protected array $query_args = array();

	/**
	 * @var int|null
	 */
	protected ?int $statusCode;

	/**
	 * @var WP_Error|null
	 */
	protected ?WP_Error $error;

	/**
	 * @var array|\Requests_Utility_CaseInsensitiveDictionary
	 */
	protected $responseHeaders;

	/**
	 * @var GPLVault_Psr_Log_Interface|GPLVault_Null_Logger|GPLVault_Psr_Logger $logger
	 */
	protected GPLVault_Psr_Log_Interface $logger;

	/**
	 * @param GPLVault_Psr_Log_Interface|null $logger
	 */
	private function __construct( GPLVault_Psr_Log_Interface $logger = null ) {
		$this->logger = $logger ?? gv_new_logger();
	}

	/**
	 * Get concrete api manager instance.
	 *
	 * @param GPLVault_Psr_Log_Interface|null $logger
	 * @return static
	 */
	public static function instance( GPLVault_Psr_Log_Interface $logger = null ) {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static( $logger );
		}

		return static::$instance;
	}

	/**
	 * @return int|null
	 */
	public function getStatusCode(): ?int {
		return $this->statusCode;
	}

	protected function request( string $path = '', array $options = array() ) {
		$defaults = array(
			'method'     => 'GET',
			'user-agent' => static::ua_string(),
			'headers'    => $this->headers(),
			'timeout'    => apply_filters( 'gv-client.api_request_timeout', 15 ),
		);

		$options = wp_parse_args( $options, $defaults );
		$url     = $this->getUrl( $path );

		$response = wp_remote_request( $url, $options );

		if ( is_wp_error( $response ) ) {
			$this->error = $response;

			return $this;
		}

		$this->rawResponse = $response;

		$this->statusCode      = wp_remote_retrieve_response_code( $response );
		$this->response        = wp_remote_retrieve_body( $response );
		$this->responseHeaders = wp_remote_retrieve_headers( $response );

		return $this;
	}

	/**
	 * @global string $wp_version
	 * @return string
	 */
	protected static function ua_string() {
		global $wp_version;
		require ABSPATH . WPINC . '/version.php';
		return 'GPLVaultClient/' . GPLVault()->version() . '; WP/' . $wp_version . '; ' . home_url( '/' );
	}

	public function hasError(): bool {
		return ! is_null( $this->error );
	}

	/**
	 * Form and get full request url to
	 * @param string $path The API request URL path if any. Default is empty string.
	 */
	abstract protected function getUrl( string $path = ''): string;

	/**
	 * Get request headers for the response.
	 *
	 * @param array<string, string> $headers
	 * @return array
	 */
	abstract protected function headers( array $headers = array()): array;

	/**
	 * Set query args for current request.
	 *
	 * @param array $query_args
	 * @return static
	 */
	abstract protected function setQuery( array $query_args = array());
}
