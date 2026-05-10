<?php

namespace Line_Bridge_WP\Messaging;

use Line_Bridge_WP\Database\DB_Templates;
use Line_Bridge_WP\Utilities\Template_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Message_Builder {

	public function __construct( private DB_Templates $db_templates ) {}

	public function build( string $template_key, array $vars ): array {
		$tpl = $this->db_templates->get( $template_key );
		if ( ! $tpl ) {
			return $this->fallback_text( $template_key, $vars );
		}

		return match ( $tpl->message_type ) {
			'flex'     => $this->build_flex_from_template( $tpl->body, $vars ),
			'imagemap' => $this->build_imagemap_from_template( $tpl->body, $vars ),
			default    => $this->build_text_from_template( $tpl->body, $vars ),
		};
	}

	public function build_order_flex( \WC_Order $order ): array {
		$vars = Template_Renderer::get_order_vars( $order );
		$tpl  = $this->db_templates->get( 'order_placed' );

		if ( $tpl && $tpl->message_type === 'flex' ) {
			$rendered_json = Template_Renderer::render( $tpl->body, $vars );
			$flex          = json_decode( $rendered_json, true );
			if ( $flex ) {
				return [ $flex ];
			}
		}

		return [ $this->build_text_from_template(
			"🛒 ยืนยันคำสั่งซื้อ #{{ORDER_NUMBER}}\n\nลูกค้า: {{CUSTOMER_NAME}}\nยอดรวม: {{ORDER_TOTAL}}\nสถานะ: {{ORDER_STATUS}}\n\nดูออเดอร์: {{ORDER_URL}}",
			$vars
		) ];
	}

	private function build_text_from_template( string $body, array $vars ): array {
		return [
			'type' => 'text',
			'text' => Template_Renderer::render( $body, $vars ),
		];
	}

	private function build_flex_from_template( string $body, array $vars ): array {
		$rendered = Template_Renderer::render( $body, $vars );
		$flex     = json_decode( $rendered, true );
		if ( ! $flex ) {
			return $this->build_text_from_template( $body, $vars );
		}
		return $flex;
	}

	private function build_imagemap_from_template( string $body, array $vars ): array {
		$rendered = Template_Renderer::render( $body, $vars );
		$data     = json_decode( $rendered, true );
		if ( ! $data ) {
			return $this->build_text_from_template( $body, $vars );
		}
		return $data;
	}

	public function build_image_message( string $image_url, string $preview_url = '' ): array {
		return [
			'type'               => 'image',
			'originalContentUrl' => $image_url,
			'previewImageUrl'    => $preview_url ?: $image_url,
		];
	}

	private function fallback_text( string $key, array $vars ): array {
		return [
			'type' => 'text',
			'text' => sprintf( '[%s] ' . get_bloginfo( 'name' ), strtoupper( $key ) ),
		];
	}
}
