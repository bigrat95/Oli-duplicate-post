<?php
/**
 * Core duplication logic.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Duplicator
 */
class Oli_DP_Duplicator {

	/**
	 * Duplicate a post.
	 *
	 * @param int    $post_id The post ID to duplicate.
	 * @param string $mode    The duplication mode: 'clone', 'new_draft', or 'rewrite_republish'.
	 * @param array  $args    Optional arguments to override default behavior.
	 * @return int|WP_Error The new post ID on success, WP_Error on failure.
	 */
	public static function duplicate( $post_id, $mode = 'clone', $args = array() ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post ID.', 'oli-duplicate-post' ) );
		}

		// Check permissions.
		if ( ! Oli_DP_Permissions::can_duplicate( $post_id ) ) {
			return new WP_Error( 'permission_denied', __( 'You do not have permission to duplicate this post.', 'oli-duplicate-post' ) );
		}

		// Get settings.
		$settings = get_option( 'oli_dp_settings', Oli_DP_Settings::get_default_settings() );
		$settings = wp_parse_args( $args, $settings );

		// Prepare post data.
		$new_post_data = self::prepare_post_data( $post, $mode, $settings );

		/**
		 * Filter the post data before duplication.
		 *
		 * @param array   $new_post_data The new post data.
		 * @param WP_Post $post          The original post object.
		 * @param string  $mode          The duplication mode.
		 * @param array   $settings      The settings array.
		 */
		$new_post_data = apply_filters( 'oli_dp_pre_duplicate_post_data', $new_post_data, $post, $mode, $settings );

		// Insert the post.
		$new_post_id = wp_insert_post( $new_post_data, true );

		if ( is_wp_error( $new_post_id ) ) {
			return $new_post_id;
		}

		// Copy meta data.
		if ( $settings['copy_content'] ) {
			self::copy_post_meta( $post_id, $new_post_id, $settings );
		}

		// Copy taxonomies.
		self::copy_taxonomies( $post_id, $new_post_id, $settings );

		// Copy featured image.
		if ( has_post_thumbnail( $post_id ) ) {
			$thumbnail_id = get_post_thumbnail_id( $post_id );
			set_post_thumbnail( $new_post_id, $thumbnail_id );
		}

		// Copy attachments.
		if ( $settings['copy_attachments'] ) {
			self::copy_attachments( $post_id, $new_post_id );
		}

		// Copy children.
		if ( $settings['copy_children'] ) {
			self::copy_children( $post_id, $new_post_id );
		}

		// Copy comments.
		if ( $settings['copy_comments'] ) {
			self::copy_comments( $post_id, $new_post_id );
		}

		// Handle rewrite & republish mode.
		if ( 'rewrite_republish' === $mode ) {
			update_post_meta( $new_post_id, '_oli_dp_original_post', $post_id );
			update_post_meta( $post_id, '_oli_dp_rewrite_copy', $new_post_id );
		}

		/**
		 * Action after post duplication.
		 *
		 * @param int     $new_post_id The new post ID.
		 * @param WP_Post $post        The original post object.
		 * @param string  $mode        The duplication mode.
		 * @param array   $settings    The settings array.
		 */
		do_action( 'oli_dp_post_duplicated', $new_post_id, $post, $mode, $settings );

		return $new_post_id;
	}

	/**
	 * Prepare post data for duplication.
	 *
	 * @param WP_Post $post     The original post.
	 * @param string  $mode     The duplication mode.
	 * @param array   $settings The settings array.
	 * @return array The prepared post data.
	 */
	private static function prepare_post_data( $post, $mode, $settings ) {
		$current_user = wp_get_current_user();

		$new_post_data = array(
			'post_type'   => $post->post_type,
			'post_status' => 'draft',
		);

		// Title.
		if ( $settings['copy_title'] ) {
			$title                       = $post->post_title;
			$prefix                      = $settings['title_prefix'] ?? '';
			$suffix                      = $settings['title_suffix'] ?? '';
			$new_post_data['post_title'] = $prefix . $title . $suffix;
		} else {
			$new_post_data['post_title'] = '';
		}

		// Content.
		if ( $settings['copy_content'] ) {
			$new_post_data['post_content'] = $post->post_content;
		}

		// Excerpt.
		if ( $settings['copy_excerpt'] ) {
			$new_post_data['post_excerpt'] = $post->post_excerpt;
		}

		// Date.
		if ( $settings['copy_date'] ) {
			$new_post_data['post_date']     = $post->post_date;
			$new_post_data['post_date_gmt'] = $post->post_date_gmt;
		}

		// Status.
		if ( $settings['copy_status'] && 'rewrite_republish' !== $mode ) {
			$new_post_data['post_status'] = $post->post_status;
		}

		// Slug.
		if ( ! $settings['copy_slug'] ) {
			// WordPress will auto-generate a unique slug.
			$new_post_data['post_name'] = '';
		} else {
			$new_post_data['post_name'] = $post->post_name;
		}

		// Author.
		if ( $settings['copy_author'] ) {
			$new_post_data['post_author'] = $post->post_author;
		} else {
			$new_post_data['post_author'] = $current_user->ID;
		}

		// Password.
		if ( $settings['copy_password'] ) {
			$new_post_data['post_password'] = $post->post_password;
		}

		// Menu order.
		if ( $settings['copy_menu_order'] ) {
			$new_post_data['menu_order'] = $post->menu_order;
		}

		// Parent.
		$new_post_data['post_parent'] = $post->post_parent;

		// Comment status.
		$new_post_data['comment_status'] = $post->comment_status;
		$new_post_data['ping_status']    = $post->ping_status;

		return $new_post_data;
	}

	/**
	 * Copy post meta.
	 *
	 * @param int   $original_post_id The original post ID.
	 * @param int   $new_post_id      The new post ID.
	 * @param array $settings         The settings array.
	 */
	private static function copy_post_meta( $original_post_id, $new_post_id, $settings ) {
		$post_meta = get_post_meta( $original_post_id );

		// Get excluded meta keys.
		$excluded_meta = isset( $settings['excluded_meta'] ) ? $settings['excluded_meta'] : '';
		$excluded_keys = array_map( 'trim', explode( ',', $excluded_meta ) );

		// Always exclude some internal keys.
		$default_excluded = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_thumbnail_id' );
		$excluded_keys    = array_merge( $excluded_keys, $default_excluded );

		foreach ( $post_meta as $meta_key => $meta_values ) {
			// Check if meta key should be excluded.
			if ( self::should_exclude_meta( $meta_key, $excluded_keys ) ) {
				continue;
			}

			foreach ( $meta_values as $meta_value ) {
				// Handle serialized data.
				$meta_value = maybe_unserialize( $meta_value );
				add_post_meta( $new_post_id, $meta_key, $meta_value );
			}
		}

		// Copy template if setting enabled.
		if ( $settings['copy_template'] ) {
			$template = get_page_template_slug( $original_post_id );
			if ( $template ) {
				update_post_meta( $new_post_id, '_wp_page_template', $template );
			}
		}

		// Copy format if setting enabled.
		if ( $settings['copy_format'] ) {
			$format = get_post_format( $original_post_id );
			if ( $format ) {
				set_post_format( $new_post_id, $format );
			}
		}
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

			// Convert wildcard pattern to regex.
			$regex_pattern = '/^' . str_replace( array( '\*', '\?' ), array( '.*', '.' ), preg_quote( $pattern, '/' ) ) . '$/';

			if ( preg_match( $regex_pattern, $meta_key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Copy taxonomies.
	 *
	 * @param int   $original_post_id The original post ID.
	 * @param int   $new_post_id      The new post ID.
	 * @param array $settings         The settings array.
	 */
	private static function copy_taxonomies( $original_post_id, $new_post_id, $settings ) {
		$post       = get_post( $original_post_id );
		$taxonomies = get_object_taxonomies( $post->post_type );

		// Get excluded taxonomies.
		$excluded_taxonomies = isset( $settings['excluded_taxonomies'] ) ? $settings['excluded_taxonomies'] : '';
		$excluded_tax_array  = array_map( 'trim', explode( ',', $excluded_taxonomies ) );

		foreach ( $taxonomies as $taxonomy ) {
			// Check if taxonomy should be excluded.
			if ( in_array( $taxonomy, $excluded_tax_array, true ) ) {
				continue;
			}

			$terms = wp_get_object_terms( $original_post_id, $taxonomy, array( 'fields' => 'ids' ) );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				wp_set_object_terms( $new_post_id, $terms, $taxonomy );
			}
		}
	}

	/**
	 * Copy attachments.
	 *
	 * @param int $original_post_id The original post ID.
	 * @param int $new_post_id      The new post ID.
	 */
	private static function copy_attachments( $original_post_id, $new_post_id ) {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
				'post_parent'    => $original_post_id,
			)
		);

		foreach ( $attachments as $attachment ) {
			$new_attachment = array(
				'post_title'     => $attachment->post_title,
				'post_content'   => $attachment->post_content,
				'post_excerpt'   => $attachment->post_excerpt,
				'post_parent'    => $new_post_id,
				'post_status'    => $attachment->post_status,
				'post_mime_type' => $attachment->post_mime_type,
				'guid'           => $attachment->guid,
			);

			$new_attachment_id = wp_insert_post( $new_attachment );

			if ( ! is_wp_error( $new_attachment_id ) ) {
				// Copy attachment meta.
				$attachment_meta = get_post_meta( $attachment->ID );
				foreach ( $attachment_meta as $meta_key => $meta_values ) {
					foreach ( $meta_values as $meta_value ) {
						add_post_meta( $new_attachment_id, $meta_key, maybe_unserialize( $meta_value ) );
					}
				}
			}
		}
	}

	/**
	 * Copy children posts.
	 *
	 * @param int $original_post_id The original post ID.
	 * @param int $new_post_id      The new post ID.
	 */
	private static function copy_children( $original_post_id, $new_post_id ) {
		$children = get_posts(
			array(
				'post_type'      => 'any',
				'posts_per_page' => -1,
				'post_parent'    => $original_post_id,
				'post_status'    => 'any',
			)
		);

		foreach ( $children as $child ) {
			// Recursively duplicate child posts.
			$settings                  = get_option( 'oli_dp_settings', Oli_DP_Settings::get_default_settings() );
			$settings['copy_children'] = false; // Prevent infinite recursion.

			$new_child_id = self::duplicate( $child->ID, 'clone', $settings );

			if ( ! is_wp_error( $new_child_id ) ) {
				wp_update_post(
					array(
						'ID'          => $new_child_id,
						'post_parent' => $new_post_id,
					)
				);
			}
		}
	}

	/**
	 * Copy comments.
	 *
	 * @param int $original_post_id The original post ID.
	 * @param int $new_post_id      The new post ID.
	 */
	private static function copy_comments( $original_post_id, $new_post_id ) {
		$comments = get_comments(
			array(
				'post_id' => $original_post_id,
				'status'  => 'all',
			)
		);

		$comment_map = array();

		foreach ( $comments as $comment ) {
			$new_comment_data = array(
				'comment_post_ID'      => $new_post_id,
				'comment_author'       => $comment->comment_author,
				'comment_author_email' => $comment->comment_author_email,
				'comment_author_url'   => $comment->comment_author_url,
				'comment_content'      => $comment->comment_content,
				'comment_type'         => $comment->comment_type,
				'comment_parent'       => 0, // Will be updated later.
				'user_id'              => $comment->user_id,
				'comment_date'         => $comment->comment_date,
				'comment_approved'     => $comment->comment_approved,
				'comment_agent'        => $comment->comment_agent,
				'comment_author_IP'    => $comment->comment_author_IP,
			);

			$new_comment_id = wp_insert_comment( $new_comment_data );

			if ( $new_comment_id ) {
				$comment_map[ $comment->comment_ID ] = $new_comment_id;

				// Copy comment meta.
				$comment_meta = get_comment_meta( $comment->comment_ID );
				foreach ( $comment_meta as $meta_key => $meta_values ) {
					foreach ( $meta_values as $meta_value ) {
						add_comment_meta( $new_comment_id, $meta_key, maybe_unserialize( $meta_value ) );
					}
				}
			}
		}

		// Update comment parents.
		foreach ( $comment_map as $old_id => $new_id ) {
			$comment = get_comment( $old_id );
			if ( $comment->comment_parent && isset( $comment_map[ $comment->comment_parent ] ) ) {
				wp_update_comment(
					array(
						'comment_ID'     => $new_id,
						'comment_parent' => $comment_map[ $comment->comment_parent ],
					)
				);
			}
		}
	}
}
