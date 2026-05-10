<?php

namespace Line_Bridge_WP\Admin;

use Line_Bridge_WP\Database\DB_Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Logs_List_Table extends \WP_List_Table {

	public function __construct(
		private DB_Notifications $db_notifications,
		private string $filter_type = '',
		private string $filter_status = ''
	) {
		parent::__construct( [
			'singular' => 'log',
			'plural'   => 'logs',
			'ajax'     => false,
		] );
	}

	public function get_columns(): array {
		return [
			'created_at'        => __( 'วันที่', 'line-bridge-wp' ),
			'notification_type' => __( 'ประเภท', 'line-bridge-wp' ),
			'recipient_id'      => __( 'ผู้รับ', 'line-bridge-wp' ),
			'reference'         => __( 'อ้างอิง', 'line-bridge-wp' ),
			'status'            => __( 'สถานะ', 'line-bridge-wp' ),
			'error_message'     => __( 'ข้อผิดพลาด', 'line-bridge-wp' ),
		];
	}

	public function prepare_items(): void {
		$per_page = 30;
		$page     = $this->get_pagenum();
		$filters  = [
			'type'     => $this->filter_type,
			'status'   => $this->filter_status,
			'per_page' => $per_page,
			'page'     => $page,
		];

		$this->items = $this->db_notifications->get_logs( $filters );
		$total       = $this->db_notifications->count( [
			'type'   => $this->filter_type,
			'status' => $this->filter_status,
		] );

		$this->set_pagination_args( [
			'total_items' => $total,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total / $per_page ),
		] );

		$this->_column_headers = [ $this->get_columns(), [], [] ];
	}

	protected function column_default( $item, $column_name ): string {
		return esc_html( $item->$column_name ?? '—' );
	}

	protected function column_created_at( $item ): string {
		return esc_html( wp_date( 'd/m/Y H:i:s', strtotime( $item->created_at ) ) );
	}

	protected function column_notification_type( $item ): string {
		return '<code>' . esc_html( $item->notification_type ) . '</code>';
	}

	protected function column_recipient_id( $item ): string {
		return '<code>' . esc_html( substr( $item->recipient_id, 0, 20 ) . '...' ) . '</code>';
	}

	protected function column_reference( $item ): string {
		if ( ! $item->reference_id ) {
			return '—';
		}
		if ( $item->reference_type === 'order' ) {
			$order = wc_get_order( $item->reference_id );
			if ( $order ) {
				return '<a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . esc_html( $order->get_order_number() ) . '</a>';
			}
		}
		return esc_html( $item->reference_type . ':' . $item->reference_id );
	}

	protected function column_status( $item ): string {
		$map = [
			'sent'    => 'status-success',
			'failed'  => 'status-error',
			'pending' => 'status-pending',
		];
		$labels = [
			'sent'    => __( 'สำเร็จ', 'line-bridge-wp' ),
			'failed'  => __( 'ล้มเหลว', 'line-bridge-wp' ),
			'pending' => __( 'รอ', 'line-bridge-wp' ),
		];
		$class = $map[ $item->status ] ?? '';
		$label = $labels[ $item->status ] ?? $item->status;
		return '<span class="line-bridge-status ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
	}

	protected function column_error_message( $item ): string {
		if ( empty( $item->error_message ) ) {
			return '—';
		}
		return '<span title="' . esc_attr( $item->error_message ) . '">'
			. esc_html( mb_substr( $item->error_message, 0, 50 ) )
			. ( mb_strlen( $item->error_message ) > 50 ? '…' : '' )
			. '</span>';
	}
}
