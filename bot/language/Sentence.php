<?php

/**
 * A complete sentence in a text. It is written in $language and has one or more interpretations.
 */
class Sentence
{
	/** @var The language of this sentence (english/dutch) */
	public $language = null;

	/** @var Raw textual input of this sentence */
	public $surfaceText = null;

	/** @var An array of lower-case words */
	public $words = null;

	/** @var The token that ends the sentence, like . ! ? or Sentence::INDIGNATION */
	public $terminator = null;

	/** @var Interpretations of this sentence that are created when its syntax is parsed */
	public $interpretations = null;

	/**
	 * Returns a "labeled bracket notation" (see http://ironcreek.net/phpsyntaxtree/) of the parse tree.
	 *
	 * @param int $treeIndex Which of the available trees?
	 * @return string
	 */
	public function getSyntax($treeIndex = 0)
	{
		if (isset($this->interpretations[$treeIndex]->syntaxTree)) {
			return $this->getBranchSyntax($this->interpretations[$treeIndex]->syntaxTree);
		} else {
			return '';
		}
	}

	protected function getBranchSyntax($branch)
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

	public function getSemantics($treeIndex = 0)
	{
		$clauses = array();
		$semanticSet = $this->interpretations[$treeIndex]->semantics;
		foreach ($semanticSet as $subject => $predicates) {
			foreach ($predicates as $predicate => $object) {
				$clauses[] = '[' . $subject . ' ' . $predicate . ' ' . $object . ']';
			}
		}
		return '[' . implode(',', $clauses) . ']';
	}

	public function getStructure($treeIndex = 0)
	{
		return $this->interpretations[$treeIndex]->structure;
	}
}