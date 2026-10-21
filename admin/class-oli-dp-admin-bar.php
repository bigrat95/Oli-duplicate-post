<?php
/**
 * Admin bar duplicate links.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Admin_Bar
 */
class Oli_DP_Admin_Bar {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_items' ), 100 );
	}

	/**
	 * Add admin bar items.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public static function add_admin_bar_items( $wp_admin_bar ) {
		// Check if admin bar items should be shown.
		$settings = get_option( 'oli_dp_settings', Oli_DP_Settings::get_default_settings() );

		if ( empty( $settings['show_admin_bar'] ) ) {
			return;
		}

		// Get the current post.
		$post = get_post();

		if ( ! $post ) {
			// Try to get post from query string.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check to display admin bar items.
			if ( isset( $_GET['post'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$post = get_post( absint( $_GET['post'] ) );
			}
		}

		if ( ! $post ) {
			return;
		}

		// Check if user can duplicate this post.
		if ( ! Oli_DP_Permissions::can_duplicate( $post->ID ) ) {
			return;
		}

		// Add parent menu item.
		$wp_admin_bar->add_menu(
			array(
				'id'    => 'oli_dp_duplicate',
				'title' => __( 'Duplicate This', 'oli-duplicate-post' ),
				'href'  => '#',
			)
		);

		// Clone action.
		$clone_url = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'clone' );
		$wp_admin_bar->add_menu(
			array(
				'parent' => 'oli_dp_duplicate',
				'id'     => 'oli_dp_clone',
				'title'  => __( 'Clone', 'oli-duplicate-post' ),
				'href'   => $clone_url,
			)
		);

		// New Draft action.
		$new_draft_url = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'new_draft' );
		$wp_admin_bar->add_menu(
			array(
				'parent' => 'oli_dp_duplicate',
				'id'     => 'oli_dp_new_draft',
				'title'  => __( 'New Draft', 'oli-duplicate-post' ),
				'href'   => $new_draft_url,
			)
		);

		// Rewrite & Republish action (only for published posts).
		if ( 'publish' === $post->post_status ) {
			$existing_rewrite = get_post_meta( $post->ID, '_oli_dp_rewrite_copy', true );

			if ( ! $existing_rewrite ) {
				$rewrite_url = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'rewrite_republish' );
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'oli_dp_duplicate',
						'id'     => 'oli_dp_rewrite',
						'title'  => __( 'Rewrite & Republish', 'oli-duplicate-post' ),
						'href'   => $rewrite_url,
					)
				);
			} else {
				$edit_url = get_edit_post_link( $existing_rewrite );
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'oli_dp_duplicate',
						'id'     => 'oli_dp_rewrite',
						'title'  => __( 'Edit Rewrite Copy', 'oli-duplicate-post' ),
						'href'   => $edit_url,
					)
				);
			}
		}
	}
}
