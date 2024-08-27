<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Helpers;

class UCWPFileSystem {

	// static method to get file content wp WP_Filesystem
	public static function get_file_content($file_path) {
		global $wp_filesystem;
		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem->get_contents($file_path);
	}

	// delete file using wp_delete_file function
	static function delete_file($file_path): void {
		 wp_delete_file($file_path);
	}

}