<?php

interface Grammar
{
	public function parse($text, $Sentence, $workingMemory);
	public function getGrammarRulesForConstituent($constituent);
	public function isWordAPartOfSpeech($word, $partOfSpeech);
	public function isPartOfSpeech($constituent);
	public function getSentenceStructure($syntaxTree);
	public function generate(Sentence $Sentence);
}

?>
