<?php

namespace agentecho\datastructure;

use \agentecho\component\Conversation;

/**
 * A complete sentence in a text.
 */
class Sentence
{
	/** @var The Conversation in which this sentence took place */
	public $Conversation = null;

	/** @var The language of this sentence (english/dutch) */
	public $language = null;

	/** @var Raw textual input of this sentence */
	public $surfaceText = null;

	/** @var An array of lower-case words */
	public $words = null;

	/** @var An array of words or word combinations that form the smallest possible group to receive a part-of-speech
	 * See also: http://en.wikipedia.org/wiki/Lexical_item
	 */
	public $lexicalItems = null;

	/** @var The token that ends the sentence, like . ! ? or Sentence::INDIGNATION */
	public $terminator = null;

	/** @var The syntax tree with added features */
	public $phraseSpecification = null;

	/** @var Describes what went wrong while processing this sentence */
	public $errorMessage = null;

	public function __construct(Conversation $Conversation)
	{
		$this->Conversation = $Conversation;
	}

	public function getConversation()
	{
		return $this->Conversation;
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

	public function getPhraseSpecificationString()
	{
		return $this->getPhraseSpecificationBranch($this->phraseSpecification['features']);
	}

	private function getPhraseSpecificationBranch($phraseSpecification)
	{
		$parts = array();
		foreach ($phraseSpecification as $key => $val) {
			if ($key == 'id') {
				continue;
			} elseif (is_array($val)) {
				$valString = $this->getPhraseSpecificationBranch($val);
			} else {
				$valString = $val;
			}

			$parts[] = $key . ': ' . $valString;
		}
		return '[' . implode(', ', $parts) . ']';
	}

	public function getStructure($treeIndex = 0)
	{
		return $this->phraseSpecification['features']['head']['sentenceType'];
	}

	public function getErrorMessage()
	{
		return $this->errorMessage;
	}
}