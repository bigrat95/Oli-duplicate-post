# Oli Duplicate Post

A WordPress plugin to clone posts, pages, and custom post types with Clone, New Draft, and Rewrite & Republish features.

## Features

- **Three Duplication Modes:**
  - **Clone** - Create an exact copy with all settings
  - **New Draft** - Create a draft copy for further editing
  - **Rewrite & Republish** - Edit a copy of a published post and merge it back on publish

- **Multiple Access Points:**
  - Row actions in post list
  - Bulk actions for multiple posts
  - Admin bar menu
  - Edit screen meta box
  - Block editor and classic editor support

- **Comprehensive Settings:**
  - Choose what to copy (title, content, meta, taxonomies, etc.)
  - Exclude specific meta fields with wildcard support
  - Enable/disable for specific post types
  - Restrict access by user role

- **WooCommerce Compatible:**
  - Full support for WooCommerce products including meta, attributes, and taxonomies

- **Developer Friendly:**
  - Template tag: `oli_dp_duplicate_post()`
  - Extensive hooks and filters
  - Well-documented code

## Installation

1. Upload the `oli-duplicate-post` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Duplicate Post to configure options
4. Start duplicating posts from the post list or edit screen

## Usage

### Via WordPress Admin

1. Navigate to Posts, Pages, or any custom post type list
2. Hover over a post and click "Clone", "New Draft", or "Rewrite & Republish"
3. Or select multiple posts and use the bulk action
4. Or use the admin bar menu while viewing/editing a post

### Via Template Tag

```php
<?php
// Clone a post
$new_post_id = oli_dp_duplicate_post( $post_id, 'clone' );

// Create a new draft
$new_post_id = oli_dp_duplicate_post( $post_id, 'new_draft' );

// Start rewrite & republish
$new_post_id = oli_dp_duplicate_post( $post_id, 'rewrite_republish' );

// With custom settings
$new_post_id = oli_dp_duplicate_post( $post_id, 'clone', array(
    'copy_author' => true,
    'copy_date' => true,
    'title_suffix' => ' - Copy',
) );
?>
```

## Developer Hooks

### Actions

```php
// After a post is duplicated
do_action( 'oli_dp_post_duplicated', $new_post_id, $original_post, $mode, $settings );

// After a rewrite copy is merged back to original
do_action( 'oli_dp_rewrite_republish_merged', $original_post_id, $rewrite_post_id );
```

### Filters

```php
// Modify post data before duplication
add_filter( 'oli_dp_pre_duplicate_post_data', function( $new_post_data, $post, $mode, $settings ) {
    // Modify $new_post_data as needed
    return $new_post_data;
}, 10, 4 );
```

## Requirements

- WordPress 6.5 or higher
- PHP 7.4 or higher

## Testing

### Run Smoke Tests

```bash
php tests/smoke-test.php
```

### Run PHPCS

```bash
composer global require wp-coding-standards/wpcs
phpcs --standard=WordPress --extensions=php .
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for all changes.

## License

GPL v2 or later - see LICENSE file for details.

## Author

**Olivier Bigras**
- Website: [olivierbigras.com](https://olivierbigras.com/)
- WordPress.org: [bigrat95](https://profiles.wordpress.org/bigrat95/)
- GitHub: [olivierbigras/Oli-duplicate-post](https://github.com/olivierbigras/Oli-duplicate-post)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For support, please use the WordPress.org support forums or open an issue on GitHub.
