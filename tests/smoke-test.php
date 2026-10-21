<?php
/**
 * Smoke tests for Oli Duplicate Post plugin.
 *
 * This script performs basic validation checks on the plugin files
 * without requiring a WordPress installation or WP-CLI.
 *
 * Usage: php tests/smoke-test.php
 *
 * @package Oli_Duplicate_Post
 */

/**
 * Print a passing test line.
 *
 * @param string $message Message to print.
 */
function oli_dp_test_pass( $message ) {
	echo "\033[0;32m✓\033[0m " . $message . "\n";
}

/**
 * Print a failing test line.
 *
 * @param string $message Message to print.
 */
function oli_dp_test_fail( $message ) {
	echo "\033[0;31m✗\033[0m " . $message . "\n";
}

/**
 * Print a section header.
 *
 * @param string $message Section title.
 */
function oli_dp_test_section( $message ) {
	echo "\n\033[1;36m" . $message . "\033[0m\n";
	echo str_repeat( '=', 60 ) . "\n";
}

/**
 * Recursively collect PHP files, skipping vendor.
 *
 * @param string $dir Directory to scan.
 * @return array
 */
function oli_dp_collect_php_files( $dir ) {
	$files  = array();
	$skip   = array( 'vendor', 'node_modules', '.git' );
	$handle = opendir( $dir );

	if ( false === $handle ) {
		return $files;
	}

	while ( false !== ( $entry = readdir( $handle ) ) ) {
		if ( '.' === $entry || '..' === $entry || in_array( $entry, $skip, true ) ) {
			continue;
		}

		$path = $dir . '/' . $entry;
		if ( is_dir( $path ) ) {
			$files = array_merge( $files, oli_dp_collect_php_files( $path ) );
		} elseif ( '.php' === substr( $entry, -4 ) ) {
			$files[] = $path;
		}
	}

	closedir( $handle );
	return $files;
}

$total_tests  = 0;
$passed_tests = 0;
$plugin_root  = dirname( __DIR__ );

oli_dp_test_section( 'Oli Duplicate Post - Smoke Tests' );

oli_dp_test_section( 'File Structure Tests' );
++$total_tests;
if ( file_exists( $plugin_root . '/oli-duplicate-post.php' ) ) {
	oli_dp_test_pass( 'Main plugin file exists' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Main plugin file not found' );
}

$required_dirs = array( 'includes', 'admin', 'assets', 'assets/js', 'assets/css', 'languages', 'tests', '.github/workflows' );
foreach ( $required_dirs as $dir ) {
	++$total_tests;
	if ( is_dir( $plugin_root . '/' . $dir ) ) {
		oli_dp_test_pass( "Directory exists: {$dir}" );
		++$passed_tests;
	} else {
		oli_dp_test_fail( "Directory missing: {$dir}" );
	}
}

$required_files = array(
	'includes/class-oli-dp-permissions.php',
	'includes/class-oli-dp-settings.php',
	'includes/class-oli-dp-duplicator.php',
	'includes/class-oli-dp-rewrite-republish.php',
	'includes/class-oli-dp-comparison.php',
	'includes/class-oli-dp-hooks.php',
	'includes/class-oli-dp-admin.php',
	'includes/class-oli-dp-quick-edit.php',
	'admin/class-oli-dp-post-list.php',
	'admin/class-oli-dp-bulk-actions.php',
	'admin/class-oli-dp-admin-bar.php',
	'admin/class-oli-dp-meta-box.php',
	'assets/js/block-editor.js',
	'assets/js/quick-edit.js',
	'assets/js/settings.js',
	'assets/css/admin.css',
	'phpcs.xml.dist',
	'composer.json',
	'.distignore',
	'.gitattributes',
);

foreach ( $required_files as $file ) {
	++$total_tests;
	if ( file_exists( $plugin_root . '/' . $file ) ) {
		oli_dp_test_pass( "File exists: {$file}" );
		++$passed_tests;
	} else {
		oli_dp_test_fail( "File missing: {$file}" );
	}
}

oli_dp_test_section( 'WordPress.org Compliance Tests' );
++$total_tests;
$readme_path = $plugin_root . '/readme.txt';
$readme_content = '';
if ( file_exists( $readme_path ) ) {
	oli_dp_test_pass( 'readme.txt exists' );
	++$passed_tests;

	$readme_content   = file_get_contents( $readme_path );
	$required_headers = array(
		'Contributors:',
		'Tags:',
		'Requires at least:',
		'Tested up to:',
		'Requires PHP:',
		'Stable tag:',
		'License:',
	);

	foreach ( $required_headers as $header ) {
		++$total_tests;
		if ( false !== strpos( $readme_content, $header ) ) {
			oli_dp_test_pass( "readme.txt contains: {$header}" );
			++$passed_tests;
		} else {
			oli_dp_test_fail( "readme.txt missing: {$header}" );
		}
	}

	++$total_tests;
	if ( false !== strpos( $readme_content, 'Contributors: bigrat95' ) ) {
		oli_dp_test_pass( 'readme.txt Contributors uses wordpress.org username bigrat95' );
		++$passed_tests;
	} else {
		oli_dp_test_fail( 'readme.txt Contributors should remain bigrat95' );
	}

	++$total_tests;
	if ( false !== strpos( $readme_content, 'github.com/olivierbigras' ) && false === strpos( $readme_content, 'github.com/bigrat95' ) ) {
		oli_dp_test_pass( 'readme.txt GitHub URL uses olivierbigras owner' );
		++$passed_tests;
	} else {
		oli_dp_test_fail( 'readme.txt GitHub URL should use github.com/olivierbigras' );
	}

	++$total_tests;
	if ( false !== strpos( $readme_content, '== External services ==' ) ) {
		oli_dp_test_pass( 'readme.txt has External services section' );
		++$passed_tests;
	} else {
		oli_dp_test_fail( 'readme.txt missing External services section' );
	}
} else {
	oli_dp_test_fail( 'readme.txt not found' );
}

oli_dp_test_section( 'Code Standards Tests' );
$main_file = file_get_contents( $plugin_root . '/oli-duplicate-post.php' );
$plugin_php = '';
foreach ( oli_dp_collect_php_files( $plugin_root ) as $php_path ) {
	if ( false !== strpos( $php_path, '/tests/' ) ) {
		continue;
	}
	$plugin_php .= file_get_contents( $php_path ) . "\n";
}

++$total_tests;
if ( preg_match( '/oli_dp_/', $plugin_php ) && preg_match( '/Oli_DP_/', $plugin_php ) ) {
	oli_dp_test_pass( 'Proper prefixing used (oli_dp_ and Oli_DP_)' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Prefix issues found' );
}

++$total_tests;
if ( preg_match( '/if\s*\(\s*!\s*defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)\s*\)/', $main_file ) ) {
	oli_dp_test_pass( 'ABSPATH security check present' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'ABSPATH security check missing' );
}

++$total_tests;
if ( preg_match( '/Text Domain:\s*oli-duplicate-post/', $main_file ) ) {
	oli_dp_test_pass( 'Correct text domain declared' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Text domain issue' );
}

++$total_tests;
$version_in_main   = preg_match( '/Version:\s*([\d.]+)/', $main_file, $main_version );
$version_in_readme = preg_match( '/Stable tag:\s*([\d.]+)/', $readme_content, $readme_version );
if ( $version_in_main && $version_in_readme && $main_version[1] === $readme_version[1] ) {
	oli_dp_test_pass( "Version consistency: {$main_version[1]}" );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Version mismatch between plugin file and readme.txt' );
}

++$total_tests;
if ( preg_match( '/0\.2\.0/', $main_file ) ) {
	oli_dp_test_pass( 'Plugin version is 0.2.0' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Plugin version is not 0.2.0' );
}

oli_dp_test_section( 'PHP Syntax Tests' );
$php_files = glob( $plugin_root . '/*.php' );
$php_files = array_merge( $php_files, glob( $plugin_root . '/includes/*.php' ) );
$php_files = array_merge( $php_files, glob( $plugin_root . '/admin/*.php' ) );

foreach ( $php_files as $php_file ) {
	++$total_tests;
	$output     = array();
	$return_var = 0;
	exec( 'php -l ' . escapeshellarg( $php_file ) . ' 2>&1', $output, $return_var );

	if ( 0 === $return_var ) {
		oli_dp_test_pass( 'Valid syntax: ' . basename( $php_file ) );
		++$passed_tests;
	} else {
		oli_dp_test_fail( 'Syntax error in: ' . basename( $php_file ) );
		echo '  ' . implode( "\n  ", $output ) . "\n";
	}
}

oli_dp_test_section( 'WordPress Standards Tests' );

++$total_tests;
if ( preg_match( '/wp_insert_post/', $plugin_php ) ) {
	oli_dp_test_pass( 'Uses wp_insert_post() for post creation' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'wp_insert_post() not found' );
}

++$total_tests;
if ( preg_match( '/sanitize_|esc_html|esc_attr|esc_url/', $plugin_php ) ) {
	oli_dp_test_pass( 'Uses sanitization/escaping functions' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'No sanitization functions found' );
}

++$total_tests;
if ( preg_match( '/wp_verify_nonce|wp_create_nonce/', $plugin_php ) ) {
	oli_dp_test_pass( 'Uses nonce verification' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Nonce functions not found' );
}

oli_dp_test_section( 'Asset Enqueue Tests' );

$plugin_js  = '';
$js_files   = glob( $plugin_root . '/assets/js/*.js' );
if ( is_array( $js_files ) ) {
	foreach ( $js_files as $js_file ) {
		$plugin_js .= file_get_contents( $js_file ) . "\n";
	}
}

++$total_tests;
if ( false === strpos( $plugin_php, '<script' ) && false === strpos( $plugin_php, '<style' ) ) {
	oli_dp_test_pass( 'No inline <script> or <style> tags in plugin PHP' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Inline <script> or <style> tags still present in plugin PHP' );
}

++$total_tests;
if ( false === strpos( $plugin_php, 'wp_add_inline_script' ) && false === strpos( $plugin_php, 'wp_add_inline_style' ) ) {
	oli_dp_test_pass( 'No wp_add_inline_script/style usage' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'wp_add_inline_script or wp_add_inline_style still used' );
}

++$total_tests;
if ( false !== strpos( $plugin_php, 'wp_enqueue_script' ) && false !== strpos( $plugin_php, 'wp_localize_script' ) && false !== strpos( $plugin_php, 'wp_enqueue_style' ) ) {
	oli_dp_test_pass( 'Scripts and styles are enqueued and localized' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Missing wp_enqueue_script, wp_localize_script, or wp_enqueue_style' );
}

oli_dp_test_section( 'Feature Parity Tests' );

++$total_tests;
if ( false !== strpos( $plugin_php, 'oli-dp-compare' ) ) {
	oli_dp_test_pass( 'Rewrite & Republish comparison screen is registered' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Comparison screen missing' );
}

++$total_tests;
if ( false !== strpos( $plugin_js, 'PluginMoreMenuItem' ) ) {
	oli_dp_test_pass( 'Block editor toolbar/menu items present' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Block editor toolbar items missing' );
}

++$total_tests;
if ( false !== strpos( $plugin_php, 'Copy to New Draft' ) || false !== strpos( $plugin_js, 'Copy to New Draft' ) ) {
	oli_dp_test_pass( 'Quick edit / Copy to New Draft link present' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Copy to New Draft link missing' );
}

++$total_tests;
if ( false !== strpos( $plugin_php, 'oli_dp_duplicated' ) ) {
	oli_dp_test_pass( 'Success notices after duplication actions present' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Duplication success notices missing' );
}

++$total_tests;
if ( false !== strpos( $plugin_php, 'Republish Schedule' ) ) {
	oli_dp_test_pass( 'Scheduled republish UI present' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Scheduled republish UI missing' );
}

oli_dp_test_section( 'Settings UX Tests' );

++$total_tests;
if ( false !== strpos( $plugin_php, 'Where to duplicate' ) && false !== strpos( $plugin_php, 'The copy' ) && false !== strpos( $plugin_php, 'Copied elements' ) ) {
	oli_dp_test_pass( 'Settings page has the three required sections' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Settings page is missing a required section title' );
}

++$total_tests;
if ( false !== strpos( $plugin_php, '<fieldset' ) && false !== strpos( $plugin_php, '<legend' ) ) {
	oli_dp_test_pass( 'Settings groups use fieldset and legend' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Settings groups are missing fieldset/legend' );
}

++$total_tests;
if ( false !== strpos( $plugin_php, 'enable_rewrite' ) && false !== strpos( $plugin_php, 'copy_thumbnail' ) ) {
	oli_dp_test_pass( 'New settings keys enable_rewrite and copy_thumbnail are present' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'enable_rewrite or copy_thumbnail is missing' );
}

++$total_tests;
if ( false !== strpos( $plugin_php, 'Copy created: %s' ) && false !== strpos( $plugin_php, 'Edit copy' ) ) {
	oli_dp_test_pass( 'Success notice uses Copy created + Edit copy' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Success notice copy is missing' );
}

++$total_tests;
if ( false !== strpos( $plugin_php, 'You are editing a copy of %s. Publish to replace the original.' ) && false !== strpos( $plugin_php, 'Compare' ) ) {
	oli_dp_test_pass( 'Rewrite banner uses the Designer copy + Compare link' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Rewrite banner copy is missing' );
}

++$total_tests;
if ( false !== strpos( $plugin_php, 'Select at least one content type.' ) ) {
	oli_dp_test_pass( 'Empty content-type selection shows an error' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Missing error when no content type is selected' );
}

++$total_tests;
if ( false !== strpos( $plugin_php, 'aria-live' ) && false !== strpos( $plugin_php, 'aria-describedby' ) && false !== strpos( $plugin_php, 'aria-label' ) ) {
	oli_dp_test_pass( 'Settings and duplicate links include required ARIA attributes' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Required ARIA attributes are missing' );
}

++$total_tests;
$distignore = file_exists( $plugin_root . '/.distignore' ) ? file_get_contents( $plugin_root . '/.distignore' ) : '';
$gitattributes = file_exists( $plugin_root . '/.gitattributes' ) ? file_get_contents( $plugin_root . '/.gitattributes' ) : '';
$distignore_needles = array( 'tests/', 'bin/', '.github/', 'phpcs.xml', 'phpunit.xml', 'composer.', 'package', 'node_modules/', '.gitignore', '*.md' );
$distignore_ok = ( '' !== $distignore );
foreach ( $distignore_needles as $needle ) {
	if ( false === strpos( $distignore, $needle ) ) {
		$distignore_ok = false;
		break;
	}
}
if ( $distignore_ok && false !== strpos( $gitattributes, 'export-ignore' ) && false !== strpos( $gitattributes, 'tests' ) ) {
	oli_dp_test_pass( '.distignore and .gitattributes exclude development files' );
	++$passed_tests;
} else {
	oli_dp_test_fail( '.distignore or .gitattributes is missing an required exclusion' );
}

oli_dp_test_section( 'CI/CD Tests' );
++$total_tests;
$workflow_file = $plugin_root . '/.github/workflows/php-checks.yml';
if ( file_exists( $workflow_file ) ) {
	oli_dp_test_pass( 'GitHub Actions workflow exists' );
	++$passed_tests;

	$workflow_content = file_get_contents( $workflow_file );

	++$total_tests;
	if ( false !== strpos( $workflow_content, 'php -l' ) ) {
		oli_dp_test_pass( 'Workflow includes syntax checking' );
		++$passed_tests;
	} else {
		oli_dp_test_fail( 'Workflow missing syntax check' );
	}

	++$total_tests;
	if ( false !== strpos( $workflow_content, 'phpcs' ) ) {
		oli_dp_test_pass( 'Workflow includes PHPCS' );
		++$passed_tests;
	} else {
		oli_dp_test_fail( 'Workflow missing PHPCS' );
	}
} else {
	oli_dp_test_fail( 'GitHub Actions workflow not found' );
}

oli_dp_test_section( 'Documentation Tests' );
++$total_tests;
if ( file_exists( $plugin_root . '/CHANGELOG.md' ) ) {
	oli_dp_test_pass( 'CHANGELOG.md exists' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'CHANGELOG.md not found' );
}

++$total_tests;
if ( file_exists( $plugin_root . '/README.md' ) ) {
	oli_dp_test_pass( 'README.md exists' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'README.md not found' );
}

++$total_tests;
$changelog = file_get_contents( $plugin_root . '/CHANGELOG.md' );
$readme_md = file_get_contents( $plugin_root . '/README.md' );
if ( false === strpos( $changelog . $readme_md, 'github.com/bigrat95' ) && false !== strpos( $changelog . $readme_md, 'github.com/olivierbigras' ) ) {
	oli_dp_test_pass( 'Docs use github.com/olivierbigras URLs' );
	++$passed_tests;
} else {
	oli_dp_test_fail( 'Docs still contain github.com/bigrat95 or missing olivierbigras GitHub URL' );
}

oli_dp_test_section( 'Test Summary' );

$pass_rate = ( $passed_tests / $total_tests ) * 100;

echo "\n";
echo "Total Tests: {$total_tests}\n";
echo "Passed: {$passed_tests}\n";
echo 'Failed: ' . ( $total_tests - $passed_tests ) . "\n";
printf( "Pass Rate: %.1f%%\n", $pass_rate );
echo "\n";

if ( $passed_tests === $total_tests ) {
	echo "\033[1;32m✓ All tests passed!\033[0m\n\n";
	exit( 0 );
}

echo "\033[1;31m✗ Smoke tests failed.\033[0m\n\n";
exit( 1 );
