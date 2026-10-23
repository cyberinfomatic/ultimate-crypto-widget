<?php
/**
 * A class belonging to an imaginary add-on, for the class-loading test.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace UcwSampleAddon\Greeting;

final class Hello {

	public static function say(): string {
		return 'hello';
	}
}
