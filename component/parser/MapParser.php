<?php

namespace agentecho\component\parser;

use agentecho\datastructure\Map;

/**
 * Parses the contents of a .map file
 *
 * @author Patrick van Bergen
 */
class MapParser extends SemanticStructureParser
{
	/**
	 * @param array $tokens
	 * @param int $pos
	 * @param Map $Map
	 * @return int|null
	 */
	protected function parseMain(array $tokens, $pos, &$Map)
	{
		/** @var Map $Map */
		$Map = new Map();

		return $this->parseMap($tokens, $pos, $Map);
	}
}
