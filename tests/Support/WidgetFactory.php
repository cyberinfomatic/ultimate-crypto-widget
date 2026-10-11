<?php
/**
 * Creates widgets the way the admin screen does.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Support;

/**
 * Saves a ucwp_widget post through the plugin's own save handler, so the rows in
 * the database are the ones the plugin really writes.
 */
final class WidgetFactory {

	/**
	 * What the widget form posts for a typical widget.
	 */
	public const FORM = array(
		'ucwp_widget_count'              => '10',
		'ucwp_widget_orientation'        => 'horizontal',
		'ucwp_widget_speed'              => '3000',
		'ucwp_widget_card_width'         => '157',
		'ucwp_widget_parent_width'       => '400',
		'ucwp_widget_default_currency'   => 'usd',
		'ucwp_widget_no_of_days'         => '7',
		'ucwp_widget_data_interval'      => 'daily',
		'ucwp_widget_search_placeholder' => 'Search Coin',
		'ucwp_widget_coins'              => array( 'bitcoin', 'ethereum' ),
		'ucwp_widget_currencies'         => array( 'usd', 'eur' ),
		'ucwp_widget_dark_mode'          => 'false',
	);

	/**
	 * @param string $type   A widget type slug, e.g. "coin-marquee-1".
	 * @param array  $fields Form fields to override, keyed by full meta key.
	 * @return int Post ID.
	 */
	public static function create( string $type, array $fields = array() ): int {
		$previous = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$_POST = array_merge(
			self::FORM,
			array( 'ucwp_widget_type' => $type ),
			$fields,
			array( 'ucwp_widget_nonce' => wp_create_nonce( basename( UCWP_PLUGIN_FILE ) ) )
		);

		// wp_insert_post fires save_post_ucwp_widget, which runs the plugin's save handler.
		$id = wp_insert_post(
			array(
				'post_type'   => 'ucwp_widget',
				'post_status' => 'publish',
				'post_title'  => 'Widget ' . $type,
			)
		);

		$_POST = $previous;

		return (int) $id;
	}

	/**
	 * The widget's stored meta rows, plugin keys only, in a stable order.
	 *
	 * @return array<string, string[]>
	 */
	public static function rows( int $id ): array {
		$rows = array();
		foreach ( get_post_meta( $id ) as $key => $values ) {
			if ( 0 === strpos( $key, 'ucwp_widget_' ) ) {
				$rows[ $key ] = $values;
			}
		}
		ksort( $rows );
		return $rows;
	}
}
