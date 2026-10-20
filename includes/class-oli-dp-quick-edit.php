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
		add_action( 'admin_footer-edit.php', array( __CLASS__, 'add_quick_edit_link' ) );
		add_action( 'quick_edit_custom_box', array( __CLASS__, 'render_quick_edit_custom_box' ), 10, 2 );
	}

	/**
	 * Add JavaScript to add duplicate link to quick edit.
	 */
	public static function add_quick_edit_link() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit' !== $screen->base ) {
			return;
		}

		$settings = get_option( 'oli_dp_settings', Oli_DP_Settings::get_default_settings() );
		if ( empty( $settings['show_row_actions'] ) ) {
			return;
		}

		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Add "Copy to new draft" link in quick edit
			$('.editinline').on('click', function() {
				var postId = $(this).closest('tr').attr('id').replace('post-', '');
				var postRow = $('#post-' + postId);
				
				// Get the duplicate URL from the row actions
				var cloneLink = postRow.find('.oli_dp_clone a, .oli_dp_new_draft a').first().attr('href');
				
				if (cloneLink) {
					// Wait for quick edit to open
					setTimeout(function() {
						var quickEditRow = $('#edit-' + postId);
						if (quickEditRow.length && !quickEditRow.find('.oli-dp-quick-copy').length) {
							var copyButton = $('<div class="oli-dp-quick-copy" style="margin: 10px 0;">' +
								'<a href="' + cloneLink + '" class="button button-secondary">' +
								'<?php echo esc_js( __( 'Copy to New Draft', 'oli-duplicate-post' ) ); ?>' +
								'</a></div>');
							quickEditRow.find('.inline-edit-col-left').append(copyButton);
						}
					}, 50);
				}
			});
		});
		</script>
		<?php
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
