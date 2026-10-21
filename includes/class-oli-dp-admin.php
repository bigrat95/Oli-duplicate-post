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
		unset( $hook );

		wp_enqueue_style(
			'oli-dp-admin',
			OLI_DP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			OLI_DP_VERSION
		);
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
