<?php

namespace Line_Bridge_WP\Admin;

use Line_Bridge_WP\Database\DB_Users;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Users_List_Table extends \WP_List_Table {

	public function __construct( private DB_Users $db_users ) {
		parent::__construct( [
			'singular' => 'line_user',
			'plural'   => 'line_users',
			'ajax'     => false,
		] );
	}

	public function get_columns(): array {
		return [
			'cb'           => '<input type="checkbox">',
			'wp_user'      => __( 'ผู้ใช้ WordPress', 'line-bridge-wp' ),
			'line_profile' => __( 'LINE Profile', 'line-bridge-wp' ),
			'line_user_id' => __( 'LINE User ID', 'line-bridge-wp' ),
			'linked_at'    => __( 'เชื่อมต่อเมื่อ', 'line-bridge-wp' ),
			'actions'      => __( 'จัดการ', 'line-bridge-wp' ),
		];
	}

	public function get_sortable_columns(): array {
		return [
			'linked_at' => [ 'linked_at', true ],
		];
	}

	public function prepare_items(): void {
		$per_page = 20;
		$page     = $this->get_pagenum();

		$this->items = $this->db_users->get_all( $per_page, $page );
		$total       = $this->db_users->count();

		$this->set_pagination_args( [
			'total_items' => $total,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total / $per_page ),
		] );

		$this->_column_headers = [
			$this->get_columns(),
			[],
			$this->get_sortable_columns(),
		];
	}

	protected function column_default( $item, $column_name ): string {
		return esc_html( $item->$column_name ?? '' );
	}

	protected function column_cb( $item ): string {
		return '<input type="checkbox" name="line_user[]" value="' . esc_attr( $item->wp_user_id ) . '">';
	}

	protected function column_wp_user( $item ): string {
		$user = get_user_by( 'id', $item->wp_user_id );
		if ( ! $user ) {
			return '—';
		}
		return '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $item->wp_user_id ) ) . '">'
			. esc_html( $user->display_name )
			. '</a><br><small>' . esc_html( $user->user_email ) . '</small>';
	}

	protected function column_line_profile( $item ): string {
		$html = '';
		if ( $item->picture_url ) {
			$html .= '<img src="' . esc_url( $item->picture_url ) . '" width="40" height="40" style="border-radius:50%;vertical-align:middle;margin-right:8px">';
		}
		$html .= '<strong>' . esc_html( $item->display_name ) . '</strong>';
		return $html;
	}

	protected function column_line_user_id( $item ): string {
		return '<code>' . esc_html( $item->line_user_id ) . '</code>';
	}

	protected function column_linked_at( $item ): string {
		return esc_html( wp_date( 'd/m/Y H:i', strtotime( $item->linked_at ) ) );
	}

	protected function column_actions( $item ): string {
		return '<button class="button button-small line-bridge-unlink-btn" '
			. 'data-user-id="' . esc_attr( $item->wp_user_id ) . '" '
			. 'data-nonce="' . esc_attr( wp_create_nonce( 'line_bridge_admin' ) ) . '">'
			. esc_html__( 'ยกเลิกเชื่อมต่อ', 'line-bridge-wp' )
			. '</button>';
	}
}
