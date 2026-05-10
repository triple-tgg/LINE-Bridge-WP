<?php

namespace Line_Bridge_WP\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Crypto {

	public static function verify_line_signature( string $body, string $signature, string $channel_secret ): bool {
		if ( empty( $channel_secret ) || empty( $signature ) ) {
			return false;
		}
		$hash = base64_encode( hash_hmac( 'sha256', $body, $channel_secret, true ) );
		return hash_equals( $hash, $signature );
	}

	public static function encrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}
		$key    = substr( hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY ), 0, 32 );
		$iv     = random_bytes( 16 );
		$cipher = openssl_encrypt( $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		return base64_encode( $iv . $cipher );
	}

	public static function decrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}
		try {
			$key     = substr( hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY ), 0, 32 );
			$decoded = base64_decode( $value );
			$iv      = substr( $decoded, 0, 16 );
			$cipher  = substr( $decoded, 16 );
			$plain   = openssl_decrypt( $cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
			return $plain !== false ? $plain : '';
		} catch ( \Throwable $e ) {
			return '';
		}
	}

	public static function generate_state( int $length = 32 ): string {
		return bin2hex( random_bytes( $length ) );
	}
}
