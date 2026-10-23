<?php
/**
 * The quick widget screen: set a widget up without saving it, and take its shortcode.
 *
 * @var \Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\View\Escaper $e
 * @var array<int, array{id: string, label: string, description: string, input: string}> $fields
 * @var string $preview The preview frame.
 *
 * @package UltimateCryptoWidget
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap ucw-admin">
	<header class="ucw-admin__header">
		<h1><?php $e->html( __( 'Quick widget', 'ultimate-crypto-widget' ) ); ?></h1>
	</header>
	<hr class="wp-header-end" />
	<p><?php $e->html( __( 'Set a widget up here and copy its shortcode. Nothing is saved: the shortcode itself carries the settings.', 'ultimate-crypto-widget' ) ); ?></p>

	<div class="ucw-admin__grid">
		<section class="ucw-panel">
			<form class="ucw-editor" data-ucw-editor onsubmit="return false">
				<?php foreach ( $fields as $field ) : ?>
					<p class="wptoolkit-field">
						<label for="<?php $e->attr( $field['id'] ); ?>"><strong><?php $e->html( $field['label'] ); ?></strong></label><br />
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- FieldType::render() escapes everything it outputs.
						echo $field['input'];
						?>
						<?php if ( '' !== $field['description'] ) : ?>
							<br /><span class="description"><?php $e->html( $field['description'] ); ?></span>
						<?php endif; ?>
					</p>
				<?php endforeach; ?>
			</form>
		</section>

		<section class="ucw-panel ucw-panel--wide">
			<h2><?php $e->html( __( 'Preview', 'ultimate-crypto-widget' ) ); ?></h2>
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Preview::frame() escapes.
			echo $preview;
			?>
			<h2><?php $e->html( __( 'Shortcode', 'ultimate-crypto-widget' ) ); ?></h2>
			<p><code class="ucw-shortcode" data-ucw-shortcode data-ucw-copy></code></p>
			<p><button type="button" class="button" data-ucw-copy-from="[data-ucw-shortcode]"><?php $e->html( __( 'Copy', 'ultimate-crypto-widget' ) ); ?></button></p>
		</section>
	</div>
</div>
