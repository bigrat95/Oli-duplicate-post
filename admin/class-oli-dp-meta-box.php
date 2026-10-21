<?php
/**
 * Edit screen meta box.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Meta_Box
 */
class Oli_DP_Meta_Box {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Add meta box to edit screen.
	 */
	public static function add_meta_box() {
		// Check if edit screen should show duplicate box.
		$settings = Oli_DP_Settings::get_settings();

		if ( empty( $settings['show_edit_screen'] ) ) {
			return;
		}

		$enabled_post_types = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array();

		foreach ( $enabled_post_types as $post_type ) {
			add_meta_box(
				'oli_dp_duplicate_box',
				__( 'Duplicate Post', 'oli-duplicate-post' ),
				array( __CLASS__, 'render_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render meta box content.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function render_meta_box( $post ) {
		if ( ! Oli_DP_Permissions::can_duplicate( $post->ID ) ) {
			esc_html_e( 'You do not have permission to duplicate this post.', 'oli-duplicate-post' );
			return;
		}

		$clone_url     = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'clone' );
		$new_draft_url = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'new_draft' );
		/* translators: %s: post title */
		$clone_label = sprintf( __( 'Clone &#8220;%s&#8221;', 'oli-duplicate-post' ), $post->post_title );
		/* translators: %s: post title */
		$draft_label = sprintf( __( 'New Draft of &#8220;%s&#8221;', 'oli-duplicate-post' ), $post->post_title );

		?>
		<div class="oli-dp-meta-box">
			<p>
				<a href="<?php echo esc_url( $clone_url ); ?>" class="button button-secondary button-large oli-dp-button-full" aria-label="<?php echo esc_attr( $clone_label ); ?>">
					<?php esc_html_e( 'Clone', 'oli-duplicate-post' ); ?>
				</a>
			</p>
			<p>
				<a href="<?php echo esc_url( $new_draft_url ); ?>" class="button button-secondary button-large oli-dp-button-full" aria-label="<?php echo esc_attr( $draft_label ); ?>">
					<?php esc_html_e( 'New Draft', 'oli-duplicate-post' ); ?>
				</a>
			</p>
			<?php if ( Oli_DP_Settings::is_rewrite_enabled() && 'publish' === $post->post_status ) : ?>
				<?php
				$existing_rewrite = get_post_meta( $post->ID, '_oli_dp_rewrite_copy', true );

				if ( ! $existing_rewrite ) {
					$rewrite_url = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'rewrite_republish' );
					/* translators: %s: post title */
					$rewrite_label = sprintf( __( 'Rewrite & Republish &#8220;%s&#8221;', 'oli-duplicate-post' ), $post->post_title );
					?>
					<p>
						<a href="<?php echo esc_url( $rewrite_url ); ?>" class="button button-secondary button-large oli-dp-button-full" aria-label="<?php echo esc_attr( $rewrite_label ); ?>">
							<?php esc_html_e( 'Rewrite & Republish', 'oli-duplicate-post' ); ?>
						</a>
					</p>
				<?php } else { ?>
					<?php
					$edit_url = get_edit_post_link( $existing_rewrite );
					/* translators: %s: post title */
					$edit_label = sprintf( __( 'Edit Rewrite Copy of &#8220;%s&#8221;', 'oli-duplicate-post' ), $post->post_title );
					?>
					<p>
						<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-primary button-large oli-dp-button-full" aria-label="<?php echo esc_attr( $edit_label ); ?>">
							<?php esc_html_e( 'Edit Rewrite Copy', 'oli-duplicate-post' ); ?>
						</a>
					</p>
				<?php } ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Enqueue block editor assets.
	 */
	public static function enqueue_block_editor_assets() {
		// Check if edit screen should show duplicate box.
		$settings = Oli_DP_Settings::get_settings();

		if ( empty( $settings['show_edit_screen'] ) ) {
			return;
		}

		$post = get_post();

		if ( ! $post || ! Oli_DP_Permissions::can_duplicate( $post->ID ) ) {
			return;
		}

		$clone_url     = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'clone' );
		$new_draft_url = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'new_draft' );
		$rewrite_url   = '';

		if ( Oli_DP_Settings::is_rewrite_enabled( $settings ) && 'publish' === $post->post_status ) {
			$existing_rewrite = get_post_meta( $post->ID, '_oli_dp_rewrite_copy', true );
			if ( ! $existing_rewrite ) {
				$rewrite_url = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'rewrite_republish' );
			}
		}

		wp_enqueue_style(
			'oli-dp-admin',
			OLI_DP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			OLI_DP_VERSION
		);

		wp_enqueue_script(
			'oli-dp-block-editor',
			OLI_DP_PLUGIN_URL . 'assets/js/block-editor.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-i18n' ),
			OLI_DP_VERSION,
			true
		);

		wp_localize_script(
			'oli-dp-block-editor',
			'oliDpBlockEditor',
			array(
				'cloneUrl'    => $clone_url,
				'newDraftUrl' => $new_draft_url,
				'rewriteUrl'  => $rewrite_url,
				'i18n'        => array(
					'duplicatePost'    => __( 'Duplicate Post', 'oli-duplicate-post' ),
					'clone'            => __( 'Clone', 'oli-duplicate-post' ),
					'newDraft'         => __( 'New Draft', 'oli-duplicate-post' ),
					'copyToNewDraft'   => __( 'Copy to New Draft', 'oli-duplicate-post' ),
					'rewriteRepublish' => __( 'Rewrite & Republish', 'oli-duplicate-post' ),
					/* translators: %s: post title */
					'cloneLabel'       => sprintf( __( 'Clone &#8220;%s&#8221;', 'oli-duplicate-post' ), $post->post_title ),
					/* translators: %s: post title */
					'newDraftLabel'    => sprintf( __( 'New Draft of &#8220;%s&#8221;', 'oli-duplicate-post' ), $post->post_title ),
					/* translators: %s: post title */
					'rewriteLabel'     => sprintf( __( 'Rewrite & Republish &#8220;%s&#8221;', 'oli-duplicate-post' ), $post->post_title ),
				),
			)
		);

		wp_set_script_translations( 'oli-dp-block-editor', 'oli-duplicate-post', OLI_DP_PLUGIN_DIR . 'languages' );
	}
}
