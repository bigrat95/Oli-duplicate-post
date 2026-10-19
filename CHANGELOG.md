# Changelog

All notable changes to Oli Duplicate Post will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[0.1.0]: https://github.com/yourusername/oli-duplicate-post/releases/tag/0.1.0
