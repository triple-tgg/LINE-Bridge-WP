<?php

namespace Line_Bridge_WP\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DB_Notifications {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'line_bridge_notifications';
	}

	public function insert( array $data ): int|false {
		global $wpdb;
		$result = $wpdb->insert(
			$this->table,
			[
				'notification_type' => sanitize_text_field( $data['notification_type'] ),
				'recipient_id'      => sanitize_text_field( $data['recipient_id'] ),
				'wp_user_id'        => isset( $data['wp_user_id'] ) ? absint( $data['wp_user_id'] ) : null,
				'reference_id'      => isset( $data['reference_id'] ) ? absint( $data['reference_id'] ) : null,
				'reference_type'    => isset( $data['reference_type'] ) ? sanitize_text_field( $data['reference_type'] ) : null,
				'payload'           => isset( $data['payload'] ) ? wp_json_encode( $data['payload'] ) : null,
				'status'            => 'pending',
			],
			[ '%s', '%s', '%d', '%d', '%s', '%s', '%s' ]
		);
		return $result ? $wpdb->insert_id : false;
	}

	public function update_status( int $id, string $status, ?string $error = null ): bool {
		global $wpdb;
		$update = [
			'status'    => $status,
			'sent_at'   => current_time( 'mysql' ),
		];
		$format = [ '%s', '%s' ];
		if ( $error !== null ) {
			$update['error_message'] = $error;
			$format[]                = '%s';
		}
		return $wpdb->update(
			$this->table,
			$update,
			[ 'id' => $id ],
			$format,
			[ '%d' ]
		) !== false;
	}

	public function increment_retry( int $id ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table} SET retry_count = retry_count + 1 WHERE id = %d",
				$id
			)
		);
	}

	public function already_sent( int $reference_id, string $reference_type, string $type ): bool {
		global $wpdb;
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table}
				 WHERE reference_id = %d AND reference_type = %s AND notification_type = %s AND status = 'sent'",
				$reference_id,
				$reference_type,
				$type
			)
		);
		return (int) $count > 0;
	}

	public function get_failed_for_retry( int $max_retries = 3 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				 WHERE status = 'failed' AND retry_count < %d
				 ORDER BY created_at ASC LIMIT 50",
				$max_retries
			)
		);
	}

	public function get_logs( array $args = [] ): array {
		global $wpdb;
		$per_page = absint( $args['per_page'] ?? 20 );
		$page     = absint( $args['page'] ?? 1 );
		$offset   = ( $page - 1 ) * $per_page;

		$where  = '1=1';
		$params = [];

		if ( ! empty( $args['type'] ) ) {
			$where   .= ' AND notification_type = %s';
			$params[] = $args['type'];
		}
		if ( ! empty( $args['status'] ) ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		}

		$params[] = $per_page;
		$params[] = $offset;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
				...$params
			)
		);
	}

	public function count( array $filters = [] ): int {
		global $wpdb;
		$where  = '1=1';
		$params = [];
		if ( ! empty( $filters['type'] ) ) {
			$where   .= ' AND notification_type = %s';
			$params[] = $filters['type'];
		}
		if ( ! empty( $filters['status'] ) ) {
			$where   .= ' AND status = %s';
			$params[] = $filters['status'];
		}
		if ( empty( $params ) ) {
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE $where" );
		}
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$this->table} WHERE $where", ...$params )
		);
	}

	public function purge_old( int $days = 90 ): int {
		global $wpdb;
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);
		return (int) $result;
	}
}
