<?php
/**
 * Comparison screen for Rewrite & Republish.
 *
 * @package Oli_Duplicate_Post
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Oli_DP_Comparison
 */
class Oli_DP_Comparison {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_comparison_page' ) );
	}

	/**
	 * Add comparison page (hidden from menu).
	 */
	public static function add_comparison_page() {
		add_submenu_page(
			null,
			__( 'Compare Changes', 'oli-duplicate-post' ),
			__( 'Compare Changes', 'oli-duplicate-post' ),
			'edit_posts',
			'oli-dp-compare',
			array( __CLASS__, 'render_comparison_page' )
		);
	}

	/**
	 * Render comparison page.
	 */
	public static function render_comparison_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'oli-duplicate-post' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only comparison screen with GET parameters.
		$original_id = isset( $_GET['original'] ) ? absint( $_GET['original'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only comparison screen with GET parameters.
		$rewrite_id = isset( $_GET['rewrite'] ) ? absint( $_GET['rewrite'] ) : 0;

		if ( ! $original_id || ! $rewrite_id ) {
			wp_die( esc_html__( 'Invalid post IDs.', 'oli-duplicate-post' ) );
		}

		$original = get_post( $original_id );
		$rewrite  = get_post( $rewrite_id );

		if ( ! $original || ! $rewrite ) {
			wp_die( esc_html__( 'Posts not found.', 'oli-duplicate-post' ) );
		}

		// Verify this is actually a rewrite relationship.
		$stored_original = get_post_meta( $rewrite_id, '_oli_dp_original_post', true );
		if ( absint( $stored_original ) !== $original_id ) {
			wp_die( esc_html__( 'Invalid rewrite relationship.', 'oli-duplicate-post' ) );
		}

		?>
		<div class="wrap oli-dp-comparison-wrap">
			<h1><?php esc_html_e( 'Compare Changes - Rewrite & Republish', 'oli-duplicate-post' ); ?></h1>
			
			<div class="oli-dp-comparison-header">
				<p class="description">
					<?php
					printf(
						/* translators: 1: original post title */
						esc_html__( 'Comparing your rewritten version with the original "%s". When you publish the rewrite, these changes will replace the original.', 'oli-duplicate-post' ),
						esc_html( $original->post_title )
					);
					?>
				</p>
				<p>
					<a href="<?php echo esc_url( get_edit_post_link( $rewrite_id ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Continue Editing', 'oli-duplicate-post' ); ?>
					</a>
					<a href="<?php echo esc_url( get_edit_post_link( $original_id ) ); ?>" class="button">
						<?php esc_html_e( 'View Original', 'oli-duplicate-post' ); ?>
					</a>
				</p>
			</div>

			<div class="oli-dp-comparison-container">
				<table class="oli-dp-comparison-table widefat">
					<thead>
						<tr>
							<th style="width: 20%;"><?php esc_html_e( 'Field', 'oli-duplicate-post' ); ?></th>
							<th style="width: 40%;"><?php esc_html_e( 'Original', 'oli-duplicate-post' ); ?></th>
							<th style="width: 40%;"><?php esc_html_e( 'Rewrite', 'oli-duplicate-post' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php self::render_comparison_row( 'Title', $original->post_title, $rewrite->post_title ); ?>
						<?php self::render_comparison_row( 'Status', $original->post_status, $rewrite->post_status ); ?>
						<?php self::render_comparison_row( 'Date', $original->post_date, $rewrite->post_date ); ?>
						<?php self::render_comparison_row( 'Excerpt', $original->post_excerpt, $rewrite->post_excerpt ); ?>
						<?php self::render_comparison_row( 'Content', $original->post_content, $rewrite->post_content, true ); ?>
						
						<?php
						// Featured image.
						$original_thumb = get_post_thumbnail_id( $original_id );
						$rewrite_thumb  = get_post_thumbnail_id( $rewrite_id );
						if ( $original_thumb || $rewrite_thumb ) {
							$original_img = $original_thumb ? wp_get_attachment_image( $original_thumb, 'thumbnail' ) : __( 'No image', 'oli-duplicate-post' );
							$rewrite_img  = $rewrite_thumb ? wp_get_attachment_image( $rewrite_thumb, 'thumbnail' ) : __( 'No image', 'oli-duplicate-post' );
							self::render_comparison_row( 'Featured Image', $original_img, $rewrite_img, false, true );
						}

						// Categories/Tags.
						$taxonomies = get_object_taxonomies( $original->post_type, 'objects' );
						foreach ( $taxonomies as $taxonomy ) {
							if ( ! $taxonomy->public ) {
								continue;
							}
							$original_terms = wp_get_post_terms( $original_id, $taxonomy->name, array( 'fields' => 'names' ) );
							$rewrite_terms  = wp_get_post_terms( $rewrite_id, $taxonomy->name, array( 'fields' => 'names' ) );

							if ( ! empty( $original_terms ) || ! empty( $rewrite_terms ) ) {
								$original_terms_str = is_array( $original_terms ) ? implode( ', ', $original_terms ) : '';
								$rewrite_terms_str  = is_array( $rewrite_terms ) ? implode( ', ', $rewrite_terms ) : '';
								self::render_comparison_row( $taxonomy->label, $original_terms_str, $rewrite_terms_str );
							}
						}
						?>
					</tbody>
				</table>
			</div>

			<div class="oli-dp-comparison-actions">
				<p>
					<a href="<?php echo esc_url( get_edit_post_link( $rewrite_id ) ); ?>" class="button button-primary button-large">
						<?php esc_html_e( 'Continue Editing Rewrite', 'oli-duplicate-post' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $original->post_type ) ); ?>" class="button button-large">
						<?php esc_html_e( 'Back to List', 'oli-duplicate-post' ); ?>
					</a>
				</p>
			</div>
		</div>

		<style>
			.oli-dp-comparison-wrap {
				margin-top: 20px;
			}
			.oli-dp-comparison-header {
				background: #fff;
				border: 1px solid #c3c4c7;
				padding: 15px 20px;
				margin-bottom: 20px;
			}
			.oli-dp-comparison-container {
				background: #fff;
				border: 1px solid #c3c4c7;
				margin-bottom: 20px;
			}
			.oli-dp-comparison-table {
				margin: 0;
			}
			.oli-dp-comparison-table th {
				background: #f6f7f7;
				font-weight: 600;
				padding: 12px;
			}
			.oli-dp-comparison-table td {
				padding: 12px;
				vertical-align: top;
				border-top: 1px solid #e0e0e0;
			}
			.oli-dp-comparison-table tr.oli-dp-diff td:nth-child(2),
			.oli-dp-comparison-table tr.oli-dp-diff td:nth-child(3) {
				background: #fff8dc;
			}
			.oli-dp-comparison-table .oli-dp-field-label {
				font-weight: 600;
			}
			.oli-dp-comparison-table .oli-dp-content-preview {
				max-height: 200px;
				overflow-y: auto;
				background: #f9f9f9;
				padding: 10px;
				border: 1px solid #ddd;
				font-family: monospace;
				font-size: 12px;
				white-space: pre-wrap;
				word-wrap: break-word;
			}
			.oli-dp-comparison-actions {
				background: #fff;
				border: 1px solid #c3c4c7;
				padding: 15px 20px;
			}
		</style>
		<?php
	}

	/**
	 * Render a comparison row.
	 *
	 * @param string $label      Field label.
	 * @param string $original   Original value.
	 * @param string $rewrite    Rewrite value.
	 * @param bool   $long_text  Whether this is long text content.
	 * @param bool   $is_html    Whether the content contains HTML.
	 */
	private static function render_comparison_row( $label, $original, $rewrite, $long_text = false, $is_html = false ) {
		$is_different = $original !== $rewrite;
		$row_class    = $is_different ? 'oli-dp-diff' : '';

		?>
		<tr class="<?php echo esc_attr( $row_class ); ?>">
			<td class="oli-dp-field-label"><?php echo esc_html( $label ); ?></td>
			<td>
				<?php
				if ( $is_html ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $original;
				} elseif ( $long_text ) {
					echo '<div class="oli-dp-content-preview">' . esc_html( wp_trim_words( $original, 100 ) ) . '</div>';
				} else {
					echo esc_html( $original );
				}
				?>
			</td>
			<td>
				<?php
				if ( $is_html ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $rewrite;
				} elseif ( $long_text ) {
					echo '<div class="oli-dp-content-preview">' . esc_html( wp_trim_words( $rewrite, 100 ) ) . '</div>';
				} else {
					echo esc_html( $rewrite );
				}
				?>
			</td>
		</tr>
		<?php
	}
}
