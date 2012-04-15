<?php

/**
 * A complete sentence in a text.
 */
class Sentence
{
	/** @var The language of this sentence (english/dutch) */
	public $language = null;

	/** @var Raw textual input of this sentence */
	public $surfaceText = null;

	/** @var An array of lower-case words */
	public $words = null;

	/** @var An array of words or word combinations that form the smallest possible group to receive a part-of-speech */
	public $lexicalEntries = null;

	/** @var The token that ends the sentence, like . ! ? or Sentence::INDIGNATION */
	public $terminator = null;

	/** @var The syntax tree with added features */
	public $phraseStructure = null;

	/**
	 * Returns a "labeled bracket notation" (see http://ironcreek.net/phpphraseStructure/) of the parse tree.
	 *
	 * @return string
	 */
	public function getSyntaxString()
	{
		return $this->getBranchSyntax($this->phraseStructure);
	}

	private function getBranchSyntax($branch)
	{
		$string = '[' . $branch['part-of-speech'] . ' ';

		if (isset($branch['constituents'])) {
			foreach ($branch['constituents'] as $constituent) {
				$string .= $this->getBranchSyntax($constituent);
			}
		} elseif (isset($branch['word'])) {
			$string .= $branch['word'];
		}

		$string .= ']';

		return $string;
	}

	public function getPhraseStructureString()
	{
		return $this->getPhraseStructureBranch($this->phraseStructure['features']);
	}

	private function getPhraseStructureBranch($phraseStructure)
	{
		$parts = array();
		foreach ($phraseStructure as $key => $val) {
			if ($key == 'id') {
				continue;
			} elseif (is_array($val)) {
				$valString = $this->getPhraseStructureBranch($val);
			} else {
				$valString = $val;
			}

			$parts[] = $key . ': ' . $valString;
		}
		return '[' . implode(', ', $parts) . ']';
	}

	public function getStructure($treeIndex = 0)
	{
		return $this->phraseStructure['features']['head']['sentenceType'];
	}
}