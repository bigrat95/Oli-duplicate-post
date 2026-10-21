<?php
/**
 * Rewrite & Republish functionality.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Rewrite_Republish
 */
class Oli_DP_Rewrite_Republish {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_action( 'transition_post_status', array( __CLASS__, 'handle_publish' ), 10, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'show_rewrite_notice' ) );
		add_filter( 'display_post_states', array( __CLASS__, 'add_post_state' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_schedule_meta_box' ) );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'add_publish_box_info' ) );
	}

	/**
	 * Handle post publish for rewrite & republish.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public static function handle_publish( $new_status, $old_status, $post ) {
		// Check if this is a rewrite copy being published.
		$original_post_id = get_post_meta( $post->ID, '_oli_dp_original_post', true );

		if ( ! $original_post_id ) {
			return;
		}

		// Only proceed if the post is being published or scheduled.
		if ( ! in_array( $new_status, array( 'publish', 'future' ), true ) ) {
			return;
		}

		if ( is_user_logged_in() && ( ! current_user_can( 'edit_post', $post->ID ) || ! current_user_can( 'edit_post', $original_post_id ) ) ) {
			return;
		}

		// Verify nonce if present (publish form). Cron-scheduled transitions have no nonce.
		if ( isset( $_POST['oli_dp_rewrite_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oli_dp_rewrite_nonce'] ) ), 'oli_dp_rewrite_publish' ) ) {
				return;
			}
		}

		// Merge the rewrite copy back to the original.
		self::merge_to_original( $post->ID, $original_post_id );

		// Set a transient for success message.
		set_transient( 'oli_dp_republish_success_' . get_current_user_id(), $original_post_id, 30 );
	}

	/**
	 * Merge rewrite copy to original post.
	 *
	 * @param int $rewrite_post_id  The rewrite copy post ID.
	 * @param int $original_post_id The original post ID.
	 * @return bool True on success, false on failure.
	 */
	public static function merge_to_original( $rewrite_post_id, $original_post_id ) {
		$rewrite_post  = get_post( $rewrite_post_id );
		$original_post = get_post( $original_post_id );

		if ( ! $rewrite_post || ! $original_post ) {
			return false;
		}

		// Update the original post with the rewrite content.
		$update_data = array(
			'ID'           => $original_post_id,
			'post_title'   => $rewrite_post->post_title,
			'post_content' => $rewrite_post->post_content,
			'post_excerpt' => $rewrite_post->post_excerpt,
		);

		$result = wp_update_post( $update_data );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		// Copy meta data.
		$settings = get_option( 'oli_dp_settings', Oli_DP_Settings::get_default_settings() );

		// Get all meta from rewrite post.
		$rewrite_meta = get_post_meta( $rewrite_post_id );

		// Get excluded meta keys.
		$excluded_meta    = isset( $settings['excluded_meta'] ) ? $settings['excluded_meta'] : '';
		$excluded_keys    = array_map( 'trim', explode( ',', $excluded_meta ) );
		$default_excluded = array( '_edit_lock', '_edit_last', '_oli_dp_original_post' );
		$excluded_keys    = array_merge( $excluded_keys, $default_excluded );

		foreach ( $rewrite_meta as $meta_key => $meta_values ) {
			// Skip excluded keys.
			if ( self::should_exclude_meta( $meta_key, $excluded_keys ) ) {
				continue;
			}

			// Delete old meta values from original.
			delete_post_meta( $original_post_id, $meta_key );

			// Copy new meta values.
			foreach ( $meta_values as $meta_value ) {
				add_post_meta( $original_post_id, $meta_key, maybe_unserialize( $meta_value ) );
			}
		}

		// Copy taxonomies.
		$taxonomies = get_object_taxonomies( $original_post->post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $rewrite_post_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $terms ) ) {
				wp_set_object_terms( $original_post_id, $terms, $taxonomy );
			}
		}

		// Copy featured image.
		$thumbnail_id = get_post_thumbnail_id( $rewrite_post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $original_post_id, $thumbnail_id );
		} else {
			delete_post_thumbnail( $original_post_id );
		}

		// Delete the rewrite copy.
		delete_post_meta( $original_post_id, '_oli_dp_rewrite_copy' );
		wp_delete_post( $rewrite_post_id, true );

		/**
		 * Action after rewrite & republish merge.
		 *
		 * @param int $original_post_id The original post ID.
		 * @param int $rewrite_post_id  The rewrite copy post ID.
		 */
		do_action( 'oli_dp_rewrite_republish_merged', $original_post_id, $rewrite_post_id );

		return true;
	}

	/**
	 * Check if meta key should be excluded.
	 *
	 * @param string $meta_key      The meta key to check.
	 * @param array  $excluded_keys Array of excluded keys (supports wildcards).
	 * @return bool True if should be excluded, false otherwise.
	 */
	private static function should_exclude_meta( $meta_key, $excluded_keys ) {
		foreach ( $excluded_keys as $pattern ) {
			if ( empty( $pattern ) ) {
				continue;
			}

			$regex_pattern = '/^' . str_replace( array( '\*', '\?' ), array( '.*', '.' ), preg_quote( $pattern, '/' ) ) . '$/';

			if ( preg_match( $regex_pattern, $meta_key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Show admin notice for rewrite copies.
	 */
	public static function show_rewrite_notice() {
		// Check for success message.
		$success_post_id = get_transient( 'oli_dp_republish_success_' . get_current_user_id() );
		if ( $success_post_id ) {
			delete_transient( 'oli_dp_republish_success_' . get_current_user_id() );
			$post = get_post( $success_post_id );
			if ( $post ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						printf(
							/* translators: %s: post title */
							esc_html__( 'Successfully republished "%s" with your changes!', 'oli-duplicate-post' ),
							esc_html( $post->post_title )
						);
						?>
					</p>
				</div>
				<?php
			}
		}

		$screen = get_current_screen();

		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check to display notice.
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $post_id ) {
			return;
		}

		$original_post_id = get_post_meta( $post_id, '_oli_dp_original_post', true );

		if ( ! $original_post_id ) {
			return;
		}

		$original_post = get_post( $original_post_id );

		if ( ! $original_post ) {
			return;
		}

		$edit_link    = get_edit_post_link( $original_post_id );
		$view_link    = get_permalink( $original_post_id );
		$compare_link = add_query_arg(
			array(
				'page'     => 'oli-dp-compare',
				'original' => $original_post_id,
				'rewrite'  => $post_id,
			),
			admin_url( 'admin.php' )
		);

		?>
		<div class="notice notice-info">
			<p>
				<?php
				printf(
					/* translators: 1: Original post title, 2: Edit link, 3: View link, 4: Compare link */
					wp_kses_post( __( 'This is a <strong>Rewrite & Republish</strong> copy of "%1$s". When you publish this post, it will replace the original. <a href="%2$s">Edit original</a> | <a href="%3$s" target="_blank">View original</a> | <a href="%4$s">Compare changes</a>', 'oli-duplicate-post' ) ),
					esc_html( $original_post->post_title ),
					esc_url( $edit_link ),
					esc_url( $view_link ),
					esc_url( $compare_link )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add post state for rewrite copies.
	 *
	 * @param array   $post_states Array of post states.
	 * @param WP_Post $post        Post object.
	 * @return array Modified post states.
	 */
	public static function add_post_state( $post_states, $post ) {
		$original_post_id = get_post_meta( $post->ID, '_oli_dp_original_post', true );

		if ( $original_post_id ) {
			$post_states['oli_dp_rewrite'] = __( 'Rewrite & Republish', 'oli-duplicate-post' );
		}

		$rewrite_copy_id = get_post_meta( $post->ID, '_oli_dp_rewrite_copy', true );

		if ( $rewrite_copy_id ) {
			$post_states['oli_dp_original'] = __( 'Original (being rewritten)', 'oli-duplicate-post' );
		}

		return $post_states;
	}

	/**
	 * Add schedule meta box for rewrite copies.
	 */
	public static function add_schedule_meta_box() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check to determine if meta box should be added.
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		if ( ! $post_id ) {
			return;
		}

		$original_post_id = get_post_meta( $post_id, '_oli_dp_original_post', true );
		if ( ! $original_post_id ) {
			return;
		}

		add_meta_box(
			'oli_dp_republish_schedule',
			__( 'Republish Schedule', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_schedule_meta_box' ),
			$screen->post_type,
			'side',
			'high'
		);
	}

	/**
	 * Render schedule meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function render_schedule_meta_box( $post ) {
		$original_post_id = get_post_meta( $post->ID, '_oli_dp_original_post', true );
		if ( ! $original_post_id ) {
			return;
		}

		$original_post = get_post( $original_post_id );
		?>
		<div class="oli-dp-schedule-info">
			<p>
				<strong><?php esc_html_e( 'Original Post:', 'oli-duplicate-post' ); ?></strong><br>
				<?php echo esc_html( $original_post->post_title ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'When you publish or schedule this post, it will automatically replace the original post with your changes.', 'oli-duplicate-post' ); ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Actions:', 'oli-duplicate-post' ); ?></strong>
			</p>
			<ul class="oli-dp-schedule-list">
				<li><?php esc_html_e( 'Publish Now → Replaces original immediately', 'oli-duplicate-post' ); ?></li>
				<li><?php esc_html_e( 'Schedule → Replaces original at scheduled time', 'oli-duplicate-post' ); ?></li>
				<li><?php esc_html_e( 'Save Draft → Keep editing without publishing', 'oli-duplicate-post' ); ?></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Add info to publish box for rewrite copies.
	 */
	public static function add_publish_box_info() {
		global $post;

		if ( ! $post ) {
			return;
		}

		$original_post_id = get_post_meta( $post->ID, '_oli_dp_original_post', true );
		if ( ! $original_post_id ) {
			return;
		}

		?>
		<div class="misc-pub-section oli-dp-republish-info">
			<span class="dashicons dashicons-update oli-dp-republish-icon"></span>
			<strong><?php esc_html_e( 'Rewrite & Republish Mode', 'oli-duplicate-post' ); ?></strong>
			<p class="description oli-dp-republish-note">
				<?php esc_html_e( 'Publishing will replace the original post.', 'oli-duplicate-post' ); ?>
			</p>
			<?php wp_nonce_field( 'oli_dp_rewrite_publish', 'oli_dp_rewrite_nonce' ); ?>
		</div>
		<?php
	}

	/**
	 * Get comparison data for two posts.
	 *
	 * @param int $post_id_1 First post ID.
	 * @param int $post_id_2 Second post ID.
	 * @return array Comparison data.
	 */
	public static function get_comparison_data( $post_id_1, $post_id_2 ) {
		$post_1 = get_post( $post_id_1 );
		$post_2 = get_post( $post_id_2 );

		if ( ! $post_1 || ! $post_2 ) {
			return array();
		}

		return array(
			'title'   => array(
				'original' => $post_1->post_title,
				'rewrite'  => $post_2->post_title,
			),
			'content' => array(
				'original' => $post_1->post_content,
				'rewrite'  => $post_2->post_content,
			),
			'excerpt' => array(
				'original' => $post_1->post_excerpt,
				'rewrite'  => $post_2->post_excerpt,
			),
		);
	}
}
