<?php

namespace agentecho\component\parser;

use agentecho\datastructure\ParseRules;

/**
 * Parses the contents of a .grammar file.
 *
 * @author Patrick van Bergen
 */
class ParseRulesParser extends SemanticStructureParser
{
	/**
	 * @param array $tokens
	 * @param int $pos
	 * @param ParseRules $ParseRules
	 * @return int|null
	 */
	protected function parseMain(array $tokens, $pos, &$ParseRules)
	{
		$ParseRules = new ParseRules();
		$rules = array();

		while ($newPos = $this->parseParseRule($tokens, $pos, $Rule)) {
			$pos = $newPos;
			$rules[] = $Rule;
		}

		$ParseRules->setRules($rules);

		return $pos;
	}
}
