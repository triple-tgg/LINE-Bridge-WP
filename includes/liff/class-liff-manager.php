<?php

namespace Line_Bridge_WP\Liff;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Liff_Manager {

	public function get_liff_id(): string {
		return get_option( 'line_bridge_liff_id', '' );
	}

	public function is_configured(): bool {
		return ! empty( $this->get_liff_id() );
	}

	public function get_liff_url( string $path = '' ): string {
		$liff_id = $this->get_liff_id();
		if ( ! $liff_id ) {
			return '';
		}
		return 'https://liff.line.me/' . rawurlencode( $liff_id ) . ( $path ? '/' . ltrim( $path, '/' ) : '' );
	}

	public function get_order_liff_url( int $order_id ): string {
		return $this->get_liff_url( '?order=' . $order_id );
	}

	public function render_liff_button( int $order_id ): array {
		if ( ! $this->is_configured() ) {
			return [];
		}
		return [
			'type'   => 'button',
			'style'  => 'secondary',
			'action' => [
				'type'  => 'uri',
				'label' => '📱 ดูสถานะใน LINE',
				'uri'   => $this->get_order_liff_url( $order_id ),
			],
		];
	}
}
