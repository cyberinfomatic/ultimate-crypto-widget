<?php
/**
 * The plugin's first screen: where to start, and where things are.
 *
 * @var \Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\View\Escaper $e
 * @var string $version
 * @var int    $widgetCount
 * @var string $newUrl
 * @var string $listUrl
 * @var string $quickUrl
 * @var string $settingsUrl
 * @var string $upgradeUrl  Empty when the Pro add-on is licensed.
 * @var string $supportUrl
 * @var string $reviewUrl
 *
 * @package UltimateCryptoWidget
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap ucw-admin">
	<header class="ucw-admin__header">
		<h1><?php $e->html( __( 'Ultimate Crypto Widget', 'ultimate-crypto-widget' ) ); ?></h1>
		<span class="ucw-admin__version"><?php $e->html( $version ); ?></span>
	</header>
	<hr class="wp-header-end" />

	<div class="ucw-admin__grid">
		<section class="ucw-panel ucw-panel--wide">
			<h2><?php $e->html( 0 === $widgetCount ? __( 'Show your first prices in three steps', 'ultimate-crypto-widget' ) : __( 'Your widgets', 'ultimate-crypto-widget' ) ); ?></h2>
			<?php if ( 0 === $widgetCount ) : ?>
				<ol class="ucw-steps">
					<li><?php $e->html( __( 'Create a widget and pick a type: a price table, a scrolling ticker, price cards or a multi-currency list.', 'ultimate-crypto-widget' ) ); ?></li>
					<li><?php $e->html( __( 'Choose its coins, currency and colours. The preview shows the result as you go.', 'ultimate-crypto-widget' ) ); ?></li>
					<li><?php $e->html( __( 'Copy its shortcode into any page, post or widget area.', 'ultimate-crypto-widget' ) ); ?></li>
				</ol>
			<?php else : ?>
				<p>
					<?php
					/* translators: %d: number of published widgets. */
					$e->html( sprintf( _n( 'You have %d published widget.', 'You have %d published widgets.', $widgetCount, 'ultimate-crypto-widget' ), $widgetCount ) );
					?>
				</p>
			<?php endif; ?>
			<p class="ucw-panel__actions">
				<a class="button button-primary" href="<?php $e->url( $newUrl ); ?>"><?php $e->html( __( 'Create a widget', 'ultimate-crypto-widget' ) ); ?></a>
				<?php if ( $widgetCount > 0 ) : ?>
					<a class="button" href="<?php $e->url( $listUrl ); ?>"><?php $e->html( __( 'See all widgets', 'ultimate-crypto-widget' ) ); ?></a>
				<?php endif; ?>
				<a class="button" href="<?php $e->url( $quickUrl ); ?>"><?php $e->html( __( 'Try a quick widget', 'ultimate-crypto-widget' ) ); ?></a>
			</p>
		</section>

		<section class="ucw-panel">
			<h2><?php $e->html( __( 'Price data', 'ultimate-crypto-widget' ) ); ?></h2>
			<p><?php $e->html( __( 'Prices come from CoinGecko and work without an account. A free API key raises the limit for busy sites.', 'ultimate-crypto-widget' ) ); ?></p>
			<p class="ucw-panel__actions"><a class="button" href="<?php $e->url( $settingsUrl ); ?>"><?php $e->html( __( 'Open settings', 'ultimate-crypto-widget' ) ); ?></a></p>
		</section>

		<?php if ( '' !== $upgradeUrl ) : ?>
			<section class="ucw-panel ucw-panel--accent">
				<h2><?php $e->html( __( 'More with Pro', 'ultimate-crypto-widget' ) ); ?></h2>
				<p><?php $e->html( __( 'Price charts, searchable tables, coin details, sliders and more widget designs.', 'ultimate-crypto-widget' ) ); ?></p>
				<p class="ucw-panel__actions"><a class="button button-primary" href="<?php $e->url( $upgradeUrl ); ?>"><?php $e->html( __( 'See Pro', 'ultimate-crypto-widget' ) ); ?></a></p>
			</section>
		<?php endif; ?>

		<section class="ucw-panel">
			<h2><?php $e->html( __( 'Help', 'ultimate-crypto-widget' ) ); ?></h2>
			<ul class="ucw-links">
				<li><a href="<?php $e->url( $supportUrl ); ?>"><?php $e->html( __( 'Ask in the support forum', 'ultimate-crypto-widget' ) ); ?></a></li>
				<li><a href="<?php $e->url( $reviewUrl ); ?>"><?php $e->html( __( 'Leave a review', 'ultimate-crypto-widget' ) ); ?></a></li>
			</ul>
		</section>
	</div>
</div>
