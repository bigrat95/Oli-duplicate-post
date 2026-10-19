<?php
/**
 * Settings management.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Settings
 */
class Oli_DP_Settings {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Get default settings.
	 *
	 * @return array Default settings.
	 */
	public static function get_default_settings() {
		return array(
			// What to copy.
			'copy_title'       => true,
			'copy_date'        => false,
			'copy_status'      => false,
			'copy_slug'        => false,
			'copy_excerpt'     => true,
			'copy_content'     => true,
			'copy_template'    => true,
			'copy_format'      => true,
			'copy_author'      => false,
			'copy_password'    => false,
			'copy_menu_order'  => true,
			'copy_attachments' => false,
			'copy_children'    => false,
			'copy_comments'    => false,

			// Title settings.
			'title_prefix'     => '',
			'title_suffix'     => __( ' (Copy)', 'oli-duplicate-post' ),

			// Meta and taxonomy settings.
			'excluded_meta'    => '_edit_lock,_edit_last,_wp_old_slug,_wp_old_date',
			'excluded_taxonomies' => '',

			// Post types and roles.
			'enabled_post_types' => array( 'post', 'page' ),
			'allowed_roles'    => array( 'administrator', 'editor' ),

			// UI locations.
			'show_row_actions' => true,
			'show_admin_bar'   => true,
			'show_edit_screen' => true,
			'show_bulk_actions' => true,
		);
	}

	/**
	 * Add settings page to admin menu.
	 */
	public static function add_settings_page() {
		add_options_page(
			__( 'Oli Duplicate Post Settings', 'oli-duplicate-post' ),
			__( 'Duplicate Post', 'oli-duplicate-post' ),
			'manage_options',
			'oli-duplicate-post-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting(
			'oli_dp_settings_group',
			'oli_dp_settings',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);

		// What to copy section.
		add_settings_section(
			'oli_dp_what_to_copy',
			__( 'What to Copy', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_section_what_to_copy' ),
			'oli-duplicate-post-settings'
		);

		// Title settings section.
		add_settings_section(
			'oli_dp_title_settings',
			__( 'Title Settings', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_section_title_settings' ),
			'oli-duplicate-post-settings'
		);

		// Advanced section.
		add_settings_section(
			'oli_dp_advanced',
			__( 'Advanced Settings', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_section_advanced' ),
			'oli-duplicate-post-settings'
		);

		// Post types and permissions section.
		add_settings_section(
			'oli_dp_permissions',
			__( 'Post Types & Permissions', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_section_permissions' ),
			'oli-duplicate-post-settings'
		);

		// UI locations section.
		add_settings_section(
			'oli_dp_ui_locations',
			__( 'UI Locations', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_section_ui_locations' ),
			'oli-duplicate-post-settings'
		);

		// Register all fields.
		self::register_all_fields();
	}

	/**
	 * Register all settings fields.
	 */
	private static function register_all_fields() {
		// What to copy fields.
		$copy_fields = array(
			'copy_title'       => __( 'Title', 'oli-duplicate-post' ),
			'copy_date'        => __( 'Date', 'oli-duplicate-post' ),
			'copy_status'      => __( 'Status', 'oli-duplicate-post' ),
			'copy_slug'        => __( 'Slug', 'oli-duplicate-post' ),
			'copy_excerpt'     => __( 'Excerpt', 'oli-duplicate-post' ),
			'copy_content'     => __( 'Content', 'oli-duplicate-post' ),
			'copy_template'    => __( 'Template', 'oli-duplicate-post' ),
			'copy_format'      => __( 'Format', 'oli-duplicate-post' ),
			'copy_author'      => __( 'Author', 'oli-duplicate-post' ),
			'copy_password'    => __( 'Password', 'oli-duplicate-post' ),
			'copy_menu_order'  => __( 'Menu Order', 'oli-duplicate-post' ),
			'copy_attachments' => __( 'Attachments', 'oli-duplicate-post' ),
			'copy_children'    => __( 'Children', 'oli-duplicate-post' ),
			'copy_comments'    => __( 'Comments', 'oli-duplicate-post' ),
		);

		foreach ( $copy_fields as $field => $label ) {
			add_settings_field(
				$field,
				$label,
				array( __CLASS__, 'render_checkbox_field' ),
				'oli-duplicate-post-settings',
				'oli_dp_what_to_copy',
				array( 'field' => $field )
			);
		}

		// Title settings fields.
		add_settings_field(
			'title_prefix',
			__( 'Title Prefix', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_text_field' ),
			'oli-duplicate-post-settings',
			'oli_dp_title_settings',
			array( 'field' => 'title_prefix' )
		);

		add_settings_field(
			'title_suffix',
			__( 'Title Suffix', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_text_field' ),
			'oli-duplicate-post-settings',
			'oli_dp_title_settings',
			array( 'field' => 'title_suffix' )
		);

		// Advanced fields.
		add_settings_field(
			'excluded_meta',
			__( 'Excluded Meta Fields', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_textarea_field' ),
			'oli-duplicate-post-settings',
			'oli_dp_advanced',
			array(
				'field' => 'excluded_meta',
				'description' => __( 'Comma-separated list of meta keys to exclude. Supports wildcards (*).', 'oli-duplicate-post' ),
			)
		);

		add_settings_field(
			'excluded_taxonomies',
			__( 'Excluded Taxonomies', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_textarea_field' ),
			'oli-duplicate-post-settings',
			'oli_dp_advanced',
			array(
				'field' => 'excluded_taxonomies',
				'description' => __( 'Comma-separated list of taxonomies to exclude.', 'oli-duplicate-post' ),
			)
		);

		// Post types field.
		add_settings_field(
			'enabled_post_types',
			__( 'Enabled Post Types', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_post_types_field' ),
			'oli-duplicate-post-settings',
			'oli_dp_permissions'
		);

		// Allowed roles field.
		add_settings_field(
			'allowed_roles',
			__( 'Allowed Roles', 'oli-duplicate-post' ),
			array( __CLASS__, 'render_roles_field' ),
			'oli-duplicate-post-settings',
			'oli_dp_permissions'
		);

		// UI locations fields.
		$ui_fields = array(
			'show_row_actions'   => __( 'Show in Row Actions', 'oli-duplicate-post' ),
			'show_admin_bar'     => __( 'Show in Admin Bar', 'oli-duplicate-post' ),
			'show_edit_screen'   => __( 'Show in Edit Screen', 'oli-duplicate-post' ),
			'show_bulk_actions'  => __( 'Show Bulk Actions', 'oli-duplicate-post' ),
		);

		foreach ( $ui_fields as $field => $label ) {
			add_settings_field(
				$field,
				$label,
				array( __CLASS__, 'render_checkbox_field' ),
				'oli-duplicate-post-settings',
				'oli_dp_ui_locations',
				array( 'field' => $field )
			);
		}
	}

	/**
	 * Render section descriptions.
	 */
	public static function render_section_what_to_copy() {
		echo '<p>' . esc_html__( 'Choose which elements to copy when duplicating a post.', 'oli-duplicate-post' ) . '</p>';
	}

	/**
	 * Render title settings section.
	 */
	public static function render_section_title_settings() {
		echo '<p>' . esc_html__( 'Configure how the title should be modified for duplicated posts.', 'oli-duplicate-post' ) . '</p>';
	}

	/**
	 * Render advanced section.
	 */
	public static function render_section_advanced() {
		echo '<p>' . esc_html__( 'Advanced settings for meta fields and taxonomies.', 'oli-duplicate-post' ) . '</p>';
	}

	/**
	 * Render permissions section.
	 */
	public static function render_section_permissions() {
		echo '<p>' . esc_html__( 'Configure which post types can be duplicated and which user roles have access.', 'oli-duplicate-post' ) . '</p>';
	}

	/**
	 * Render UI locations section.
	 */
	public static function render_section_ui_locations() {
		echo '<p>' . esc_html__( 'Choose where duplicate links should appear in the WordPress admin.', 'oli-duplicate-post' ) . '</p>';
	}

	/**
	 * Render checkbox field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_checkbox_field( $args ) {
		$settings = get_option( 'oli_dp_settings', self::get_default_settings() );
		$field = $args['field'];
		$value = isset( $settings[ $field ] ) ? $settings[ $field ] : false;
		?>
		<label>
			<input type="checkbox" name="oli_dp_settings[<?php echo esc_attr( $field ); ?>]" value="1" <?php checked( $value, true ); ?> />
		</label>
		<?php
	}

	/**
	 * Render text field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_text_field( $args ) {
		$settings = get_option( 'oli_dp_settings', self::get_default_settings() );
		$field = $args['field'];
		$value = isset( $settings[ $field ] ) ? $settings[ $field ] : '';
		?>
		<input type="text" name="oli_dp_settings[<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<?php
		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render textarea field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_textarea_field( $args ) {
		$settings = get_option( 'oli_dp_settings', self::get_default_settings() );
		$field = $args['field'];
		$value = isset( $settings[ $field ] ) ? $settings[ $field ] : '';
		?>
		<textarea name="oli_dp_settings[<?php echo esc_attr( $field ); ?>]" rows="3" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<?php
		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render post types field.
	 */
	public static function render_post_types_field() {
		$settings = get_option( 'oli_dp_settings', self::get_default_settings() );
		$enabled_post_types = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();
		
		$post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
		
		foreach ( $post_types as $post_type ) {
			$checked = in_array( $post_type->name, $enabled_post_types, true );
			?>
			<label style="display: block; margin-bottom: 5px;">
				<input type="checkbox" name="oli_dp_settings[enabled_post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( $checked ); ?> />
				<?php echo esc_html( $post_type->label ); ?>
			</label>
			<?php
		}
	}

	/**
	 * Render roles field.
	 */
	public static function render_roles_field() {
		$settings = get_option( 'oli_dp_settings', self::get_default_settings() );
		$allowed_roles = isset( $settings['allowed_roles'] ) ? $settings['allowed_roles'] : array();
		
		$roles = wp_roles()->roles;
		
		foreach ( $roles as $role_key => $role ) {
			$checked = in_array( $role_key, $allowed_roles, true );
			?>
			<label style="display: block; margin-bottom: 5px;">
				<input type="checkbox" name="oli_dp_settings[allowed_roles][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( $checked ); ?> />
				<?php echo esc_html( $role['name'] ); ?>
			</label>
			<?php
		}
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings_page() {
		if ( ! Oli_DP_Permissions::can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'oli-duplicate-post' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'oli_dp_settings_group' );
				do_settings_sections( 'oli-duplicate-post-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Input settings.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $input ) {
		$sanitized = array();

		// Checkbox fields.
		$checkbox_fields = array(
			'copy_title', 'copy_date', 'copy_status', 'copy_slug', 'copy_excerpt',
			'copy_content', 'copy_template', 'copy_format', 'copy_author',
			'copy_password', 'copy_menu_order', 'copy_attachments', 'copy_children',
			'copy_comments', 'show_row_actions', 'show_admin_bar', 'show_edit_screen',
			'show_bulk_actions',
		);

		foreach ( $checkbox_fields as $field ) {
			$sanitized[ $field ] = ! empty( $input[ $field ] );
		}

		// Text fields.
		$sanitized['title_prefix'] = sanitize_text_field( $input['title_prefix'] ?? '' );
		$sanitized['title_suffix'] = sanitize_text_field( $input['title_suffix'] ?? '' );

		// Textarea fields.
		$sanitized['excluded_meta'] = sanitize_textarea_field( $input['excluded_meta'] ?? '' );
		$sanitized['excluded_taxonomies'] = sanitize_textarea_field( $input['excluded_taxonomies'] ?? '' );

		// Array fields.
		$sanitized['enabled_post_types'] = array();
		if ( ! empty( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			foreach ( $input['enabled_post_types'] as $post_type ) {
				$sanitized['enabled_post_types'][] = sanitize_key( $post_type );
			}
		}

		$sanitized['allowed_roles'] = array();
		if ( ! empty( $input['allowed_roles'] ) && is_array( $input['allowed_roles'] ) ) {
			foreach ( $input['allowed_roles'] as $role ) {
				$sanitized['allowed_roles'][] = sanitize_key( $role );
			}
		}

		return $sanitized;
	}
}
