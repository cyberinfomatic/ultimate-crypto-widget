<?php

/**
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Http;

use Closure;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\Request;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\Response;

/**
 * One link in the request pipeline (Chain of Responsibility, ADR-0008). Return `$next($request)` to
 * continue, return a Response to stop, or throw an HttpError.
 */
interface Middleware
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response;
}
