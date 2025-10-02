<?php
/**
 * Credits: https://github.com/robinvdvleuten/php-ulid
 */

final class GPLVault_Ulid {
	const ENCODING_CHARS  = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
	const ENCODING_LENGTH = 32;
	const TIME_MAX        = 281474976710655;
	const TIME_LENGTH     = 10;
	const RANDOM_LENGTH   = 16;

	/**
	 * @var int
	 */
	private static $lastGenTime = 0;

	/**
	 * @var array
	 */
	private static $lastRandChars = array();

	/**
	 * @var string
	 */
	private $time;

	/**
	 * @var string
	 */
	private $randomness;

	/**
	 * @var bool
	 */
	private $lowercase;

	private function __construct( string $time, string $randomness, bool $lowercase = false ) {
		$this->time       = $time;
		$this->randomness = $randomness;
		$this->lowercase  = $lowercase;
	}

	public static function fromString( string $value, bool $lowercase = false ): self {
		if ( strlen( $value ) !== static::TIME_LENGTH + static::RANDOM_LENGTH ) {
			throw new GPLVault_Invalid_Ulid_Exception( 'Invalid ULID string (wrong length): ' . $value );
		}

		// Convert to uppercase for regex. Doesn't matter for output later, that is determined by $lowercase.
		$value = strtoupper( $value );

		if ( ! preg_match( sprintf( '!^[%s]{%d}$!', static::ENCODING_CHARS, static::TIME_LENGTH + static::RANDOM_LENGTH ), $value ) ) {
			throw new GPLVault_Invalid_Ulid_Exception( 'Invalid ULID string (wrong characters): ' . $value );
		}

		return new static( substr( $value, 0, static::TIME_LENGTH ), substr( $value, static::TIME_LENGTH, static::RANDOM_LENGTH ), $lowercase );
	}

	/**
	 * Create a ULID using the given timestamp.
	 * @param int $milliseconds Number of milliseconds since the UNIX epoch for which to generate this ULID.
	 * @param bool $lowercase True to output lowercase ULIDs.
	 * @return GPLVault_Ulid Returns a GPLVault_Ulid object for the given microsecond time.
	 */
	public static function fromTimestamp( int $milliseconds, bool $lowercase = false ): self {
		$duplicate_time = $milliseconds === static::$lastGenTime;

		static::$lastGenTime = $milliseconds;

		$time_chars = '';
		$rand_chars = '';

		$encoding_chars = static::ENCODING_CHARS;

		for ( $i = static::TIME_LENGTH - 1; $i >= 0; $i-- ) {
			$mod          = $milliseconds % static::ENCODING_LENGTH;
			$time_chars   = $encoding_chars[ $mod ] . $time_chars;
			$milliseconds = ( $milliseconds - $mod ) / static::ENCODING_LENGTH;
		}

		if ( ! $duplicate_time ) {
			for ( $i = 0; $i < static::RANDOM_LENGTH; $i++ ) {
				static::$lastRandChars[ $i ] = random_int( 0, 31 );
			}
		} else {
			// If the timestamp hasn't changed since last push,
			// use the same random number, except incremented by 1.
			for ( $i = static::RANDOM_LENGTH - 1; $i >= 0 && static::$lastRandChars[ $i ] === 31; $i-- ) { // phpcs:ignore
				static::$lastRandChars[ $i ] = 0;
			}

			static::$lastRandChars[ $i ]++;
		}

		for ( $i = 0; $i < static::RANDOM_LENGTH; $i++ ) {
			$rand_chars .= $encoding_chars[ static::$lastRandChars[ $i ] ];
		}

		return new static( $time_chars, $rand_chars, $lowercase );
	}

	public static function generate( bool $lowercase = false ): self {
		$now = (int) ( microtime( true ) * 1000 );

		return static::fromTimestamp( $now, $lowercase );
	}

	public function getTime(): string {
		return $this->time;
	}

	public function getRandomness(): string {
		return $this->randomness;
	}

	public function isLowercase(): bool {
		return $this->lowercase;
	}

	public function toTimestamp(): int {
		return $this->decodeTime( $this->time );
	}

	public function __toString(): string {
		return ( $value = $this->time . $this->randomness ) && $this->lowercase ? strtolower( $value ) : strtoupper( $value ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
	}

	private function decodeTime( string $time ): int {
		$time_chars = str_split( strrev( $time ) );
		$carry      = 0;

		foreach ( $time_chars as $index => $char ) {
			if ( ( $encoding_index = strripos( static::ENCODING_CHARS, $char ) ) === false ) { // phpcs:ignore
				throw new GPLVault_Invalid_Ulid_Exception( 'Invalid ULID character: ' . $char );
			}

			$carry += ( $encoding_index * pow( static::ENCODING_LENGTH, $index ) );
		}

		if ( $carry > static::TIME_MAX ) {
			throw new GPLVault_Invalid_Ulid_Exception( 'Invalid ULID string: timestamp too large' );
		}

		return $carry;
	}
}
