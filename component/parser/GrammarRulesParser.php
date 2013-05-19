<?php

namespace agentecho\component\parser;

use agentecho\datastructure\GrammarRules;

/**
 * Parses the contents of a .grammar file.
 *
 * @author Patrick van Bergen
 */
class GrammarRulesParser extends SemanticStructureParser
{
	/**
	 * @param array $tokens
	 * @param int $pos
	 * @param GrammarRules $GrammarRules
	 * @return int|null
	 */
	protected function parseMain(array $tokens, $pos, &$GrammarRules)
	{
		$GrammarRules = new GrammarRules();
		$rules = array();

		while ($newPos = $this->parseGrammarRule($tokens, $pos, $Rule)) {
			$pos = $newPos;
			$rules[] = $Rule;
		}

		$GrammarRules->setRules($rules);

		return $pos;
	}
}
