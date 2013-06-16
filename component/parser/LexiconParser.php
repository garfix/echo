<?php

namespace agentecho\component\parser;

use agentecho\datastructure\Lexicon;

/**
 * Parses the contents of a .lexicon file
 *
 * @author Patrick van Bergen
 */
class LexiconParser extends SemanticStructureParser
{
	/**
	 * @param array $tokens
	 * @param int $pos
	 * @param Lexicon $Lexicon
	 * @return int|null
	 */
	protected function parseMain(array $tokens, $pos, &$Lexicon)
	{
		/** @var Lexicon $Lexicon */
		$Lexicon = new Lexicon();

		while ($newPos = $this->parseLexicalEntry($tokens, $pos, $Entry)) {
			$pos = $newPos;
			$Lexicon->addEntry($Entry);
		}

		return $pos;
	}
}
