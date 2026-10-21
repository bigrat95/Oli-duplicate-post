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

			if ( ! Oli_DP_Permissions::can_duplicate( $post_id ) ) {
				wp_die( esc_html__( 'You do not have permission to duplicate this post.', 'oli-duplicate-post' ) );
			}

			$mode_map = array(
				'clone'             => 'clone',
				'new_draft'         => 'new_draft',
				'rewrite_republish' => 'rewrite_republish',
			);

			if ( ! isset( $mode_map[ $action ] ) ) {
				wp_die( esc_html__( 'Invalid action.', 'oli-duplicate-post' ) );
			}

			$mode        = $mode_map[ $action ];
			$new_post_id = Oli_DP_Duplicator::duplicate( $post_id, $mode );

			if ( is_wp_error( $new_post_id ) ) {
				wp_die( esc_html( $new_post_id->get_error_message() ) );
			}

			// Redirect to edit the new post with success message.
			$redirect_url = add_query_arg(
				array(
					'oli_dp_duplicated' => 1,
					'oli_dp_mode'       => $mode,
				),
				get_edit_post_link( $new_post_id, 'raw' )
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Handle duplicate action from admin_post hook.
	 */
	public static function handle_duplicate_action() {
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['oli_dp_nonce'] ) ) {
			wp_die( esc_html__( 'Invalid request.', 'oli-duplicate-post' ) );
		}

		$post_id = absint( $_POST['post_id'] );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['oli_dp_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'oli_dp_duplicate_' . $post_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'oli-duplicate-post' ) );
		}

		if ( ! Oli_DP_Permissions::can_duplicate( $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to duplicate this post.', 'oli-duplicate-post' ) );
		}

		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'clone';

		$new_post_id = Oli_DP_Duplicator::duplicate( $post_id, $mode );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( esc_html( $new_post_id->get_error_message() ) );
		}

		// Redirect to edit the new post with success message.
		$redirect_url = add_query_arg(
			array(
				'oli_dp_duplicated' => 1,
				'oli_dp_mode'       => $mode,
			),
			get_edit_post_link( $new_post_id, 'raw' )
		);
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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of mode parameter we set ourselves.
		$mode = isset( $_GET['oli_dp_mode'] ) ? sanitize_text_field( wp_unslash( $_GET['oli_dp_mode'] ) ) : 'clone';

		$messages = array(
			'clone'             => __( 'Post cloned successfully!', 'oli-duplicate-post' ),
			'new_draft'         => __( 'New draft created successfully!', 'oli-duplicate-post' ),
			'rewrite_republish' => __( 'Rewrite copy created successfully! Edit and publish to replace the original.', 'oli-duplicate-post' ),
		);

		$message = isset( $messages[ $mode ] ) ? $messages[ $mode ] : $messages['clone'];

		?>
		<div class="notice notice-success is-dismissible">
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
