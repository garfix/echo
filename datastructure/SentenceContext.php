<?php

namespace agentecho\datastructure;

use \agentecho\phrasestructure\PhraseStructure;

/**
 * A blackboard for a sentence in progress.
 */
class SentenceContext
{
	/** @var The language of this sentence (english/dutch) */
	private $language = null;

	/** @var Raw textual input of this sentence */
	public $surfaceText = null;

	/** @var An array of lower-case words */
	public $words = null;

	/** @var An array of words or word combinations that form the smallest possible group to receive a part-of-speech
	 * See also: http://en.wikipedia.org/wiki/Lexical_item
	 */
	public $lexicalItems = null;

	/** @var The token that ends the sentence, like . ! ? or SentenceContext::INDIGNATION */
	public $terminator = null;

	/** @var The syntax tree with added features */
	private $phraseSpecification = null;

	/** @var The root object of the object structure. */
	public $RootObject = null;

	public function getRootObject()
	{
		return $this->RootObject;
	}

	public function setPhraseSpecification($specification)
	{
		$this->phraseSpecification = $specification;
	}

	public function getPhraseSpecification()
	{
		return $this->phraseSpecification;
	}

	public function setLanguage($language)
	{
		$this->language = $language;
	}

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

	public function getStructure()
	{
		return $this->phraseSpecification['features']['head']['sentenceType'];
	}

	public function getObjectString()
	{
		return (string)$this->RootObject;
	}
}