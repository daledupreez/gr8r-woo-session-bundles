<?php
/**
 * Logger helper class.
 *
 * @package GR8R_Woo_Session_Bundles
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GR8R Session Bundles Logger
 *
 * Wraps wc_get_logger() to provide consistent logging for the plugin.
 * Logged data will appear under WooCommerce > Status > Logs.
 *
 * Filter:
 *     gr8r_woo_session_bundles_logging_level — Set a minimum log level string
 *     (e.g. 'warning') to suppress lower-priority entries. Defaults to null
 *     which passes all entries through to WooCommerce's own level filter.
 */
class GR8R_Woo_Session_Bundles_Logger {

	/**
	 * WooCommerce log source identifier. Entries appear under this name
	 * in WooCommerce > Status > Logs.
	 */
	private const LOG_SOURCE = 'gr8r-session-bundles';

	/**
	 * Valid log levels for configuration.
	 */
	private const VALID_LOG_LEVELS = [
		'debug',
		'warning',
	];

	/**
	 * Cached minimum log level from the filter.
	 *
	 * @var string|null
	 */
	private static ?string $log_level = null;

	/**
	 * WC_Logger instance.
	 *
	 * @var WC_Logger|null
	 */
	private static $logger = null;

	/**
	 * This class is stateless — instantiation is not supported.
	 */
	private function __construct() {}

	// ---------------------------------------------------------------------------
	// Public static API
	// ---------------------------------------------------------------------------

	/**
	 * Log a debug message.
	 *
	 * @param string $message Human-readable log message.
	 * @param array  $context Additional context passed to WC_Logger.
	 */
	public static function debug( string $message, array $context = [] ): void {
		self::log( 'debug', $message, $context );
	}

	/**
	 * Log an info-level message.
	 *
	 * @param string $message Human-readable log message.
	 * @param array  $context Additional context passed to WC_Logger.
	 */
	public static function info( string $message, array $context = [] ): void {
		self::log( 'info', $message, $context );
	}

	/**
	 * Log a notice.
	 *
	 * @param string $message Human-readable log message.
	 * @param array  $context Additional context passed to WC_Logger.
	 */
	public static function notice( string $message, array $context = [] ): void {
		self::log( 'notice', $message, $context );
	}

	/**
	 * Log a warning-level message.
	 *
	 * @param string $message Human-readable log message.
	 * @param array  $context Additional context passed to WC_Logger.
	 */
	public static function warning( string $message, array $context = [] ): void {
		self::direct_log( 'warning', $message, $context );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Human-readable log message.
	 * @param array  $context Additional context passed to WC_Logger.
	 */
	public static function error( string $message, array $context = [] ): void {
		self::direct_log( 'error', $message, array_merge( $context, [ 'backtrace' => true ] ) );
	}

	/**
	 * Log a critical message.
	 *
	 * @param string $message Human-readable log message.
	 * @param array  $context Additional context passed to WC_Logger.
	 */
	public static function critical( string $message, array $context = [] ): void {
		self::direct_log( 'critical', $message, array_merge( $context, [ 'backtrace' => true ] ) );
	}

	/**
	 * Log an alert message.
	 *
	 * @param string $message Human-readable log message.
	 * @param array  $context Additional context passed to WC_Logger.
	 */
	public static function alert( string $message, array $context = [] ): void {
		self::direct_log( 'alert', $message, array_merge( $context, [ 'backtrace' => true ] ) );
	}

	/**
	 * Log an emergency-level message.
	 *
	 * @param string $message Human-readable log message.
	 * @param array  $context Additional context passed to WC_Logger.
	 */
	public static function emergency( string $message, array $context = [] ): void {
		self::direct_log( 'emergency', $message, array_merge( $context, [ 'backtrace' => true ] ) );
	}

	// ---------------------------------------------------------------------------
	// Private helpers
	// ---------------------------------------------------------------------------

	/**
	 * Check whether we should log a given log level.
	 *
	 * @param string $level WC log level string (e.g. 'debug', 'error').
	 * @return bool True if the entry should be logged. False if it should be ignored.
	 */
	private static function should_log( string $level ): bool {
		if ( null === self::$log_level ) {
			$log_level = apply_filters( 'gr8r_woo_session_bundles_logging_level', 'warning' );
			if ( ! in_array( $log_level, self::VALID_LOG_LEVELS, true ) ) {
				$log_level = 'warning';
			}
			self::$log_level = $log_level;
		}

		if ( 'debug' === self::$log_level ) {
			return true;
		}

		// We call direct_log() for warning and above, so we can ignore the logging 
		// when we are not in debug mode.
		return false;
	}

	/**
	 * Write a log entry after checking whether that log level should be logged.
	 *
	 * @param string $level   WC log level string.
	 * @param string $message Human-readable log message.
	 * @param array  $context Additional context merged with the source tag.
	 */
	private static function log( string $level, string $message, array $context ): void {
		if ( ! self::should_log( $level ) ) {
			return;
		}

		self::direct_log( $level, $message, $context );
	}

	/**
	 * Write a log entry without any log level checks.
	 *
	 * @param string $level   WC log level string (e.g. 'warning', 'error').
	 * @param string $message Human-readable log message.
	 * @param array  $context Additional context merged with the source tag.
	 */
	private static function direct_log( string $level, string $message, array $context ): void {
		if ( null === self::$logger ) {
			self::$logger = wc_get_logger();
		}

		$context['source'] = self::LOG_SOURCE;

		self::$logger->log( $level, $message, $context );
	}
}
