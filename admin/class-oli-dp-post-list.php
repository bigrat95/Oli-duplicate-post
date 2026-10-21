<?php
/**
 * Post list row actions.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Post_List
 */
class Oli_DP_Post_List {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_filter( 'post_row_actions', array( __CLASS__, 'add_row_actions' ), 10, 2 );
		add_filter( 'page_row_actions', array( __CLASS__, 'add_row_actions' ), 10, 2 );
	}

	/**
	 * Add duplicate row actions.
	 *
	 * @param array   $actions Existing row actions.
	 * @param WP_Post $post    Post object.
	 * @return array Modified row actions.
	 */
	public static function add_row_actions( $actions, $post ) {
		// Check if row actions should be shown.
		$settings = Oli_DP_Settings::get_settings();

		if ( empty( $settings['show_row_actions'] ) ) {
			return $actions;
		}

		// Check if user can duplicate this post.
		if ( ! Oli_DP_Permissions::can_duplicate( $post->ID ) ) {
			return $actions;
		}

		$duplicate_actions = array();

		// Clone action.
		$clone_url                         = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'clone' );
		$duplicate_actions['oli_dp_clone'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $clone_url ),
			/* translators: %s: post title */
			esc_attr( sprintf( __( 'Clone &#8220;%s&#8221;', 'oli-duplicate-post' ), $post->post_title ) ),
			esc_html__( 'Clone', 'oli-duplicate-post' )
		);

		// New Draft action.
		$new_draft_url                         = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'new_draft' );
		$duplicate_actions['oli_dp_new_draft'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $new_draft_url ),
			/* translators: %s: post title */
			esc_attr( sprintf( __( 'New Draft of &#8220;%s&#8221;', 'oli-duplicate-post' ), $post->post_title ) ),
			esc_html__( 'New Draft', 'oli-duplicate-post' )
		);

		// Rewrite & Republish action (only for published posts, when enabled).
		if ( Oli_DP_Settings::is_rewrite_enabled( $settings ) && 'publish' === $post->post_status ) {
			// Check if there's already a rewrite copy.
			$existing_rewrite = get_post_meta( $post->ID, '_oli_dp_rewrite_copy', true );

			if ( ! $existing_rewrite ) {
				$rewrite_url                         = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'rewrite_republish' );
				$duplicate_actions['oli_dp_rewrite'] = sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					esc_url( $rewrite_url ),
					/* translators: %s: post title */
					esc_attr( sprintf( __( 'Rewrite & Republish &#8220;%s&#8221;', 'oli-duplicate-post' ), $post->post_title ) ),
					esc_html__( 'Rewrite & Republish', 'oli-duplicate-post' )
				);
			} else {
				// Link to existing rewrite copy.
				$edit_url                            = get_edit_post_link( $existing_rewrite );
				$duplicate_actions['oli_dp_rewrite'] = sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					esc_url( $edit_url ),
					/* translators: %s: post title */
					esc_attr( sprintf( __( 'Edit Rewrite Copy of &#8220;%s&#8221;', 'oli-duplicate-post' ), $post->post_title ) ),
					esc_html__( 'Edit Rewrite Copy', 'oli-duplicate-post' )
				);
			}
		}

		// Insert after 'edit' action if it exists.
		if ( isset( $actions['edit'] ) ) {
			$offset  = array_search( 'edit', array_keys( $actions ), true );
			$actions = array_slice( $actions, 0, $offset + 1, true ) +
						$duplicate_actions +
						array_slice( $actions, $offset + 1, null, true );
		} else {
			$actions = $duplicate_actions + $actions;
		}

		return $actions;
	}
}
