<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Data\Http;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Http\HttpClient;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Http\HttpRequest;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Http\HttpResponse;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Exceptions\HttpException;

/**
 * HTTP through WordPress's own transport (wp_remote_request), so a site's proxy
 * settings and SSL configuration apply.
 *
 * This stands in for the toolkit's WpHttpClient (1.0.0-rc.4), which reads response
 * headers by casting WordPress's header object to an array. That yields the
 * object's internals, raises "Array to string conversion" on every real response
 * and loses the headers (Retry-After among them). Remove this class, and its
 * binding in DataProvider, once the bundled toolkit has the fix.
 */
final class WordPressHttpClient implements HttpClient
{
	/**
	 * Follows at most three redirects. Throws only when no answer came at all; an
	 * error status is returned like any other response.
	 */
	public function send(HttpRequest $request): HttpResponse
	{
		$args = [
			'method'      => $request->method,
			'headers'     => $request->sentHeaders(),
			'timeout'     => $request->timeout,
			'redirection' => 3,
		];
		$body = $request->encodedBody();
		if (null !== $body) {
			$args['body'] = $body;
		}

		$result = wp_remote_request($request->url, $args);

		if (is_wp_error($result)) {
			throw HttpException::transport($request, $result->get_error_message());
		}

		return new HttpResponse(
			(int) wp_remote_retrieve_response_code($result),
			$this->headers(wp_remote_retrieve_headers($result)),
			(string) wp_remote_retrieve_body($result)
		);
	}

	/**
	 * WordPress gives the headers as a case-insensitive dictionary object (or a
	 * plain array from some filters). Both can be walked; neither may be cast.
	 *
	 * @param iterable<string, string|string[]>|mixed $headers
	 * @return array<string, string> Lowercase name => value; repeated headers are joined.
	 */
	private function headers($headers): array
	{
		$out = [];
		if (!is_iterable($headers)) {
			return $out;
		}

		foreach ($headers as $name => $value) {
			$out[strtolower((string) $name)] = is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;
		}

		return $out;
	}
}
