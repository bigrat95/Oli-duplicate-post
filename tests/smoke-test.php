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

// Color output helpers.
function test_pass( $message ) {
	echo "\033[0;32m✓\033[0m " . $message . "\n";
}

function test_fail( $message ) {
	echo "\033[0;31m✗\033[0m " . $message . "\n";
	return false;
}

function test_info( $message ) {
	echo "\033[0;34mℹ\033[0m " . $message . "\n";
}

function test_section( $message ) {
	echo "\n\033[1;36m" . $message . "\033[0m\n";
	echo str_repeat( '=', 60 ) . "\n";
}

// Track results.
$total_tests = 0;
$passed_tests = 0;

test_section( 'Oli Duplicate Post - Smoke Tests' );

// Test 1: Check main plugin file exists.
test_section( 'File Structure Tests' );
$total_tests++;
if ( file_exists( __DIR__ . '/../oli-duplicate-post.php' ) ) {
	test_pass( 'Main plugin file exists' );
	$passed_tests++;
} else {
	test_fail( 'Main plugin file not found' );
}

// Test 2: Check required directories exist.
$required_dirs = array( 'includes', 'admin', 'languages', 'tests', '.github/workflows' );
foreach ( $required_dirs as $dir ) {
	$total_tests++;
	if ( is_dir( __DIR__ . '/../' . $dir ) ) {
		test_pass( "Directory exists: {$dir}" );
		$passed_tests++;
	} else {
		test_fail( "Directory missing: {$dir}" );
	}
}

// Test 3: Check required class files exist.
$required_files = array(
	'includes/class-oli-dp-permissions.php',
	'includes/class-oli-dp-settings.php',
	'includes/class-oli-dp-duplicator.php',
	'includes/class-oli-dp-rewrite-republish.php',
	'includes/class-oli-dp-hooks.php',
	'includes/class-oli-dp-admin.php',
	'admin/class-oli-dp-post-list.php',
	'admin/class-oli-dp-bulk-actions.php',
	'admin/class-oli-dp-admin-bar.php',
	'admin/class-oli-dp-meta-box.php',
);

foreach ( $required_files as $file ) {
	$total_tests++;
	if ( file_exists( __DIR__ . '/../' . $file ) ) {
		test_pass( "File exists: {$file}" );
		$passed_tests++;
	} else {
		test_fail( "File missing: {$file}" );
	}
}

// Test 4: Check readme.txt exists and has required headers.
test_section( 'WordPress.org Compliance Tests' );
$total_tests++;
$readme_path = __DIR__ . '/../readme.txt';
if ( file_exists( $readme_path ) ) {
	test_pass( 'readme.txt exists' );
	$passed_tests++;
	
	$readme_content = file_get_contents( $readme_path );
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
		$total_tests++;
		if ( strpos( $readme_content, $header ) !== false ) {
			test_pass( "readme.txt contains: {$header}" );
			$passed_tests++;
		} else {
			test_fail( "readme.txt missing: {$header}" );
		}
	}
} else {
	test_fail( 'readme.txt not found' );
}

// Test 5: Check for proper prefixing in main plugin file.
test_section( 'Code Standards Tests' );
$total_tests++;
$main_file = file_get_contents( __DIR__ . '/../oli-duplicate-post.php' );

// Check for proper prefixes.
$has_oli_dp_prefix = preg_match( '/oli_dp_/i', $main_file );
$has_class_prefix = preg_match( '/class Oli_DP_/i', $main_file );

if ( $has_oli_dp_prefix && $has_class_prefix ) {
	test_pass( 'Proper prefixing used (oli_dp_ and Oli_DP_)' );
	$passed_tests++;
} else {
	test_fail( 'Prefix issues found' );
}

// Test 6: Check for security best practices.
$total_tests++;
$has_abspath_check = preg_match( '/if\s*\(\s*!\s*defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)\s*\)/', $main_file );

if ( $has_abspath_check ) {
	test_pass( 'ABSPATH security check present' );
	$passed_tests++;
} else {
	test_fail( 'ABSPATH security check missing' );
}

// Test 7: Check for text domain.
$total_tests++;
$has_text_domain = preg_match( '/Text Domain:\s*oli-duplicate-post/', $main_file );

if ( $has_text_domain ) {
	test_pass( 'Correct text domain declared' );
	$passed_tests++;
} else {
	test_fail( 'Text domain issue' );
}

// Test 8: Check plugin version consistency.
$total_tests++;
$version_in_main = preg_match( '/Version:\s*([\d.]+)/', $main_file, $main_version );
$version_in_readme = preg_match( '/Stable tag:\s*([\d.]+)/', $readme_content, $readme_version );

if ( $version_in_main && $version_in_readme && $main_version[1] === $readme_version[1] ) {
	test_pass( "Version consistency: {$main_version[1]}" );
	$passed_tests++;
} else {
	test_fail( 'Version mismatch between plugin file and readme.txt' );
}

// Test 9: Syntax check all PHP files.
test_section( 'PHP Syntax Tests' );

$php_files = glob( __DIR__ . '/../*.php' );
$php_files = array_merge( $php_files, glob( __DIR__ . '/../includes/*.php' ) );
$php_files = array_merge( $php_files, glob( __DIR__ . '/../admin/*.php' ) );

foreach ( $php_files as $php_file ) {
	$total_tests++;
	$output = array();
	$return_var = 0;
	exec( 'php -l ' . escapeshellarg( $php_file ) . ' 2>&1', $output, $return_var );
	
	if ( $return_var === 0 ) {
		test_pass( 'Valid syntax: ' . basename( $php_file ) );
		$passed_tests++;
	} else {
		test_fail( 'Syntax error in: ' . basename( $php_file ) );
		echo '  ' . implode( "\n  ", $output ) . "\n";
	}
}

// Test 10: Check for common WordPress function usage.
test_section( 'WordPress Standards Tests' );

$duplicator_file = file_get_contents( __DIR__ . '/../includes/class-oli-dp-duplicator.php' );

$total_tests++;
if ( preg_match( '/wp_insert_post/', $duplicator_file ) ) {
	test_pass( 'Uses wp_insert_post() for post creation' );
	$passed_tests++;
} else {
	test_fail( 'wp_insert_post() not found in duplicator' );
}

$total_tests++;
if ( preg_match( '/sanitize_|esc_/', $duplicator_file ) ) {
	test_pass( 'Uses sanitization/escaping functions' );
	$passed_tests++;
} else {
	test_fail( 'No sanitization functions found' );
}

$settings_file = file_get_contents( __DIR__ . '/../includes/class-oli-dp-settings.php' );

$total_tests++;
if ( preg_match( '/wp_verify_nonce|wp_create_nonce/', $settings_file . $main_file ) ) {
	test_pass( 'Uses nonce verification' );
	$passed_tests++;
} else {
	test_fail( 'Nonce functions not found' );
}

// Test 11: Check GitHub Actions workflow.
test_section( 'CI/CD Tests' );
$total_tests++;
$workflow_file = __DIR__ . '/../.github/workflows/php-checks.yml';
if ( file_exists( $workflow_file ) ) {
	test_pass( 'GitHub Actions workflow exists' );
	$passed_tests++;
	
	$workflow_content = file_get_contents( $workflow_file );
	
	$total_tests++;
	if ( strpos( $workflow_content, 'php -l' ) !== false || strpos( $workflow_content, 'syntax' ) !== false ) {
		test_pass( 'Workflow includes syntax checking' );
		$passed_tests++;
	} else {
		test_fail( 'Workflow missing syntax check' );
	}
	
	$total_tests++;
	if ( strpos( $workflow_content, 'phpcs' ) !== false || strpos( $workflow_content, 'PHPCS' ) !== false ) {
		test_pass( 'Workflow includes PHPCS' );
		$passed_tests++;
	} else {
		test_fail( 'Workflow missing PHPCS' );
	}
} else {
	test_fail( 'GitHub Actions workflow not found' );
}

// Test 12: Check for proper documentation.
test_section( 'Documentation Tests' );
$total_tests++;
if ( file_exists( __DIR__ . '/../CHANGELOG.md' ) ) {
	test_pass( 'CHANGELOG.md exists' );
	$passed_tests++;
} else {
	test_fail( 'CHANGELOG.md not found' );
}

$total_tests++;
if ( file_exists( __DIR__ . '/../README.md' ) ) {
	test_pass( 'README.md exists' );
	$passed_tests++;
} else {
	test_fail( 'README.md not found' );
}

// Final summary.
test_section( 'Test Summary' );

$pass_rate = ( $passed_tests / $total_tests ) * 100;

echo "\n";
echo "Total Tests: {$total_tests}\n";
echo "Passed: {$passed_tests}\n";
echo "Failed: " . ( $total_tests - $passed_tests ) . "\n";
echo sprintf( "Pass Rate: %.1f%%\n", $pass_rate );
echo "\n";

if ( $passed_tests === $total_tests ) {
	echo "\033[1;32m✓ All tests passed!\033[0m\n\n";
	exit( 0 );
} elseif ( $pass_rate >= 80 ) {
	echo "\033[1;33m⚠ Some tests failed, but pass rate is acceptable.\033[0m\n\n";
	exit( 0 );
} else {
	echo "\033[1;31m✗ Too many tests failed.\033[0m\n\n";
	exit( 1 );
}
