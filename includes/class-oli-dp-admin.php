<?php
/**
 * Admin interface initialization.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Admin
 */
class Oli_DP_Admin {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . OLI_DP_PLUGIN_BASENAME, array( __CLASS__, 'add_plugin_action_links' ) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_scripts( $hook ) {
		// Add inline CSS for admin notices and UI.
		$custom_css = '
			.oli-dp-comparison-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
			.oli-dp-comparison-table th,
			.oli-dp-comparison-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
			.oli-dp-comparison-table th { background-color: #f5f5f5; font-weight: 600; }
			.oli-dp-comparison-diff { background-color: #fff8dc; }
		';
		
		wp_add_inline_style( 'wp-admin', $custom_css );
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public static function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=oli-duplicate-post-settings' ),
			__( 'Settings', 'oli-duplicate-post' )
		);
		
		array_unshift( $links, $settings_link );
		
		return $links;
	}
}
