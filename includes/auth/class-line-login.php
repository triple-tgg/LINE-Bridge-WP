<?php

namespace Line_Bridge_WP\Auth;

use Line_Bridge_WP\Database\DB_Users;
use Line_Bridge_WP\Utilities\Crypto;
use Line_Bridge_WP\Utilities\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Line_Login {

	private const LOGIN_URL   = 'https://access.line.me/oauth2/v2.1/authorize';
	private const TOKEN_URL   = 'https://api.line.me/oauth2/v2.1/token';
	private const PROFILE_URL = 'https://api.line.me/v2/profile';
	private const VERIFY_URL  = 'https://api.line.me/oauth2/v2.1/verify';

	public function __construct(
		private Token_Manager $token_manager,
		private DB_Users $db_users
	) {}

	public function get_authorization_url(): string {
		$state  = Crypto::generate_state();
		$nonce  = Crypto::generate_state( 16 );
		set_transient( 'line_bridge_oauth_state_' . $state, $nonce, 15 * MINUTE_IN_SECONDS );

		return self::LOGIN_URL . '?' . http_build_query( [
			'response_type' => 'code',
			'client_id'     => get_option( 'line_bridge_login_channel_id' ),
			'redirect_uri'  => $this->get_redirect_uri(),
			'state'         => $state,
			'scope'         => 'profile openid email',
			'nonce'         => $nonce,
			'bot_prompt'    => 'aggressive',
		] );
	}

	public function get_redirect_uri(): string {
		return rest_url( 'line-bridge/v1/oauth/callback' );
	}

	public function handle_callback( string $code, string $state ): \WP_User|\WP_Error {
		$nonce = get_transient( 'line_bridge_oauth_state_' . $state );
		if ( ! $nonce ) {
			return new \WP_Error( 'invalid_state', __( 'Invalid state token', 'line-bridge-wp' ) );
		}
		delete_transient( 'line_bridge_oauth_state_' . $state );

		$token_data = $this->exchange_code_for_token( $code );
		if ( is_wp_error( $token_data ) ) {
			return $token_data;
		}

		$profile = $this->get_line_profile( $token_data['access_token'] );
		if ( is_wp_error( $profile ) ) {
			return $profile;
		}

		return $this->create_or_login_user( $profile, $token_data );
	}

	public function exchange_code_for_token( string $code ): array|\WP_Error {
		$response = wp_remote_post( self::TOKEN_URL, [
			'body' => [
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'redirect_uri'  => $this->get_redirect_uri(),
				'client_id'     => get_option( 'line_bridge_login_channel_id' ),
				'client_secret' => get_option( 'line_bridge_login_channel_secret' ),
			],
		] );

		if ( is_wp_error( $response ) ) {
			Logger::error( 'Token exchange failed', [ 'error' => $response->get_error_message() ] );
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) {
			Logger::error( 'Token exchange: no access_token', $body );
			return new \WP_Error( 'token_error', $body['error_description'] ?? 'Token exchange failed' );
		}

		return $body;
	}

	public function get_line_profile( string $access_token ): array|\WP_Error {
		$response = wp_remote_get( self::PROFILE_URL, [
			'headers' => [ 'Authorization' => 'Bearer ' . $access_token ],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['userId'] ) ) {
			return new \WP_Error( 'profile_error', 'Could not retrieve LINE profile' );
		}

		return $body;
	}

	public function create_or_login_user( array $profile, array $token_data ): \WP_User|\WP_Error {
		$line_user_id = $profile['userId'];
		$existing     = $this->db_users->get_by_line_user_id( $line_user_id );

		if ( $existing ) {
			$wp_user = get_user_by( 'id', $existing->wp_user_id );
			if ( ! $wp_user ) {
				$this->db_users->delete( $existing->wp_user_id );
			} else {
				$this->db_users->update( $wp_user->ID, [
					'display_name' => $profile['displayName'] ?? '',
					'picture_url'  => $profile['pictureUrl'] ?? '',
				] );
				$this->token_manager->save_tokens( $wp_user->ID, $token_data );
				return $wp_user;
			}
		}

		if ( ! get_option( 'line_bridge_login_auto_register', '1' ) ) {
			return new \WP_Error(
				'no_linked_account',
				__( 'ไม่พบบัญชีที่เชื่อมต่อกับ LINE นี้ กรุณาล็อกอินด้วยบัญชีปกติก่อน แล้วเชื่อมต่อ LINE ในหน้าบัญชีของฉัน', 'line-bridge-wp' )
			);
		}

		return $this->register_new_user( $profile, $token_data );
	}

	private function register_new_user( array $profile, array $token_data ): \WP_User|\WP_Error {
		$email    = $profile['email'] ?? '';
		$username = $this->generate_username( $profile['displayName'] ?? 'line_user' );

		if ( $email && email_exists( $email ) ) {
			$wp_user = get_user_by( 'email', $email );
			if ( $wp_user ) {
				$this->link_account( $wp_user->ID, $profile, $token_data );
				return $wp_user;
			}
		}

		$user_id = wp_insert_user( [
			'user_login'   => $username,
			'user_pass'    => wp_generate_password( 24 ),
			'user_email'   => $email ?: $username . '@line.user',
			'display_name' => $profile['displayName'] ?? '',
			'role'         => get_option( 'default_role', 'subscriber' ),
		] );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$this->link_account( $user_id, $profile, $token_data );

		do_action( 'line_bridge_user_registered', $user_id, $profile );

		return get_user_by( 'id', $user_id );
	}

	public function link_account( int $wp_user_id, array $profile, array $token_data ): bool|\WP_Error {
		$existing = $this->db_users->get_by_line_user_id( $profile['userId'] );
		if ( $existing && (int) $existing->wp_user_id !== $wp_user_id ) {
			return new \WP_Error( 'already_linked', __( 'บัญชี LINE นี้ถูกเชื่อมต่อกับผู้ใช้อื่นแล้ว', 'line-bridge-wp' ) );
		}

		$existing_wp = $this->db_users->get_by_wp_user_id( $wp_user_id );
		$expires = isset( $token_data['expires_in'] )
			? gmdate( 'Y-m-d H:i:s', time() + (int) $token_data['expires_in'] )
			: null;

		if ( $existing_wp ) {
			$this->db_users->update( $wp_user_id, [
				'line_user_id' => $profile['userId'],
				'display_name' => $profile['displayName'] ?? '',
				'picture_url'  => $profile['pictureUrl'] ?? '',
				'email'        => $profile['email'] ?? '',
				'access_token' => Crypto::encrypt( $token_data['access_token'] ?? '' ),
				'token_expires'=> $expires,
			] );
		} else {
			$this->db_users->insert( [
				'wp_user_id'   => $wp_user_id,
				'line_user_id' => $profile['userId'],
				'display_name' => $profile['displayName'] ?? '',
				'picture_url'  => $profile['pictureUrl'] ?? '',
				'email'        => $profile['email'] ?? '',
				'access_token' => Crypto::encrypt( $token_data['access_token'] ?? '' ),
				'token_expires'=> $expires,
			] );
		}

		do_action( 'line_bridge_user_linked', $wp_user_id, $profile );
		return true;
	}

	public function unlink_account( int $wp_user_id ): bool {
		$result = $this->db_users->delete( $wp_user_id );
		if ( $result ) {
			do_action( 'line_bridge_user_unlinked', $wp_user_id );
		}
		return $result;
	}

	public function render_login_button(): string {
		$url = $this->get_authorization_url();
		ob_start();
		?>
		<div class="line-bridge-login-wrap">
			<a href="<?php echo esc_url( $url ); ?>" class="line-bridge-login-btn">
				<img src="<?php echo esc_url( LINE_BRIDGE_WP_URL . 'assets/images/line-logo.svg' ); ?>" alt="LINE" width="20" height="20">
				<?php esc_html_e( 'เข้าสู่ระบบด้วย LINE', 'line-bridge-wp' ); ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	private function generate_username( string $display_name ): string {
		$base     = 'line_' . preg_replace( '/[^a-zA-Z0-9]/', '', sanitize_user( $display_name, true ) );
		$base     = strtolower( $base ) ?: 'line_user';
		$username = $base;
		$i        = 1;
		while ( username_exists( $username ) ) {
			$username = $base . '_' . $i++;
		}
		return $username;
	}
}
