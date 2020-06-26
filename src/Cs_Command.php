<?php

/**
 * Run scripts
 */

namespace Camaleaun;

use WP_CLI;
use WP_CLI\Utils;
use Mustangostang\Spyc;

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
class Cs_Command {

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
	 * [<args>]
	 * : One or more files and/or directories to check
	 * ---
	 * default:
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
	public function __invoke( $args, $assoc_args ) {
		list( $files, $args ) = $args;

		if ( '.' === $files ) {
			$files = rtrim( realpath( getcwd() ), '/' );
		}

		$standard = WP_CLI\Utils\get_flag_value( $assoc_args, 'standard' );

		// Path to composer vendor.
		$vendor = dirname( dirname( __FILE__ ) ) . '/vendor';

		// Path to phpcs bin file.
		$binary = $vendor . '/bin/phpcs';
		WP_CLI::debug( $binary );

		// Force WPCS in installed path config.
		WP_CLI::debug( "$binary --config-set installed_paths {$vendor}/wp-coding-standards/wpcs" );
		$ouput  = WP_CLI::launch( "$binary --config-set installed_paths {$vendor}/wp-coding-standards/wpcs", false, true );
		$stdout = array_filter( preg_split( '/\r\n|\n|\r/', $ouput->stdout ) );
		foreach ( $stdout as $line ) {
			WP_CLI::debug( $line );
		}

		// Get istalled codding standards.
		$output = WP_CLI::launch( "$binary -i", true, true );
		$output = preg_replace( '/^The installed coding standards are /', '', trim( $output->stdout ) );
		$output = preg_replace( '/ and/', ',', $output );

		$standards = explode( ', ', $output );

		$standards_str = '';
		foreach ( $standards as $item ) {
			if ( end( $standards ) === $item ) {
				$standards_str .= ' and ';
			} elseif ( reset( $standards ) !== $item ) {
				$standards_str .= ', ';
			}
			$standards_str .= $item;
		}

		WP_CLI::debug( sprintf( 'The installed coding standards are %s', $standards_str ) );

		// Show error when startard not installed.
		if ( ! in_array( $standard, $standards, true ) ) {
			WP_CLI::error(
				sprintf(
					'the "%s" coding standard is not installed. The installed coding standards are %s',
					$standard,
					$standards_str
				)
			);
			return;
		}

		$extensions = WP_CLI\Utils\get_flag_value( $assoc_args, 'extensions' );

		// WP_CLI::log( "$binary --standard={$standard} --extensions=$extensions --report=full $files" );
		// $output = WP_CLI::launch( "$binary --standard={$standard} --extensions=$extensions --report=json $files", false, true );
		WP_CLI::debug( "$binary $args --standard={$standard} --extensions=$extensions $files" );
		$output = WP_CLI::launch( "$binary $args --standard={$standard} --extensions=$extensions $files", false, true );
		// $json   = $output->stdout;
		// print_r( json_decode( $json ) );
		WP_CLI::log( $output->stdout );
	}
}
