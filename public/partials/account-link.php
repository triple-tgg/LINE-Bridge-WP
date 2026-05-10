<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/** @var object|null $line_row */
?>
<div class="line-bridge-account-section">
	<h2><?php esc_html_e( 'เชื่อมต่อ LINE', 'line-bridge-wp' ); ?></h2>

	<?php if ( $line_row ) : ?>
		<div class="line-bridge-connected">
			<?php if ( $line_row->picture_url ) : ?>
				<img src="<?php echo esc_url( $line_row->picture_url ); ?>" alt="<?php echo esc_attr( $line_row->display_name ); ?>" class="line-bridge-avatar">
			<?php endif; ?>
			<p>
				<strong><?php esc_html_e( 'เชื่อมต่อแล้วในชื่อ:', 'line-bridge-wp' ); ?></strong>
				<?php echo esc_html( $line_row->display_name ); ?>
			</p>
			<p class="line-bridge-linked-at">
				<?php
				printf(
					/* translators: %s: linked date */
					esc_html__( 'เชื่อมต่อเมื่อ: %s', 'line-bridge-wp' ),
					esc_html( wp_date( 'd/m/Y H:i', strtotime( $line_row->linked_at ) ) )
				);
				?>
			</p>
			<button
				class="button line-bridge-unlink-btn"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>"
				data-url="<?php echo esc_url( rest_url( 'line-bridge/v1/oauth/unlink' ) ); ?>">
				<?php esc_html_e( 'ยกเลิกการเชื่อมต่อ LINE', 'line-bridge-wp' ); ?>
			</button>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'คุณยังไม่ได้เชื่อมต่อบัญชี LINE กรุณาคลิกปุ่มด้านล่างเพื่อเชื่อมต่อ', 'line-bridge-wp' ); ?></p>
		<?php
		$login_obj = new \Line_Bridge_WP\Auth\Line_Login(
			new \Line_Bridge_WP\Auth\Token_Manager( new \Line_Bridge_WP\Database\DB_Users() ),
			new \Line_Bridge_WP\Database\DB_Users()
		);
		echo wp_kses_post( $login_obj->render_login_button() );
		?>
	<?php endif; ?>
</div>
