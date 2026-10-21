# Changelog

All notable changes to Oli Duplicate Post will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2026-10-21

### Added
- Settings page reorganized into **Where to duplicate**, **The copy**, and a collapsed **Advanced** section
- `enable_rewrite` setting to turn Rewrite & Republish off
- Live title preview (`aria-live`) for prefix and suffix
- Featured image (`copy_thumbnail`) among the copied elements
- `.distignore` and `.gitattributes` `export-ignore` rules so development files stay out of the WordPress.org ZIP

### Changed
- Existing option keys are kept; stored values are soft-migrated (empty title suffix, newline meta keys, taxonomy checkboxes)
- Default title suffix is stored empty and becomes a translated `(Copy)` at runtime, with the leading space added in code
- Copy status is a radio: draft (recommended) or the same status as the original, with an inline warning
- Excluded taxonomies are generated from `get_taxonomies()`; reserved meta keys are always excluded and shown read-only
- Success notice is `Copy created: %s` with an Edit copy link; errors use `notice-error`
- Rewrite banner is `You are editing a copy of %s. Publish to replace the original.` with a Compare link
- Comparison screen uses Original and Copy headers, plus a Changed label so differences are not color-only
- Duplicate links include an `aria-label` with the post title; settings groups use `fieldset`/`legend`
- Section titles use sentence case
- Default enabled types/roles stay post, page, administrator, and editor, and add product / shop_manager when WooCommerce is active

## [0.1.2] - 2026-10-21

### Changed
- Extracted the block editor sidebar/Options-menu script to `assets/js/block-editor.js` and enqueue it with `wp_enqueue_script` + `wp_localize_script`
- Extracted the Quick Edit "Copy to New Draft" script to `assets/js/quick-edit.js`
- Moved admin and comparison styles to `assets/css/admin.css` (no inline `<style>` or `wp_add_inline_style`)

### Fixed
- Escape comparison featured-image markup with `wp_kses_post`
- Require `current_user_can` / `Oli_DP_Permissions::can_duplicate` on comparison and duplicate admin actions
- Document in `readme.txt` that the plugin does not call external services

## [0.1.1] - 2026-10-19

### Added
- Side-by-side comparison screen for Rewrite & Republish (`admin.php?page=oli-dp-compare`)
- "Compare changes" link in rewrite notice
- Block editor toolbar menu items (Options menu → Clone, Copy to New Draft, Rewrite & Republish)
- Enhanced block editor sidebar panel with all three duplication modes
- Quick edit integration with "Copy to new draft" button
- Scheduled republish UI with meta box showing republish schedule info
- Publish box warning indicator for Rewrite & Republish mode
- Success notices after duplication actions (dismissible)
- Success notice after successful republish with transient-based messaging

### Improved
- Better admin notices throughout the plugin
- Enhanced block editor integration with both sidebar and toolbar access
- More intuitive Rewrite & Republish workflow with visual feedback

### Fixed
- PHPCS compliance issues
- PHP syntax validation
- GitHub Actions PHPCS job (exclude CLI smoke tests, add phpcs.xml.dist and Composer install)
- GitHub repository URLs after owner rename to olivierbigras (wordpress.org contributor remains bigrat95)

## [0.1.0] - 2026-10-19

### Added
- Initial release of Oli Duplicate Post
- Three duplication modes: Clone, New Draft, and Rewrite & Republish
- Duplicate posts, pages, and custom post types
- Row actions in post list for quick duplication
- Bulk actions for duplicating multiple posts at once
- Admin bar menu for easy access
- Edit screen meta box (classic and block editor)
- Comprehensive settings page with the following options:
  - Choose what to copy (title, content, meta, taxonomies, etc.)
  - Configure title prefix and suffix
  - Exclude specific meta fields (with wildcard support)
  - Exclude specific taxonomies
  - Enable/disable for specific post types
  - Restrict access by user role
  - Customize UI locations
- Full WooCommerce product support including meta, attributes, and taxonomies
- Template tag `oli_dp_duplicate_post()` for programmatic duplication
- Developer hooks and filters:
  - `oli_dp_post_duplicated` action
  - `oli_dp_rewrite_republish_merged` action
  - `oli_dp_pre_duplicate_post_data` filter
- Block editor (Gutenberg) support
- Classic editor support
- i18n ready with .pot file
- WordPress.org compliant code (sanitization, escaping, nonces, capabilities)
- PHPCS compliance with WordPress Coding Standards
- Plugin Check (PCP) compliance

### Security
- All user inputs properly sanitized and escaped
- Nonce verification for all actions
- Capability checks for all operations
- No external calls or tracking

[0.2.0]: https://github.com/olivierbigras/Oli-duplicate-post/releases/tag/0.2.0
[0.1.2]: https://github.com/olivierbigras/Oli-duplicate-post/releases/tag/0.1.2
[0.1.1]: https://github.com/olivierbigras/Oli-duplicate-post/releases/tag/0.1.1
[0.1.0]: https://github.com/olivierbigras/Oli-duplicate-post/releases/tag/0.1.0
