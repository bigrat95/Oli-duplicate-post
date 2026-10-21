<?php
/**
 * Bulk actions for duplicating posts.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Bulk_Actions
 */
class Oli_DP_Bulk_Actions {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_filter( 'bulk_actions-edit-post', array( __CLASS__, 'add_bulk_actions' ) );
		add_filter( 'bulk_actions-edit-page', array( __CLASS__, 'add_bulk_actions' ) );

		add_filter( 'handle_bulk_actions-edit-post', array( __CLASS__, 'handle_bulk_action' ), 10, 3 );
		add_filter( 'handle_bulk_actions-edit-page', array( __CLASS__, 'handle_bulk_action' ), 10, 3 );

		// Add bulk action for custom post types.
		add_action( 'admin_init', array( __CLASS__, 'register_custom_post_type_bulk_actions' ) );

		add_action( 'admin_notices', array( __CLASS__, 'show_bulk_action_notice' ) );
	}

	/**
	 * Register bulk actions for custom post types.
	 */
	public static function register_custom_post_type_bulk_actions() {
		$settings           = Oli_DP_Settings::get_settings();
		$enabled_post_types = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();

		foreach ( $enabled_post_types as $post_type ) {
			if ( in_array( $post_type, array( 'post', 'page' ), true ) ) {
				continue;
			}

			add_filter( 'bulk_actions-edit-' . $post_type, array( __CLASS__, 'add_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-' . $post_type, array( __CLASS__, 'handle_bulk_action' ), 10, 3 );
		}
	}

	/**
	 * Add bulk actions to post list.
	 *
	 * @param array $actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public static function add_bulk_actions( $actions ) {
		// Check if bulk actions should be shown.
		$settings = Oli_DP_Settings::get_settings();

		if ( empty( $settings['show_bulk_actions'] ) ) {
			return $actions;
		}

		$actions['oli_dp_clone']     = __( 'Clone', 'oli-duplicate-post' );
		$actions['oli_dp_new_draft'] = __( 'New Draft', 'oli-duplicate-post' );

		return $actions;
	}

	/**
	 * Handle bulk action.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $action      Action name.
	 * @param array  $post_ids    Array of post IDs.
	 * @return string Modified redirect URL.
	 */
	public static function handle_bulk_action( $redirect_to, $action, $post_ids ) {
		if ( ! in_array( $action, array( 'oli_dp_clone', 'oli_dp_new_draft' ), true ) ) {
			return $redirect_to;
		}

		// Verify nonce.
		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return $redirect_to;
		}

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'bulk-posts' ) && ! wp_verify_nonce( $nonce, 'bulk-pages' ) ) {
			return $redirect_to;
		}

		$mode             = ( 'oli_dp_clone' === $action ) ? 'clone' : 'new_draft';
		$duplicated_count = 0;

		foreach ( $post_ids as $post_id ) {
			if ( ! Oli_DP_Permissions::can_duplicate( $post_id ) ) {
				continue;
			}

			$new_post_id = Oli_DP_Duplicator::duplicate( $post_id, $mode );

			if ( ! is_wp_error( $new_post_id ) ) {
				++$duplicated_count;
			}
		}

		$redirect_to = add_query_arg( 'oli_dp_bulk_duplicated', $duplicated_count, $redirect_to );

		return $redirect_to;
	}

	/**
	 * Show bulk action notice.
	 */
	public static function show_bulk_action_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of success parameter we set ourselves.
		if ( ! isset( $_REQUEST['oli_dp_bulk_duplicated'] ) ) {
			return;
		}

		$count = absint( $_REQUEST['oli_dp_bulk_duplicated'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $count > 0 ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				/* translators: %d: number of posts duplicated */
				esc_html( sprintf( _n( '%d post duplicated.', '%d posts duplicated.', $count, 'oli-duplicate-post' ), $count ) )
			);
		}
	}
}
