<?php

namespace Line_Bridge_WP\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DB_Templates {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'line_bridge_message_templates';
	}

	public function get( string $key, string $locale = 'default' ): object|null {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE template_key = %s AND locale = %s AND is_active = 1",
				$key,
				$locale
			)
		);
	}

	public function upsert( string $key, string $locale, array $data ): bool {
		global $wpdb;
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->table} WHERE template_key = %s AND locale = %s",
				$key,
				$locale
			)
		);
		if ( $existing ) {
			$result = $wpdb->update(
				$this->table,
				[
					'message_type' => sanitize_text_field( $data['message_type'] ?? 'text' ),
					'subject'      => sanitize_text_field( $data['subject'] ?? '' ),
					'body'         => wp_kses_post( $data['body'] ?? '' ),
					'is_active'    => isset( $data['is_active'] ) ? (int) $data['is_active'] : 1,
				],
				[ 'id' => (int) $existing ],
				[ '%s', '%s', '%s', '%d' ],
				[ '%d' ]
			);
		} else {
			$result = $wpdb->insert(
				$this->table,
				[
					'template_key' => $key,
					'locale'       => $locale,
					'message_type' => sanitize_text_field( $data['message_type'] ?? 'text' ),
					'subject'      => sanitize_text_field( $data['subject'] ?? '' ),
					'body'         => wp_kses_post( $data['body'] ?? '' ),
					'is_active'    => 1,
				],
				[ '%s', '%s', '%s', '%s', '%s', '%d' ]
			);
		}
		return $result !== false;
	}

	public function get_all(): array {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$this->table} ORDER BY template_key ASC, locale ASC"
		);
	}

	public function get_keys(): array {
		global $wpdb;
		return $wpdb->get_col(
			"SELECT DISTINCT template_key FROM {$this->table} ORDER BY template_key ASC"
		);
	}
}
