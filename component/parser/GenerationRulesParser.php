<?php

namespace agentecho\component\parser;

use agentecho\datastructure\GenerationRules;

/**
 * Parses the contents of a .grammar file.
 *
 * @author Patrick van Bergen
 */
class GenerationRulesParser extends SemanticStructureParser
{
	/**
	 * @param array $tokens
	 * @param int $pos
	 * @param $GenerationRules
	 * @return int|null
	 */
	protected function parseMain(array $tokens, $pos, &$GenerationRules)
	{
		$GenerationRules = new GenerationRules();
		$rules = array();

		while ($newPos = $this->parseGenerationRule($tokens, $pos, $Rule)) {
			$pos = $newPos;
			$rules[] = $Rule;
		}

		$GenerationRules->setRules($rules);

		return $pos;
	}
}
