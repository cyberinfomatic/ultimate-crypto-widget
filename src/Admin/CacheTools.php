<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Admin;

use Cyberinfomatic\UltimateCryptoWidget\Data\PriceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Notices;

/**
 * The "clear cached prices" button on the settings screen.
 */
final class CacheTools
{
	public const ACTION = 'ucwp_clear_api_cache';

	public function __construct(
		private readonly PriceProvider $prices,
		private readonly Notices $notices
	) {
	}

	/**
	 * Hooked to admin_post_ucwp_clear_api_cache.
	 */
	public function handle(): void
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to do this.', 'ultimate-crypto-widget'), '', ['response' => 403]);
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- this is the nonce check.
		$nonce = isset($_POST['security']) ? sanitize_text_field(wp_unslash($_POST['security'])) : '';
		if (!wp_verify_nonce($nonce, self::ACTION)) {
			wp_die(esc_html__('This link has expired. Go back and try again.', 'ultimate-crypto-widget'), '', ['response' => 403]);
		}

		$this->prices->clearCache();
		$this->notices->flash(__('Cached prices cleared.', 'ultimate-crypto-widget'));

		wp_safe_redirect(admin_url('admin.php?page=' . Screens::SETTINGS));
		exit;
	}
}
