<?php

/**
 * A possible interpretation of the sentence
 */
class SentenceInterpretation
{
	/** @var A possible syntax trees that could be parsed from the $words */
	public $syntaxTree = null;

	/** @var A syntax-meaning representation of the syntax tree */
	public $phraseStructure = null;

	/** @var The type of sentence */
	public $structure = null;
}