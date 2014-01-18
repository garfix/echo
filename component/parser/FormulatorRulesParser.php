<?php

namespace agentecho\component\parser;

use agentecho\datastructure\FormulationRules;

/**
 * @author Patrick van Bergen
 */
class FormulatorRulesParser extends SemanticStructureParser
{
	/**
	 * @param array $tokens
	 * @param int $pos
	 * @param $FormulationRules
	 * @return int|null
	 */
	protected function parseMain(array $tokens, $pos, &$FormulationRules)
	{
		$FormulationRules = new FormulationRules();
		$rules = array();

		while ($newPos = $this->parseFormulationRule($tokens, $pos, $Rule)) {
			$pos = $newPos;
			$rules[] = $Rule;
		}

		$FormulationRules->setRules($rules);

		return $pos;
	}
}
