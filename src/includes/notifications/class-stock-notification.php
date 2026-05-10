<?php

namespace Line_Bridge_WP\Notifications;

use Line_Bridge_WP\Utilities\Template_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Stock_Notification {

	public function __construct( private Notification_Manager $notif_manager ) {
		$this->hooks();
	}

	private function hooks(): void {
		add_action( 'woocommerce_low_stock',  [ $this, 'on_low_stock' ], 10, 1 );
		add_action( 'woocommerce_no_stock',   [ $this, 'on_out_of_stock' ], 10, 1 );
	}

	public function on_low_stock( \WC_Product $product ): void {
		if ( ! $this->notif_manager->flag_enabled( 'low_stock' ) ) {
			return;
		}
		$vars = Template_Renderer::get_product_vars( $product );
		$this->notif_manager->send_to_admin( 'admin_low_stock', $vars, [
			'reference_id'   => $product->get_id(),
			'reference_type' => 'product',
		] );
	}

	public function on_out_of_stock( \WC_Product $product ): void {
		if ( ! $this->notif_manager->flag_enabled( 'out_of_stock' ) ) {
			return;
		}
		$vars = Template_Renderer::get_product_vars( $product );
		$this->notif_manager->send_to_admin( 'admin_out_of_stock', $vars, [
			'reference_id'   => $product->get_id(),
			'reference_type' => 'product',
		] );
	}
}
