=== Oli Duplicate Post ===
Contributors: bigrat95
Tags: duplicate, clone, copy, post, page
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Clone posts, pages, and custom post types with Clone, New Draft, and Rewrite & Republish features.

== Description ==

Oli Duplicate Post makes it easy to duplicate posts, pages, and custom post types in WordPress. With three different duplication modes and extensive customization options, you have complete control over how your content is duplicated.

= Features =

* **Three Duplication Modes:**
  * **Clone** - Create an exact copy with all settings
  * **New Draft** - Create a draft copy for further editing
  * **Rewrite & Republish** - Edit a copy of a published post and merge it back on publish

* **Multiple Access Points:**
  * Row actions in post list
  * Bulk actions for multiple posts
  * Admin bar menu
  * Edit screen meta box
  * Block editor and classic editor support

* **Comprehensive Copying Options:**
  * Title (with customizable prefix/suffix)
  * Content, excerpt, and featured image
  * Meta fields and taxonomies
  * Post format and template
  * Author, date, status, and slug
  * Attachments and child posts (optional)
  * Comments (optional)
  * Full WooCommerce product support

* **Settings (Settings > Duplicate Post):**
  * Where to duplicate: public content types, roles that can edit posts, and Rewrite & Republish
  * The copy: title prefix/suffix with a live preview, and draft or same-status copies
  * Advanced copied elements, excluded taxonomies, excluded meta (one key per line, `*` wildcards), and where copy links appear

* **Developer Friendly:**
  * Template tag: `oli_dp_duplicate_post()`
  * Extensive hooks and filters
  * Well-documented code

= Rewrite & Republish =

The Rewrite & Republish feature lets you create a copy of a published post, make changes to it, and then replace the original when you're ready. This is perfect for major updates to live content without taking the original post offline.

1. Click "Rewrite & Republish" on a published post
2. Edit the copy as needed
3. When you publish the copy, it automatically replaces the original
4. The copy is deleted, and the original is updated with your changes

= Template Tag =

Use the template tag in your theme or plugin:

`
<?php
$new_post_id = oli_dp_duplicate_post( $post_id, 'clone' );
?>
`

Parameters:
* `$post_id` (int) - The post ID to duplicate
* `$mode` (string) - 'clone', 'new_draft', or 'rewrite_republish'
* `$args` (array) - Optional settings override

= Hooks & Filters =

**Actions:**
* `oli_dp_post_duplicated` - After a post is duplicated
* `oli_dp_rewrite_republish_merged` - After a rewrite copy is merged

**Filters:**
* `oli_dp_pre_duplicate_post_data` - Modify post data before duplication

== Installation ==

1. Upload the `oli-duplicate-post` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Duplicate Post to configure options
4. Start duplicating posts from the post list or edit screen

== Frequently Asked Questions ==

= Does this work with custom post types? =

Yes! You can enable duplication for any custom post type, including WooCommerce products, in the plugin settings.

= Does it work with the block editor? =

Yes! The plugin works with both the block editor (Gutenberg) and the classic editor.

= Can I exclude certain meta fields from being copied? =

Yes! In the settings, you can specify meta field keys to exclude. Wildcard patterns are supported (e.g., `_yoast_*` to exclude all Yoast meta fields).

= What's the difference between Clone and New Draft? =

Clone creates a copy with the same status and settings as the original. New Draft always creates a draft post regardless of the original's status.

= Is this compatible with WooCommerce? =

Yes! The plugin fully supports WooCommerce products, including all product meta, attributes, and taxonomies.

= Can I restrict duplication to certain user roles? =

Yes! In the settings, you can choose which user roles are allowed to duplicate posts.

= Does this copy all post relationships? =

The plugin copies meta fields, taxonomies, featured images, and optionally attachments and child posts. Some complex plugin-specific relationships may require custom code using the provided filters.

== Screenshots ==

1. Row actions in post list
2. Bulk actions for multiple posts
3. Admin bar menu
4. Edit screen meta box
5. Settings page - Where to duplicate
6. Settings page - The copy
7. Settings page - Advanced copied elements
8. Rewrite & Republish notice

== External services ==

This plugin does not call any external service. Clone, New Draft, and Rewrite & Republish run entirely on your WordPress site. No visitor or editor data is sent to olivierbigras.com, PayPal, Google, Cloudflare Turnstile, Carto, Leaflet, Google Sheets, Google Fonts, Adobe Typekit, or any other third party.

== Changelog ==

= 0.2.0 - 2026-10-21 =
* Reorganized Settings > Duplicate Post into Where to duplicate, The copy, and a collapsed Advanced section
* Added enable_rewrite, live title preview, featured-image copy control, and draft vs same-status radios
* Soft-migrated existing option keys (empty suffix, newline meta keys, taxonomy checkboxes)
* Updated success, error, and rewrite notices, plus comparison Original/Copy headers
* Added fieldset/legend groups, aria-labels on Duplicate links, and sentence-case section titles
* Excluded development files from the WordPress.org ZIP via .distignore and .gitattributes

= 0.1.2 - 2026-10-21 =
* Moved block editor and Quick Edit JavaScript from inline scripts into enqueued files
* Moved admin CSS from inline styles into an enqueued stylesheet
* Localized editor URLs and labels with wp_localize_script
* Escaped comparison featured-image HTML with wp_kses_post
* Added current_user_can checks on comparison and duplicate admin actions

= 0.1.1 - 2026-10-19 =
* Added side-by-side comparison screen for Rewrite & Republish
* Added "Compare changes" link in rewrite notice
* Added block editor toolbar menu items (Options menu)
* Enhanced block editor with sidebar panel and toolbar access
* Added quick edit integration with "Copy to new draft" button
* Added scheduled republish UI with meta box and publish box warnings
* Added success notices after duplication actions (dismissible)
* Added success notice after successful republish
* Improved admin notices throughout the plugin
* Improved block editor integration
* Fixed PHPCS compliance issues
* Fixed PHP syntax validation

= 0.1.0 - 2026-10-19 =
* Initial release
* Clone posts, pages, and custom post types
* Three duplication modes: Clone, New Draft, Rewrite & Republish
* Comprehensive settings page
* Multiple UI entry points
* Full WooCommerce support
* Developer hooks and filters
* Block editor support

== Upgrade Notice ==

= 0.2.0 =
Settings > Duplicate Post is reorganized. Existing options are kept and migrated softly.

= 0.1.2 =
Moves remaining inline JavaScript and CSS into enqueued files for WordPress.org Plugin Check.

= 0.1.1 =
Adds comparison screen, enhanced block editor integration, quick edit support, and improved admin notices.

= 0.1.0 =
Initial release of Oli Duplicate Post.

== Privacy Policy ==

Oli Duplicate Post does not collect, store, or transmit any user data. All duplication operations happen locally on your WordPress installation.

== Development ==

Development happens on GitHub: https://github.com/olivierbigras/Oli-duplicate-post

Contributions are welcome!
