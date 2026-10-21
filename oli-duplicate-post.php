<?php
/**
 * Plugin Name: Oli Duplicate Post
 * Plugin URI: https://olivierbigras.com/oli-duplicate-post/
 * Description: Clone posts, pages, custom post types with Clone, New Draft, and Rewrite & Republish features.
 * Version: 0.2.0
 * Author: Olivier Bigras
 * Author URI: https://olivierbigras.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: oli-duplicate-post
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Tested up to: 7.1
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'OLI_DP_VERSION', '0.2.0' );
define( 'OLI_DP_PLUGIN_FILE', __FILE__ );
define( 'OLI_DP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OLI_DP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OLI_DP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load text domain for translations.
 */
function oli_dp_load_textdomain() {
	load_plugin_textdomain( 'oli-duplicate-post', false, dirname( OLI_DP_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'plugins_loaded', 'oli_dp_load_textdomain' );

/**
 * Include required files.
 */
require_once OLI_DP_PLUGIN_DIR . 'includes/class-oli-dp-permissions.php';
require_once OLI_DP_PLUGIN_DIR . 'includes/class-oli-dp-settings.php';
require_once OLI_DP_PLUGIN_DIR . 'includes/class-oli-dp-duplicator.php';
require_once OLI_DP_PLUGIN_DIR . 'includes/class-oli-dp-rewrite-republish.php';
require_once OLI_DP_PLUGIN_DIR . 'includes/class-oli-dp-comparison.php';
require_once OLI_DP_PLUGIN_DIR . 'includes/class-oli-dp-hooks.php';
require_once OLI_DP_PLUGIN_DIR . 'includes/class-oli-dp-admin.php';
require_once OLI_DP_PLUGIN_DIR . 'includes/class-oli-dp-quick-edit.php';
require_once OLI_DP_PLUGIN_DIR . 'admin/class-oli-dp-post-list.php';
require_once OLI_DP_PLUGIN_DIR . 'admin/class-oli-dp-bulk-actions.php';
require_once OLI_DP_PLUGIN_DIR . 'admin/class-oli-dp-admin-bar.php';
require_once OLI_DP_PLUGIN_DIR . 'admin/class-oli-dp-meta-box.php';

/**
 * Initialize the plugin.
 */
function oli_dp_init() {
	// Initialize classes.
	Oli_DP_Permissions::init();
	Oli_DP_Settings::init();
	Oli_DP_Hooks::init();
	Oli_DP_Admin::init();
	Oli_DP_Quick_Edit::init();
	Oli_DP_Post_List::init();
	Oli_DP_Bulk_Actions::init();
	Oli_DP_Admin_Bar::init();
	Oli_DP_Meta_Box::init();
	Oli_DP_Rewrite_Republish::init();
	Oli_DP_Comparison::init();
}
add_action( 'init', 'oli_dp_init' );

/**
 * Template tag to duplicate a post programmatically.
 *
 * @param int    $post_id The post ID to duplicate.
 * @param string $mode    The duplication mode: 'clone', 'new_draft', or 'rewrite_republish'.
 * @param array  $args    Optional arguments to override default behavior.
 * @return int|WP_Error The new post ID on success, WP_Error on failure.
 */
function oli_dp_duplicate_post( $post_id, $mode = 'clone', $args = array() ) {
	return Oli_DP_Duplicator::duplicate( $post_id, $mode, $args );
}

/**
 * Activation hook.
 */
function oli_dp_activate() {
	// Set default options.
	if ( false === get_option( 'oli_dp_settings' ) ) {
		$defaults = Oli_DP_Settings::get_default_settings();
		update_option( 'oli_dp_settings', $defaults );
	}
}
register_activation_hook( __FILE__, 'oli_dp_activate' );

/**
 * Deactivation hook.
 */
function oli_dp_deactivate() {
	// Clean up temporary data if any.
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_oli_dp_original_post' ) );
}
register_deactivation_hook( __FILE__, 'oli_dp_deactivate' );
