<?php

namespace Line_Bridge_WP\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logger {

	public static function log( string $message, string $level = 'info', array $context = [] ): void {
		if ( ! WP_DEBUG_LOG ) {
			return;
		}
		$entry = sprintf(
			'[LINE Bridge WP][%s][%s] %s %s',
			strtoupper( $level ),
			current_time( 'Y-m-d H:i:s' ),
			$message,
			$context ? wp_json_encode( $context ) : ''
		);
		error_log( $entry ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}

	public static function error( string $message, array $context = [] ): void {
		self::log( $message, 'error', $context );
	}

	public static function info( string $message, array $context = [] ): void {
		self::log( $message, 'info', $context );
	}
}
