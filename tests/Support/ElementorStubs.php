<?php
/**
 * The few parts of Elementor our element touches, for tests on a site without it.
 *
 * These follow Elementor's public API (Widget_Base, Controls_Manager) as documented;
 * they prove our side of the contract, not Elementor's.
 *
 * @package Ultimate_Crypto_Widget
 */

// phpcs:disable

namespace Elementor;

if ( ! class_exists( Controls_Manager::class ) ) {
	final class Controls_Manager {
		public const TAB_CONTENT = 'content';
		public const SELECT      = 'select';
		public const SELECT2     = 'select2';
		public const NUMBER      = 'number';
		public const TEXT        = 'text';
	}
}

if ( ! class_exists( Widget_Base::class ) ) {
	abstract class Widget_Base {

		/** @var array<string, array<string, mixed>> */
		public array $controls = array();

		/** @var string[] */
		public array $sections = array();

		/** @var array<string, mixed> */
		public array $settings = array();

		abstract public function get_name();

		abstract protected function register_controls();

		abstract protected function render();

		public function start_controls_section( string $id, array $args = array() ): void {
			$this->sections[] = $id;
		}

		public function end_controls_section(): void {
		}

		public function add_control( string $id, array $args ): void {
			$this->controls[ $id ] = $args;
		}

		public function get_settings_for_display(): array {
			return $this->settings;
		}

		/** What Elementor does when it sets an element up. */
		public function build_controls(): void {
			$this->register_controls();
		}

		/** What Elementor does when it draws an element. */
		public function output(): string {
			ob_start();
			$this->render();
			return (string) ob_get_clean();
		}
	}
}
