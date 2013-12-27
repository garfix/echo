<?php

namespace agentecho\datastructure;

/**
 * A datastructure that contains all information that can be directly
 * abstracted from parsing a sentence.
 */
class SentenceInformation
{
	/** @var string The language of this sentence (english/dutch) */
	private $language = null;

	/** @var string Raw textual input of this sentence */
	private $surfaceText = null;

	/** @var string[] An array of lower-case words */
	private $words = null;

	/** @var string[] An array of words or word combinations that form the smallest possible group to receive a part-of-speech
	 * See also: http://en.wikipedia.org/wiki/Lexical_item
	 */
	private $lexicalItems = null;

	/** @var string The token that ends the sentence, like . ! ? or SentenceInformation::INDIGNATION */
	private $terminator = null;

	/** @var array Raw Earley parser data structure */
	private $phraseSpecification = null;

	/** @var RelationList */
	private $Semantics = null;

	/**
	 * @param string[] $words
	 */
	public function setWords(array $words)
	{
		$this->words = $words;
	}

	/**
	 * @return string[]
	 */
	public function getWords()
	{
		return $this->words;
	}

	/**
	 * @param \agentecho\datastructure\The $terminator
	 */
	public function setTerminator($terminator)
	{
		$this->terminator = $terminator;
	}

	/**
	 * @return \agentecho\datastructure\The
	 */
	public function getTerminator()
	{
		return $this->terminator;
	}

	/**
	 * @param \agentecho\datastructure\An $lexicalItems
	 */
	public function setLexicalItems($lexicalItems)
	{
		$this->lexicalItems = $lexicalItems;
	}

	/**
	 * @return \agentecho\datastructure\An
	 */
	public function getLexicalItems()
	{
		return $this->lexicalItems;
	}

	/**
	 * @param $text
	 */
	public function setSurfaceText($text)
	{
		$this->surfaceText = $text;
	}

	/**
	 * @return string
	 */
	public function getSurfaceText()
	{
		return $this->surfaceText;
	}

	/**
	 * @param $specification
	 */
	public function setPhraseSpecification(array $specification)
	{
		$this->phraseSpecification = $specification;
	}

	/**
	 * @return array
	 */
	public function getPhraseSpecification()
	{
		return $this->phraseSpecification;
	}

	/**
	 * @param RelationList $Semantics
	 */
	public function setSemantics($Semantics)
	{
		$this->Semantics = $Semantics;
	}

	/**
	 * @return RelationList
	 */
	public function getSemantics()
	{
		return $this->Semantics;
	}

	/**
	 * @param string $language
	 */
	public function setLanguage($language)
	{
		$this->language = $language;
	}

	/**
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * Returns a "labeled bracket notation" (see http://ironcreek.net/phpphraseSpecification/) of the parse tree.
	 *
	 * @return string
	 */
	public function getSyntaxString()
	{
		return $this->getBranchSyntax($this->phraseSpecification);
	}

	/**
	 * @param $branch
	 * @return string
	 */
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

	/**
	 * @return string A flat representation of the semantics structure
	 */
	public function getSemanticsString()
	{
		return (string)$this->Semantics;
	}
}