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
		$settings = get_option( 'oli_dp_settings', Oli_DP_Settings::get_default_settings() );
		
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

		$clone_url = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'clone' );
		$new_draft_url = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'new_draft' );
		
		?>
		<div class="oli-dp-meta-box">
			<p>
				<a href="<?php echo esc_url( $clone_url ); ?>" class="button button-secondary button-large" style="width: 100%; text-align: center; margin-bottom: 8px;">
					<?php esc_html_e( 'Clone', 'oli-duplicate-post' ); ?>
				</a>
			</p>
			<p>
				<a href="<?php echo esc_url( $new_draft_url ); ?>" class="button button-secondary button-large" style="width: 100%; text-align: center; margin-bottom: 8px;">
					<?php esc_html_e( 'New Draft', 'oli-duplicate-post' ); ?>
				</a>
			</p>
			<?php if ( 'publish' === $post->post_status ) : ?>
				<?php
				$existing_rewrite = get_post_meta( $post->ID, '_oli_dp_rewrite_copy', true );
				
				if ( ! $existing_rewrite ) {
					$rewrite_url = Oli_DP_Hooks::get_duplicate_url( $post->ID, 'rewrite_republish' );
					?>
					<p>
						<a href="<?php echo esc_url( $rewrite_url ); ?>" class="button button-secondary button-large" style="width: 100%; text-align: center;">
							<?php esc_html_e( 'Rewrite & Republish', 'oli-duplicate-post' ); ?>
						</a>
					</p>
				<?php } else { ?>
					<?php $edit_url = get_edit_post_link( $existing_rewrite ); ?>
					<p>
						<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-primary button-large" style="width: 100%; text-align: center;">
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
		$settings = get_option( 'oli_dp_settings', Oli_DP_Settings::get_default_settings() );
		
		if ( empty( $settings['show_edit_screen'] ) ) {
			return;
		}

		$post = get_post();
		
		if ( ! $post || ! Oli_DP_Permissions::can_duplicate( $post->ID ) ) {
			return;
		}

		// Add inline script to add duplicate buttons to block editor.
		$inline_script = "
		( function( wp ) {
			if ( ! wp.plugins || ! wp.editPost || ! wp.element || ! wp.components ) {
				return;
			}

			var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
			var createElement = wp.element.createElement;
			var Button = wp.components.Button;
			var registerPlugin = wp.plugins.registerPlugin;

			var OliDPPanel = function() {
				var cloneUrl = '" . esc_js( Oli_DP_Hooks::get_duplicate_url( $post->ID, 'clone' ) ) . "';
				var newDraftUrl = '" . esc_js( Oli_DP_Hooks::get_duplicate_url( $post->ID, 'new_draft' ) ) . "';
				
				return createElement(
					PluginDocumentSettingPanel,
					{
						name: 'oli-duplicate-post-panel',
						title: '" . esc_js( __( 'Duplicate Post', 'oli-duplicate-post' ) ) . "',
						className: 'oli-duplicate-post-panel'
					},
					createElement(
						'div',
						{ style: { padding: '0 16px 16px' } },
						createElement(
							Button,
							{
								href: cloneUrl,
								variant: 'secondary',
								style: { width: '100%', marginBottom: '8px', justifyContent: 'center' }
							},
							'" . esc_js( __( 'Clone', 'oli-duplicate-post' ) ) . "'
						),
						createElement(
							Button,
							{
								href: newDraftUrl,
								variant: 'secondary',
								style: { width: '100%', justifyContent: 'center' }
							},
							'" . esc_js( __( 'New Draft', 'oli-duplicate-post' ) ) . "'
						)
					)
				);
			};

			registerPlugin( 'oli-duplicate-post', {
				render: OliDPPanel,
				icon: null
			} );
		} )( window.wp );
		";

		wp_add_inline_script( 'wp-plugins', $inline_script );
	}
}
