<?php

namespace agentecho\component;

use agentecho\grammar\DutchGrammar;
use agentecho\grammar\EnglishGrammar;

/**
 * @author Patrick van Bergen
 */
class GrammarFactory
{
	private static $grammars = array();

	public static function getGrammar($language)
	{
		if (!isset(self::$grammars[$language])) {

			switch ($language) {
				case 'en':
					$Grammar = new EnglishGrammar();
					break;
				case 'nl':
					$Grammar = new DutchGrammar();
					break;
				default:
					trigger_error('No grammar for language: ' . $language, E_USER_ERROR);
					$Grammar = null;
					break;
			}

			self::$grammars[$language] = $Grammar;
		}

		return self::$grammars[$language];
	}
}
