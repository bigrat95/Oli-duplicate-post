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
	 * Meta keys that are always excluded from copies.
	 *
	 * @var string[]
	 */
	private static $always_excluded_meta = array(
		'_edit_lock',
		'_edit_last',
		'_wp_old_slug',
		'_wp_old_date',
	);

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_migrate' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_settings_assets' ) );
	}

	/**
	 * Meta keys that cannot be copied.
	 *
	 * @return string[]
	 */
	public static function get_always_excluded_meta() {
		return self::$always_excluded_meta;
	}

	/**
	 * Whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Get default settings.
	 *
	 * @return array Default settings.
	 */
	public static function get_default_settings() {
		$post_types = array( 'post', 'page' );
		$roles      = array( 'administrator', 'editor' );

		if ( self::is_woocommerce_active() ) {
			$post_types[] = 'product';
			$roles[]      = 'shop_manager';
		}

		return array(
			// What to copy.
			'copy_title'          => true,
			'copy_date'           => false,
			'copy_status'         => false,
			'copy_slug'           => false,
			'copy_excerpt'        => true,
			'copy_content'        => true,
			'copy_template'       => true,
			'copy_format'         => true,
			'copy_author'         => false,
			'copy_password'       => false,
			'copy_menu_order'     => true,
			'copy_attachments'    => false,
			'copy_children'       => false,
			'copy_comments'       => false,
			'copy_thumbnail'      => true,

			// Title settings. An empty suffix means a translated "(Copy)" at runtime.
			'title_prefix'        => '',
			'title_suffix'        => '',

			// Meta and taxonomy settings.
			'excluded_meta'       => '',
			'excluded_taxonomies' => array(),

			// Post types and roles.
			'enabled_post_types'  => $post_types,
			'allowed_roles'       => $roles,

			// UI locations.
			'show_row_actions'    => true,
			'show_admin_bar'      => true,
			'show_edit_screen'    => true,
			'show_bulk_actions'   => true,

			// Features.
			'enable_rewrite'      => true,
		);
	}

	/**
	 * Get saved settings merged with defaults and a soft migration.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( 'oli_dp_settings', false );

		if ( false === $saved || ! is_array( $saved ) ) {
			return self::get_default_settings();
		}

		return self::migrate_settings( $saved );
	}

	/**
	 * Persist a soft migration when stored options use a previous shape.
	 */
	public static function maybe_migrate() {
		$saved = get_option( 'oli_dp_settings', false );

		if ( false === $saved || ! is_array( $saved ) ) {
			return;
		}

		$migrated = self::migrate_settings( $saved );

		if ( $migrated !== $saved ) {
			update_option( 'oli_dp_settings', $migrated );
		}
	}

	/**
	 * Soft-migrate stored settings without renaming existing keys.
	 *
	 * @param array $settings Stored settings.
	 * @return array
	 */
	public static function migrate_settings( $settings ) {
		$defaults = self::get_default_settings();
		$settings = wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );

		$settings['copy_title']     = true;
		$settings['enable_rewrite'] = ! empty( $settings['enable_rewrite'] );
		$settings['copy_thumbnail'] = array_key_exists( 'copy_thumbnail', $settings )
			? ! empty( $settings['copy_thumbnail'] )
			: true;

		$old_suffixes = array(
			' (Copy)',
			__( ' (Copy)', 'oli-duplicate-post' ),
		);
		if ( in_array( $settings['title_suffix'], $old_suffixes, true ) ) {
			$settings['title_suffix'] = '';
		}

		$settings['excluded_meta']       = self::normalize_excluded_meta( $settings['excluded_meta'] );
		$settings['excluded_taxonomies'] = self::normalize_excluded_taxonomies( $settings['excluded_taxonomies'] );

		if ( ! is_array( $settings['enabled_post_types'] ) ) {
			$settings['enabled_post_types'] = $defaults['enabled_post_types'];
		}

		if ( ! is_array( $settings['allowed_roles'] ) ) {
			$settings['allowed_roles'] = $defaults['allowed_roles'];
		}

		return $settings;
	}

	/**
	 * Normalize excluded meta to one key per line, without reserved keys.
	 *
	 * @param mixed $value Stored value.
	 * @return string
	 */
	public static function normalize_excluded_meta( $value ) {
		if ( is_array( $value ) ) {
			$keys = $value;
		} else {
			$raw  = is_string( $value ) ? $value : '';
			$keys = preg_split( '/[\r\n,]+/', $raw );
		}

		$keys = array_filter( array_map( 'trim', (array) $keys ) );
		$keys = array_values( array_diff( $keys, self::get_always_excluded_meta() ) );

		return implode( "\n", $keys );
	}

	/**
	 * Normalize excluded taxonomies to an array of slugs.
	 *
	 * @param mixed $value Stored value.
	 * @return string[]
	 */
	public static function normalize_excluded_taxonomies( $value ) {
		if ( is_array( $value ) ) {
			$taxonomies = $value;
		} else {
			$raw        = is_string( $value ) ? $value : '';
			$taxonomies = preg_split( '/[\r\n,]+/', $raw );
		}

		$taxonomies = array_filter( array_map( 'sanitize_key', (array) $taxonomies ) );

		return array_values( array_unique( $taxonomies ) );
	}

	/**
	 * Parse excluded meta patterns for duplication, including reserved keys.
	 *
	 * @param array|null $settings Settings array.
	 * @return string[]
	 */
	public static function get_excluded_meta_keys( $settings = null ) {
		if ( null === $settings ) {
			$settings = self::get_settings();
		}

		$user_keys = preg_split( '/[\r\n,]+/', isset( $settings['excluded_meta'] ) ? (string) $settings['excluded_meta'] : '' );
		$user_keys = array_filter( array_map( 'trim', (array) $user_keys ) );

		return array_values( array_unique( array_merge( self::get_always_excluded_meta(), $user_keys ) ) );
	}

	/**
	 * Parse excluded taxonomy slugs.
	 *
	 * @param array|null $settings Settings array.
	 * @return string[]
	 */
	public static function get_excluded_taxonomy_slugs( $settings = null ) {
		if ( null === $settings ) {
			$settings = self::get_settings();
		}

		return self::normalize_excluded_taxonomies( isset( $settings['excluded_taxonomies'] ) ? $settings['excluded_taxonomies'] : array() );
	}

	/**
	 * Build the duplicated title. An empty suffix uses a translated "(Copy)"
	 * with the leading space added here, not in the translated string.
	 *
	 * @param string     $title    Original title.
	 * @param array|null $settings Settings array.
	 * @return string
	 */
	public static function get_copy_title( $title, $settings = null ) {
		if ( null === $settings ) {
			$settings = self::get_settings();
		}

		$prefix = isset( $settings['title_prefix'] ) ? (string) $settings['title_prefix'] : '';
		$suffix = isset( $settings['title_suffix'] ) ? (string) $settings['title_suffix'] : '';

		if ( '' === $suffix ) {
			$suffix = ' ' . __( '(Copy)', 'oli-duplicate-post' );
		}

		return $prefix . $title . $suffix;
	}

	/**
	 * Whether Rewrite & Republish is enabled.
	 *
	 * @param array|null $settings Settings array.
	 * @return bool
	 */
	public static function is_rewrite_enabled( $settings = null ) {
		if ( null === $settings ) {
			$settings = self::get_settings();
		}

		return ! empty( $settings['enable_rewrite'] );
	}

	/**
	 * Public post types with an admin UI.
	 *
	 * @return WP_Post_Type[]
	 */
	public static function get_duplicable_post_types() {
		$post_types = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects'
		);

		return $post_types;
	}

	/**
	 * Roles that can edit posts.
	 *
	 * @return array<string, string> Role slug => label.
	 */
	public static function get_editable_roles() {
		$choices = array();

		foreach ( wp_roles()->roles as $role_key => $role ) {
			$role_object = get_role( $role_key );
			if ( ! $role_object || ! $role_object->has_cap( 'edit_posts' ) ) {
				continue;
			}

			$choices[ $role_key ] = translate_user_role( $role['name'] );
		}

		return $choices;
	}

	/**
	 * Add settings page to admin menu.
	 */
	public static function add_settings_page() {
		add_options_page(
			__( 'Duplicate Post settings', 'oli-duplicate-post' ),
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
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::get_default_settings(),
			)
		);
	}

	/**
	 * Enqueue settings-page script.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_settings_assets( $hook ) {
		if ( 'settings_page_oli-duplicate-post-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'oli-dp-admin',
			OLI_DP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			OLI_DP_VERSION
		);

		wp_enqueue_script(
			'oli-dp-settings',
			OLI_DP_PLUGIN_URL . 'assets/js/settings.js',
			array(),
			OLI_DP_VERSION,
			true
		);

		wp_localize_script(
			'oli-dp-settings',
			'oliDpSettings',
			array(
				'sampleTitle'   => __( 'Sample title', 'oli-duplicate-post' ),
				'defaultSuffix' => __( '(Copy)', 'oli-duplicate-post' ),
			)
		);

		wp_set_script_translations( 'oli-dp-settings', 'oli-duplicate-post', OLI_DP_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings_page() {
		if ( ! Oli_DP_Permissions::can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'oli-duplicate-post' ) );
		}

		$settings = self::get_settings();
		?>
		<div class="wrap oli-dp-settings-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors( 'oli_dp_settings' ); ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'oli_dp_settings_group' ); ?>

				<h2 class="title"><?php esc_html_e( 'Where to duplicate', 'oli-duplicate-post' ); ?></h2>
				<?php self::render_where_to_duplicate( $settings ); ?>

				<h2 class="title"><?php esc_html_e( 'The copy', 'oli-duplicate-post' ); ?></h2>
				<?php self::render_the_copy( $settings ); ?>

				<details class="oli-dp-advanced">
					<summary><?php esc_html_e( 'Advanced', 'oli-duplicate-post' ); ?></summary>
					<h2 class="title"><?php esc_html_e( 'Copied elements', 'oli-duplicate-post' ); ?></h2>
					<p class="description"><?php esc_html_e( 'The title is always copied.', 'oli-duplicate-post' ); ?></p>
					<?php self::render_copied_elements( $settings ); ?>
				</details>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render “Where to duplicate”.
	 *
	 * @param array $settings Settings.
	 */
	private static function render_where_to_duplicate( $settings ) {
		$enabled_post_types = isset( $settings['enabled_post_types'] ) ? (array) $settings['enabled_post_types'] : array();
		$allowed_roles      = isset( $settings['allowed_roles'] ) ? (array) $settings['allowed_roles'] : array();
		?>
		<fieldset class="oli-dp-fieldset">
			<legend><?php esc_html_e( 'Content types', 'oli-duplicate-post' ); ?></legend>
			<?php foreach ( self::get_duplicable_post_types() as $post_type ) : ?>
				<label class="oli-dp-settings-option">
					<input type="checkbox" name="oli_dp_settings[enabled_post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $enabled_post_types, true ) ); ?> />
					<?php echo esc_html( $post_type->label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<fieldset class="oli-dp-fieldset">
			<legend><?php esc_html_e( 'Roles', 'oli-duplicate-post' ); ?></legend>
			<?php foreach ( self::get_editable_roles() as $role_key => $role_label ) : ?>
				<label class="oli-dp-settings-option">
					<input type="checkbox" name="oli_dp_settings[allowed_roles][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $allowed_roles, true ) ); ?> />
					<?php echo esc_html( $role_label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<fieldset class="oli-dp-fieldset">
			<legend><?php esc_html_e( 'Rewrite & Republish', 'oli-duplicate-post' ); ?></legend>
			<label class="oli-dp-settings-option">
				<input type="checkbox" name="oli_dp_settings[enable_rewrite]" value="1" <?php checked( ! empty( $settings['enable_rewrite'] ) ); ?> />
				<?php esc_html_e( 'Rewrite & Republish', 'oli-duplicate-post' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Edit a copy of published content, then replace the original in one click.', 'oli-duplicate-post' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Render “The copy”.
	 *
	 * @param array $settings Settings.
	 */
	private static function render_the_copy( $settings ) {
		$prefix      = isset( $settings['title_prefix'] ) ? $settings['title_prefix'] : '';
		$suffix      = isset( $settings['title_suffix'] ) ? $settings['title_suffix'] : '';
		$copy_status = ! empty( $settings['copy_status'] );
		$preview     = self::get_copy_title( __( 'Sample title', 'oli-duplicate-post' ), $settings );
		?>
		<fieldset class="oli-dp-fieldset">
			<legend><?php esc_html_e( 'Title', 'oli-duplicate-post' ); ?></legend>
			<p>
				<label for="oli_dp_title_prefix"><?php esc_html_e( 'Title prefix', 'oli-duplicate-post' ); ?></label><br />
				<input type="text" class="regular-text" id="oli_dp_title_prefix" name="oli_dp_settings[title_prefix]" value="<?php echo esc_attr( $prefix ); ?>" />
			</p>
			<p>
				<label for="oli_dp_title_suffix"><?php esc_html_e( 'Title suffix', 'oli-duplicate-post' ); ?></label><br />
				<input type="text" class="regular-text" id="oli_dp_title_suffix" name="oli_dp_settings[title_suffix]" value="<?php echo esc_attr( $suffix ); ?>" />
			</p>
			<p id="oli-dp-title-preview" class="oli-dp-title-preview" aria-live="polite">
				<?php esc_html_e( 'Preview:', 'oli-duplicate-post' ); ?>
				<strong id="oli-dp-title-preview-text"><?php echo esc_html( $preview ); ?></strong>
			</p>
		</fieldset>

		<fieldset class="oli-dp-fieldset">
			<legend><?php esc_html_e( 'Status of the copy', 'oli-duplicate-post' ); ?></legend>
			<label class="oli-dp-settings-option">
				<input type="radio" name="oli_dp_settings[copy_status]" value="0" <?php checked( $copy_status, false ); ?> />
				<?php esc_html_e( 'Draft (recommended)', 'oli-duplicate-post' ); ?>
			</label>
			<label class="oli-dp-settings-option">
				<input type="radio" name="oli_dp_settings[copy_status]" value="1" <?php checked( $copy_status, true ); ?> aria-describedby="oli-dp-status-warning" />
				<?php esc_html_e( 'Same status as the original', 'oli-duplicate-post' ); ?>
			</label>
			<p id="oli-dp-status-warning" class="oli-dp-status-warning notice notice-warning inline" <?php echo $copy_status ? '' : 'hidden="hidden"'; ?>>
				<?php esc_html_e( 'A copy of published content will be published immediately', 'oli-duplicate-post' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Render Advanced → Copied elements.
	 *
	 * @param array $settings Settings.
	 */
	private static function render_copied_elements( $settings ) {
		$standard = array(
			'copy_content'    => __( 'Content', 'oli-duplicate-post' ),
			'copy_excerpt'    => __( 'Excerpt', 'oli-duplicate-post' ),
			'copy_template'   => __( 'Template', 'oli-duplicate-post' ),
			'copy_format'     => __( 'Format', 'oli-duplicate-post' ),
			'copy_menu_order' => __( 'Menu order', 'oli-duplicate-post' ),
			'copy_thumbnail'  => __( 'Featured image', 'oli-duplicate-post' ),
		);

		$optional = array(
			'copy_date'        => array(
				'label' => __( 'Date', 'oli-duplicate-post' ),
				'help'  => '',
			),
			'copy_slug'        => array(
				'label' => __( 'Slug', 'oli-duplicate-post' ),
				'help'  => __( 'WordPress will add -2', 'oli-duplicate-post' ),
			),
			'copy_author'      => array(
				'label' => __( 'Author', 'oli-duplicate-post' ),
				'help'  => __( 'Otherwise, it will be you', 'oli-duplicate-post' ),
			),
			'copy_password'    => array(
				'label' => __( 'Password', 'oli-duplicate-post' ),
				'help'  => '',
			),
			'copy_attachments' => array(
				'label' => __( 'Attachments', 'oli-duplicate-post' ),
				'help'  => __( 'Creates new files in the media library', 'oli-duplicate-post' ),
			),
			'copy_children'    => array(
				'label' => __( 'Child pages', 'oli-duplicate-post' ),
				'help'  => __( 'Also copies child pages', 'oli-duplicate-post' ),
			),
			'copy_comments'    => array(
				'label' => __( 'Comments', 'oli-duplicate-post' ),
				'help'  => '',
			),
		);

		$ui_locations = array(
			'show_row_actions'  => __( 'List', 'oli-duplicate-post' ),
			'show_admin_bar'    => __( 'Admin bar', 'oli-duplicate-post' ),
			'show_edit_screen'  => __( 'Edit screen', 'oli-duplicate-post' ),
			'show_bulk_actions' => __( 'Bulk actions', 'oli-duplicate-post' ),
		);

		$excluded_taxonomies = self::get_excluded_taxonomy_slugs( $settings );
		$taxonomies          = get_taxonomies( array(), 'objects' );
		?>
		<fieldset class="oli-dp-fieldset">
			<legend><?php esc_html_e( 'Standard elements', 'oli-duplicate-post' ); ?></legend>
			<?php foreach ( $standard as $field => $label ) : ?>
				<label class="oli-dp-settings-option">
					<input type="checkbox" name="oli_dp_settings[<?php echo esc_attr( $field ); ?>]" value="1" <?php checked( ! empty( $settings[ $field ] ) ); ?> />
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<fieldset class="oli-dp-fieldset">
			<legend><?php esc_html_e( 'Optional elements', 'oli-duplicate-post' ); ?></legend>
			<?php foreach ( $optional as $field => $item ) : ?>
				<label class="oli-dp-settings-option">
					<input type="checkbox" name="oli_dp_settings[<?php echo esc_attr( $field ); ?>]" value="1" <?php checked( ! empty( $settings[ $field ] ) ); ?> />
					<?php echo esc_html( $item['label'] ); ?>
					<?php if ( '' !== $item['help'] ) : ?>
						<span class="description oli-dp-option-help"><?php echo esc_html( $item['help'] ); ?></span>
					<?php endif; ?>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<fieldset class="oli-dp-fieldset">
			<legend><?php esc_html_e( 'Excluded taxonomies', 'oli-duplicate-post' ); ?></legend>
			<?php foreach ( $taxonomies as $taxonomy ) : ?>
				<label class="oli-dp-settings-option">
					<input type="checkbox" name="oli_dp_settings[excluded_taxonomies][]" value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php checked( in_array( $taxonomy->name, $excluded_taxonomies, true ) ); ?> />
					<?php echo esc_html( $taxonomy->label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<fieldset class="oli-dp-fieldset">
			<legend><?php esc_html_e( 'Excluded meta', 'oli-duplicate-post' ); ?></legend>
			<p class="description">
				<?php esc_html_e( 'One meta key per line. The * wildcard is accepted.', 'oli-duplicate-post' ); ?>
			</p>
			<p class="description oli-dp-always-excluded">
				<?php esc_html_e( 'Always excluded:', 'oli-duplicate-post' ); ?>
				<?php foreach ( self::get_always_excluded_meta() as $meta_key ) : ?>
					<code><?php echo esc_html( $meta_key ); ?></code>
				<?php endforeach; ?>
			</p>
			<label for="oli_dp_excluded_meta" class="screen-reader-text"><?php esc_html_e( 'Excluded meta keys', 'oli-duplicate-post' ); ?></label>
			<textarea id="oli_dp_excluded_meta" name="oli_dp_settings[excluded_meta]" rows="5" class="large-text code"><?php echo esc_textarea( isset( $settings['excluded_meta'] ) ? $settings['excluded_meta'] : '' ); ?></textarea>
		</fieldset>

		<fieldset class="oli-dp-fieldset">
			<legend><?php esc_html_e( 'Show copy links in', 'oli-duplicate-post' ); ?></legend>
			<?php foreach ( $ui_locations as $field => $label ) : ?>
				<label class="oli-dp-settings-option">
					<input type="checkbox" name="oli_dp_settings[<?php echo esc_attr( $field ); ?>]" value="1" <?php checked( ! empty( $settings[ $field ] ) ); ?> />
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Input settings.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $input ) {
		$input     = is_array( $input ) ? $input : array();
		$sanitized = self::get_default_settings();

		$checkbox_fields = array(
			'copy_date',
			'copy_slug',
			'copy_excerpt',
			'copy_content',
			'copy_template',
			'copy_format',
			'copy_author',
			'copy_password',
			'copy_menu_order',
			'copy_attachments',
			'copy_children',
			'copy_comments',
			'copy_thumbnail',
			'show_row_actions',
			'show_admin_bar',
			'show_edit_screen',
			'show_bulk_actions',
			'enable_rewrite',
		);

		foreach ( $checkbox_fields as $field ) {
			$sanitized[ $field ] = ! empty( $input[ $field ] );
		}

		$sanitized['copy_title']  = true;
		$sanitized['copy_status'] = isset( $input['copy_status'] ) && '1' === (string) $input['copy_status'];

		$sanitized['title_prefix'] = sanitize_text_field( $input['title_prefix'] ?? '' );
		$sanitized['title_suffix'] = sanitize_text_field( $input['title_suffix'] ?? '' );

		$sanitized['excluded_meta']       = self::normalize_excluded_meta( $input['excluded_meta'] ?? '' );
		$sanitized['excluded_taxonomies'] = array();
		if ( ! empty( $input['excluded_taxonomies'] ) && is_array( $input['excluded_taxonomies'] ) ) {
			foreach ( $input['excluded_taxonomies'] as $taxonomy ) {
				$sanitized['excluded_taxonomies'][] = sanitize_key( $taxonomy );
			}
			$sanitized['excluded_taxonomies'] = array_values( array_unique( $sanitized['excluded_taxonomies'] ) );
		}

		$sanitized['enabled_post_types'] = array();
		if ( ! empty( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			foreach ( $input['enabled_post_types'] as $post_type ) {
				$sanitized['enabled_post_types'][] = sanitize_key( $post_type );
			}
		}

		if ( empty( $sanitized['enabled_post_types'] ) ) {
			add_settings_error(
				'oli_dp_settings',
				'oli_dp_no_post_types',
				__( 'Select at least one content type.', 'oli-duplicate-post' ),
				'error'
			);

			$previous = get_option( 'oli_dp_settings', array() );
			if ( ! empty( $previous['enabled_post_types'] ) && is_array( $previous['enabled_post_types'] ) ) {
				$sanitized['enabled_post_types'] = $previous['enabled_post_types'];
			} else {
				$sanitized['enabled_post_types'] = self::get_default_settings()['enabled_post_types'];
			}
		}

		$sanitized['allowed_roles'] = array();
		if ( ! empty( $input['allowed_roles'] ) && is_array( $input['allowed_roles'] ) ) {
			$valid_roles = array_keys( self::get_editable_roles() );
			foreach ( $input['allowed_roles'] as $role ) {
				$role = sanitize_key( $role );
				if ( in_array( $role, $valid_roles, true ) ) {
					$sanitized['allowed_roles'][] = $role;
				}
			}
		}

		return $sanitized;
	}
}
