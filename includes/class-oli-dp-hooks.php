<?php
/**
 * WordPress hooks and filters integration.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Hooks
 */
class Oli_DP_Hooks {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_action( 'admin_post_oli_dp_duplicate_post', array( __CLASS__, 'handle_duplicate_action' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_quick_action' ) );
		add_action( 'admin_notices', array( __CLASS__, 'show_success_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'show_error_notice' ) );
	}

	/**
	 * Register quick action handler.
	 */
	public static function register_quick_action() {
		// This allows direct duplication via URL parameters.
		if ( isset( $_GET['oli_dp_action'] ) && isset( $_GET['post'] ) ) {
			$action  = sanitize_text_field( wp_unslash( $_GET['oli_dp_action'] ) );
			$post_id = absint( $_GET['post'] );
			$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'oli_dp_duplicate_' . $post_id ) ) {
				wp_die( esc_html__( 'Security check failed.', 'oli-duplicate-post' ) );
			}

			self::process_duplicate_request( $post_id, $action );
		}
	}

	/**
	 * Handle duplicate action from admin_post hook.
	 */
	public static function handle_duplicate_action() {
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['oli_dp_nonce'] ) ) {
			self::redirect_with_error( 'invalid' );
		}

		$post_id = absint( $_POST['post_id'] );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['oli_dp_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'oli_dp_duplicate_' . $post_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'oli-duplicate-post' ) );
		}

		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'clone';

		self::process_duplicate_request( $post_id, $mode );
	}

	/**
	 * Duplicate a post and redirect with a notice.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action  Action or mode slug.
	 */
	private static function process_duplicate_request( $post_id, $action ) {
		if ( ! Oli_DP_Permissions::can_duplicate( $post_id ) ) {
			self::redirect_with_error( 'permission' );
		}

		$mode_map = array(
			'clone'             => 'clone',
			'new_draft'         => 'new_draft',
			'rewrite_republish' => 'rewrite_republish',
		);

		if ( ! isset( $mode_map[ $action ] ) ) {
			self::redirect_with_error( 'invalid' );
		}

		$mode = $mode_map[ $action ];

		if ( 'rewrite_republish' === $mode && ! Oli_DP_Settings::is_rewrite_enabled() ) {
			self::redirect_with_error( 'rewrite_disabled' );
		}

		$new_post_id = Oli_DP_Duplicator::duplicate( $post_id, $mode );

		if ( is_wp_error( $new_post_id ) ) {
			self::redirect_with_error( $new_post_id->get_error_code() );
		}

		self::redirect_after_success( $post_id, $new_post_id, $mode );
	}

	/**
	 * Redirect after a successful copy.
	 *
	 * @param int    $original_id Original post ID.
	 * @param int    $new_post_id New post ID.
	 * @param string $mode        Duplication mode.
	 */
	private static function redirect_after_success( $original_id, $new_post_id, $mode ) {
		$original = get_post( $original_id );
		$fallback = $original ? admin_url( 'edit.php?post_type=' . $original->post_type ) : admin_url();
		$referer  = wp_get_referer();

		if ( 'rewrite_republish' === $mode ) {
			$redirect_url = get_edit_post_link( $new_post_id, 'raw' );
		} else {
			$redirect_url = $referer ? $referer : $fallback;
		}

		$redirect_url = add_query_arg(
			array(
				'oli_dp_duplicated' => $new_post_id,
				'oli_dp_mode'       => $mode,
			),
			$redirect_url
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Redirect back with an error notice.
	 *
	 * @param string $code Error code.
	 */
	private static function redirect_with_error( $code ) {
		$redirect_url = wp_get_referer();
		if ( ! $redirect_url ) {
			$redirect_url = admin_url();
		}

		$redirect_url = add_query_arg( 'oli_dp_error', sanitize_key( $code ), $redirect_url );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Show success notice after duplication.
	 */
	public static function show_success_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of success parameter we set ourselves.
		if ( ! isset( $_GET['oli_dp_duplicated'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of success parameter we set ourselves.
		$new_post_id = absint( $_GET['oli_dp_duplicated'] );
		$new_post    = $new_post_id ? get_post( $new_post_id ) : null;

		if ( ! $new_post ) {
			return;
		}

		$edit_link = get_edit_post_link( $new_post_id );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %s: duplicated post title */
					esc_html__( 'Copy created: %s', 'oli-duplicate-post' ),
					esc_html( $new_post->post_title )
				);
				if ( $edit_link ) {
					echo ' ';
					printf(
						'<a href="%s">%s</a>',
						esc_url( $edit_link ),
						esc_html__( 'Edit copy', 'oli-duplicate-post' )
					);
				}
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Show an error notice after a failed duplication.
	 */
	public static function show_error_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of error parameter we set ourselves.
		if ( ! isset( $_GET['oli_dp_error'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of error parameter we set ourselves.
		$code = sanitize_key( wp_unslash( $_GET['oli_dp_error'] ) );

		$messages = array(
			'permission'        => __( 'You do not have permission to duplicate this post.', 'oli-duplicate-post' ),
			'permission_denied' => __( 'You do not have permission to duplicate this post.', 'oli-duplicate-post' ),
			'invalid'           => __( 'The copy could not be created.', 'oli-duplicate-post' ),
			'invalid_post'      => __( 'The copy could not be created.', 'oli-duplicate-post' ),
			'rewrite_disabled'  => __( 'Rewrite & Republish is disabled.', 'oli-duplicate-post' ),
			'failed'            => __( 'The copy could not be created.', 'oli-duplicate-post' ),
		);

		$message = isset( $messages[ $code ] ) ? $messages[ $code ] : $messages['failed'];
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Get duplicate URL for a post.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $action  The action: 'clone', 'new_draft', or 'rewrite_republish'.
	 * @return string The duplicate URL.
	 */
	public static function get_duplicate_url( $post_id, $action = 'clone' ) {
		$url = admin_url( 'admin.php' );
		$url = add_query_arg(
			array(
				'oli_dp_action' => $action,
				'post'          => $post_id,
				'_wpnonce'      => wp_create_nonce( 'oli_dp_duplicate_' . $post_id ),
			),
			$url
		);

		return $url;
	}
}
