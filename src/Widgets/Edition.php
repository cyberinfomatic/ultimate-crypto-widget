<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * Which widgets this plugin unlocks: all of them, or only the free tier with the rest sold as Pro.
 *
 * The answer lives in one file, edition.json at the plugin's root, which the PHP here, the release
 * build (build.php) and the Pro add-on's build all read, so they can never disagree. Switching is a
 * one-line change to that file and a release: see docs/maintainers/editions.md.
 */
final class Edition
{
	/** Every widget is free and ships in this plugin. */
	public const ALL_FREE = 'all-free';

	/** The Pro-tier widgets are left out of this plugin, listed as Pro, and unlocked by the Pro add-on. */
	public const TIERED = 'tiered';

	/** @var array{edition: string, proTierViews: string[]}|null */
	private static ?array $file = null;

	/** The edition this copy of the plugin was released as. */
	public static function current(): string
	{
		return self::read()['edition'];
	}

	/**
	 * The views (folders under react/widgets) that make up the Pro tier.
	 *
	 * @return string[]
	 */
	public static function proTierViews(): array
	{
		return self::read()['proTierViews'];
	}

	/** Whether this edition sells Pro, so "upgrade" suggestions make sense. */
	public static function sellsPro(?string $edition = null): bool
	{
		return self::TIERED === ($edition ?? self::current());
	}

	/**
	 * @return array{edition: string, proTierViews: string[]}
	 */
	private static function read(): array
	{
		if (null !== self::$file) {
			return self::$file;
		}

		// A missing or broken file means every widget is free: the plugin keeps working, and
		// nothing that was free is ever locked by mistake.
		$raw  = is_readable(self::path()) ? file_get_contents(self::path()) : false;
		$data = is_string($raw) ? json_decode($raw, true) : null;
		$data = is_array($data) ? $data : [];

		$edition = in_array($data['edition'] ?? null, [self::ALL_FREE, self::TIERED], true) ? $data['edition'] : self::ALL_FREE;
		$views   = array_values(array_filter((array) ($data['proTierViews'] ?? []), 'is_string'));

		return self::$file = ['edition' => $edition, 'proTierViews' => $views];
	}

	/** Where edition.json is: the plugin's root folder. */
	public static function path(): string
	{
		return dirname(__DIR__, 2) . '/edition.json';
	}
}
