<?php
/**
 * Permissions and capability checks.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Permissions
 */
class Oli_DP_Permissions {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		// Hook into WordPress.
	}

	/**
	 * Check if current user can duplicate a post.
	 *
	 * @param int $post_id The post ID.
	 * @return bool True if user can duplicate, false otherwise.
	 */
	public static function can_duplicate( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		// Check if user has permission to read the post.
		if ( ! current_user_can( 'read_post', $post_id ) ) {
			return false;
		}

		// Check if user has permission to edit posts of this type.
		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! $post_type_object ) {
			return false;
		}

		if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
			return false;
		}

		// Check if post type is enabled in settings.
		$settings           = Oli_DP_Settings::get_settings();
		$enabled_post_types = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();

		if ( ! in_array( $post->post_type, $enabled_post_types, true ) ) {
			return false;
		}

		// Check if user role is allowed.
		$allowed_roles = isset( $settings['allowed_roles'] ) ? $settings['allowed_roles'] : array();
		if ( ! empty( $allowed_roles ) ) {
			$user             = wp_get_current_user();
			$user_roles       = (array) $user->roles;
			$has_allowed_role = false;

			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $allowed_roles, true ) ) {
					$has_allowed_role = true;
					break;
				}
			}

			if ( ! $has_allowed_role ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if current user can access settings page.
	 *
	 * @return bool True if user can access settings, false otherwise.
	 */
	public static function can_manage_settings() {
		return current_user_can( 'manage_options' );
	}
}
