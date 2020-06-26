<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Tokenizes files and detects violations of a defined set of coding standards.
 *
 * Wrapper from PHP_CodeSniffer to PHP, JavaScript and CSS.
 *
 * ## OPTIONS
 *
 * [<files>]
 * : One or more files and/or directories to check
 * ---
 * default: .
 * ---
 *
 * [--standard=<standard>]
 * : The name or path of the coding standard to use
 * ---
 * default: WordPress
 * ---
 *
 * [--extensions=<extensions>]
 * : A comma separated list of file extensions to check
 * The type of the file can be specified using: ext/type
 * e.g., module/php,es/js
 * ---
 * default: php
 * ---
 *
 * @when before_wp_load
 */
$phpcs_command = function( $args = null, $assoc_args = null ) {
	list( $files ) = $args;

	if ( '.' === $files ) {
		$files = rtrim( realpath( getcwd() ), '/' );
	}

	$standard = 'WordPress';
	if ( isset( $assoc_args['standard'] ) ) {
		$standard = $assoc_args['standard'];
	}

	// Path to composer vendor.
	$vendor = dirname( __FILE__ ) . '/vendor';

	// Path to phpcs bin file.
	$binary = $vendor . '/bin/phpcs';

	// Force WPCS in installed path config.
	WP_CLI::launch( "$binary --config-set installed_paths {$vendor}/wp-coding-standards/wpcs" );

	// Get istalled codding standards.
	$output = WP_CLI::launch( "$binary -i", true, true );
	$output = preg_replace( '/^The installed coding standards are /', '', trim( $output->stdout ) );
	$output = preg_replace( '/ and/', ',', $output );

	$standards = explode( ', ', $output );

	// Show error when startard not installed.
	if ( ! in_array( $standard, $standards, true ) ) {
		$joined = '';
		foreach ( $standards as $item ) {
			if ( end( $standards ) === $item ) {
				$joined .= ' and ';
			} elseif ( reset( $standards ) !== $item ) {
				$joined .= ', ';
			}
			$joined .= $item;
		}
		WP_CLI::error(
			sprintf(
				'the "%s" coding standard is not installed. The installed coding standards are %s',
				$standard,
				$joined
			)
		);
		return;
	}

	$extensions = 'php';
	if ( isset( $assoc_args['extensions'] ) ) {
		$extensions = $assoc_args['extensions'];
	}

	// WP_CLI::log( "$binary --standard={$standard} --extensions=$extensions --report=full $files" );
	$output = WP_CLI::launch( "$binary --standard={$standard} --extensions=$extensions --report=json $files", false, true );
	$output = WP_CLI::launch( "$binary --standard={$standard} --extensions=$extensions $files", false, true );
	// $json   = $output->stdout;
	// print_r( json_decode( $json ) );
	WP_CLI::log( $output->stdout );
};
WP_CLI::add_command( 'phpcs', $phpcs_command );
