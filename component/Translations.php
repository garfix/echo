<?php

namespace agentecho\component;

class Translations
{
	private static $translations = array(
		'Word not found: %1$s' => array(
			'nl' => 'Woord niet gevonden: %1$s'
		),
		'Generation exception: unknown constituent: %s' => array(
		),
		'Generation exception: could not find a word for the part-of-speech: %s' => array(
		),
		'Could not parse the part that starts with "%s"' => array(
			'nl'=> 'Ik kon het deel niet parsen dat start met "%s"'
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