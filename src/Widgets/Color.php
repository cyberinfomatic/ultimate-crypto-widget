<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * Colour arithmetic for the widgets' theme.
 *
 * The theme builds every accent shade from one hue and one strength in the OKLCH
 * colour space, so a colour picked as "#4f46e5" has to be turned into those two
 * numbers. The conversion is the published sRGB to OKLab one (Björn Ottosson).
 */
final class Color
{
	/**
	 * @param string $hex "#rrggbb".
	 * @return array{lightness: float, chroma: float, hue: float}|null Lightness 0 to 1, chroma from 0 (grey)
	 *         to about 0.37, hue in degrees. Null when the text is not a colour.
	 */
	public static function hexToOklch(string $hex): ?array
	{
		if (1 !== preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', trim($hex), $parts)) {
			return null;
		}

		[$r, $g, $b] = array_map(
			static function (string $channel): float {
				$value = hexdec($channel) / 255;
				// Undo the sRGB curve: these sums need light as it physically adds up.
				return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
			},
			[$parts[1], $parts[2], $parts[3]]
		);

		$l = (0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b) ** (1 / 3);
		$m = (0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b) ** (1 / 3);
		$s = (0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b) ** (1 / 3);

		$lightness = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
		$a         = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
		$bAxis     = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;

		$chroma = sqrt($a * $a + $bAxis * $bAxis);
		$hue    = fmod(rad2deg(atan2($bAxis, $a)) + 360, 360);

		return [
			'lightness' => round($lightness, 4),
			'chroma'    => round($chroma, 4),
			// A grey has no hue; 0 keeps the result stable.
			'hue'       => $chroma < 0.0005 ? 0.0 : round($hue, 2),
		];
	}
}
