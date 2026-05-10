<?php

namespace Line_Bridge_WP\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DB_Users {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'line_bridge_users';
	}

	public function insert( array $data ): int|false {
		global $wpdb;
		$result = $wpdb->insert(
			$this->table,
			[
				'wp_user_id'   => absint( $data['wp_user_id'] ),
				'line_user_id' => sanitize_text_field( $data['line_user_id'] ),
				'display_name' => sanitize_text_field( $data['display_name'] ?? '' ),
				'picture_url'  => esc_url_raw( $data['picture_url'] ?? '' ),
				'email'        => sanitize_email( $data['email'] ?? '' ),
				'access_token' => $data['access_token'] ?? null,
				'refresh_token'=> $data['refresh_token'] ?? null,
				'token_expires'=> $data['token_expires'] ?? null,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
		return $result ? $wpdb->insert_id : false;
	}

	public function get_by_wp_user_id( int $wp_user_id ): object|null {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE wp_user_id = %d",
				$wp_user_id
			)
		);
	}

	public function get_by_line_user_id( string $line_user_id ): object|null {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE line_user_id = %s",
				$line_user_id
			)
		);
	}

	public function update( int $wp_user_id, array $data ): bool {
		global $wpdb;
		$allowed = [ 'display_name', 'picture_url', 'email', 'access_token', 'refresh_token', 'token_expires' ];
		$update  = [];
		$formats = [];
		foreach ( $allowed as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$update[ $field ] = $data[ $field ];
				$formats[]        = '%s';
			}
		}
		if ( empty( $update ) ) {
			return false;
		}
		$result = $wpdb->update(
			$this->table,
			$update,
			[ 'wp_user_id' => $wp_user_id ],
			$formats,
			[ '%d' ]
		);
		return $result !== false;
	}

	public function delete( int $wp_user_id ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			$this->table,
			[ 'wp_user_id' => $wp_user_id ],
			[ '%d' ]
		);
		return (bool) $result;
	}

	public function get_all( int $per_page = 20, int $page = 1 ): array {
		global $wpdb;
		$offset = ( $page - 1 ) * $per_page;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.*, wu.user_login, wu.user_email AS wp_email
				 FROM {$this->table} u
				 LEFT JOIN {$wpdb->users} wu ON wu.ID = u.wp_user_id
				 ORDER BY u.linked_at DESC
				 LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);
	}

	public function count(): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
	}
}
