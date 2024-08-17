<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
// simple autoloader file to load

spl_autoload_register( function ( $class ) {
	// Replace '\' with directory separator '/'
	$class = str_replace( '\\', DIRECTORY_SEPARATOR, $class );

	if ( ! str_contains( $class, 'Cyberinfomatic' . DIRECTORY_SEPARATOR . 'UltimateCryptoWidget' ) ) {
		return;
	}
	// Specify the directory where your classes are located
	$class_file = __DIR__ . '/../../' . $class . '.php';
	// replace 'Cyberinformatic\UltimateCryptoWidget' with 'src'
	$class_file = str_replace( 'Cyberinfomatic' . DIRECTORY_SEPARATOR . 'UltimateCryptoWidget' . DIRECTORY_SEPARATOR, 'src'.DIRECTORY_SEPARATOR, $class_file );
	// Check if the class file exists
	if ( file_exists( $class_file ) ) {
		// Load the class file
		require_once $class_file;
	}
} );
