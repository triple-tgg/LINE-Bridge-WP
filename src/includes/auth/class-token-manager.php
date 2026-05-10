<?php

namespace Line_Bridge_WP\Auth;

use Line_Bridge_WP\Database\DB_Users;
use Line_Bridge_WP\Utilities\Crypto;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Token_Manager {

	public function __construct( private DB_Users $db_users ) {}

	public function save_tokens( int $wp_user_id, array $token_data ): bool {
		$expires = isset( $token_data['expires_in'] )
			? gmdate( 'Y-m-d H:i:s', time() + (int) $token_data['expires_in'] )
			: null;

		return $this->db_users->update( $wp_user_id, [
			'access_token'  => Crypto::encrypt( $token_data['access_token'] ?? '' ),
			'refresh_token' => Crypto::encrypt( $token_data['refresh_token'] ?? '' ),
			'token_expires' => $expires,
		] );
	}

	public function get_token( int $wp_user_id ): array|null {
		$row = $this->db_users->get_by_wp_user_id( $wp_user_id );
		if ( ! $row ) {
			return null;
		}
		return [
			'access_token'  => Crypto::decrypt( $row->access_token ?? '' ),
			'refresh_token' => Crypto::decrypt( $row->refresh_token ?? '' ),
			'token_expires' => $row->token_expires,
		];
	}

	public function is_token_valid( int $wp_user_id ): bool {
		$token = $this->get_token( $wp_user_id );
		if ( ! $token || empty( $token['access_token'] ) ) {
			return false;
		}
		if ( $token['token_expires'] ) {
			return strtotime( $token['token_expires'] ) > time();
		}
		return true;
	}

	public function revoke( int $wp_user_id ): void {
		$this->db_users->update( $wp_user_id, [
			'access_token'  => null,
			'refresh_token' => null,
			'token_expires' => null,
		] );
	}
}
