<?php

/**
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Exceptions;

use Throwable;

/**
 * Marker for every exception the library throws, so a consumer can catch ours specifically.
 */
interface WPToolkitException extends Throwable
{
}
