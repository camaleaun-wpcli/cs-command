<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Cs scripts.
 *
 * @when before_wp_load
 */
$wpcli_cs_command = dirname( __FILE__ ) . '/src/Cs_Command.php';
if ( file_exists( $wpcli_cs_command ) ) {
	require_once $wpcli_cs_command;
}
WP_CLI::add_command( 'cs', 'Camaleaun\Cs_Command' );
