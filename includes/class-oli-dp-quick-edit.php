<?php
/**
 * Quick edit integration.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Quick_Edit
 */
class Oli_DP_Quick_Edit {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'quick_edit_custom_box', array( __CLASS__, 'render_quick_edit_custom_box' ), 10, 2 );
	}

	/**
	 * Enqueue Quick Edit script on list tables.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_scripts( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$settings = Oli_DP_Settings::get_settings();
		if ( empty( $settings['show_row_actions'] ) ) {
			return;
		}

		wp_enqueue_style(
			'oli-dp-admin',
			OLI_DP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			OLI_DP_VERSION
		);

		wp_enqueue_script(
			'oli-dp-quick-edit',
			OLI_DP_PLUGIN_URL . 'assets/js/quick-edit.js',
			array( 'jquery' ),
			OLI_DP_VERSION,
			true
		);

		wp_localize_script(
			'oli-dp-quick-edit',
			'oliDpQuickEdit',
			array(
				'copyToNewDraft' => __( 'Copy to New Draft', 'oli-duplicate-post' ),
			)
		);

		wp_set_script_translations( 'oli-dp-quick-edit', 'oli-duplicate-post', OLI_DP_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Render custom box in quick edit (not used but kept for extensibility).
	 *
	 * @param string $column_name Column name.
	 * @param string $post_type   Post type.
	 */
	public static function render_quick_edit_custom_box( $column_name, $post_type ) {
		unset( $column_name, $post_type );
	}
}
