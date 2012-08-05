<?php

namespace agentecho\component;

class Translations
{
	private static $translations = array(
		'Word not found: %1$s' => array(
			'nl' => 'Woord niet gevonden: %1$s'
		)
	);

	public static function translate($text, $languageCode)
	{
		if (isset(self::$translations[$text][$languageCode])) {
			return self::$translations[$text][$languageCode];
		} elseif ($languageCode != 'en') {
			return $text . ' (' . $languageCode . ')';
		} else {
			return $text;
		}
	}
}