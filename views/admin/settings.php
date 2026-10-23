<?php
/**
 * The settings screen.
 *
 * @var \Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\View\Escaper $e
 * @var \Closure $form       Prints the settings form.
 * @var int      $callCount  Requests made to CoinGecko in the current period.
 * @var string   $clearUrl
 * @var string   $clearNonce
 *
 * @package UltimateCryptoWidget
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap ucw-admin">
	<header class="ucw-admin__header">
		<h1><?php $e->html( __( 'Settings', 'ultimate-crypto-widget' ) ); ?></h1>
	</header>
	<hr class="wp-header-end" />

	<?php settings_errors(); ?>

	<div class="ucw-admin__grid">
		<section class="ucw-panel ucw-panel--wide">
			<?php $form(); ?>
		</section>

		<section class="ucw-panel">
			<h2><?php $e->html( __( 'Cached prices', 'ultimate-crypto-widget' ) ); ?></h2>
			<p>
				<?php
				/* translators: %d: number of requests. */
				$e->html( sprintf( _n( '%d request to CoinGecko in the last 30 days.', '%d requests to CoinGecko in the last 30 days.', $callCount, 'ultimate-crypto-widget' ), $callCount ) );
				?>
			</p>
			<p><?php $e->html( __( 'Prices are kept for the time set here, then fetched again. Clear them to fetch fresh prices now.', 'ultimate-crypto-widget' ) ); ?></p>
			<form method="post" action="<?php $e->url( $clearUrl ); ?>">
				<input type="hidden" name="security" value="<?php $e->attr( $clearNonce ); ?>" />
				<button type="submit" class="button"><?php $e->html( __( 'Clear cached prices', 'ultimate-crypto-widget' ) ); ?></button>
			</form>
		</section>
	</div>
</div>
