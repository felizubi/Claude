<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class GPLVault_Psr_Log_Handler implements GPLVault_Psr_Log_Handler_Interface {

	/**
	 * @var string
	 */
	private $filename;

	public function __construct( $filename ) {
		$dir = dirname( $filename );
		if ( ! file_exists( $dir ) ) {
			$status = wp_mkdir_p( $dir );
			if ( false === $status && ! is_dir( $dir ) ) {
				throw new UnexpectedValueException( sprintf( 'Could not create the directory ["%s"], or somehow, it is missing.', $dir ) );
			}
		}
		$this->filename = $filename;
	}

	public function handle( $vars ) {
		$output = self::DEFAULT_FORMAT;
		foreach ( $vars as $var => $value ) {
			$output = str_replace( '%' . $var . '%', $value, $output );
		}
		file_put_contents( $this->filename, $output . PHP_EOL, FILE_APPEND ); // @phpcs:ignore
	}
}
