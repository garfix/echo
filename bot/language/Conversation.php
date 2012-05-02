<?php

require_once __DIR__ . '/Sentence.php';

class Conversation
{
	/** @var Local memory store for the roles in the conversation */
	private $context = array();

	private $Echo1;

	/** @var Start parsing in the last used grammar */
	private $CurrentGrammar = null;

	public function __construct(Echo1 $Echo1)
	{
		$this->Echo1 = $Echo1;
	}

	public function setCurrentGrammar(Grammar $Grammar)
	{
		$this->CurrentGrammar = $Grammar;
	}

	/**
	 * The raw input is parsed into a syntactic / semantic structure.
	 * and these can only be corrected if the most likely grammatical class is known.
	 * The input may be from any of the known languages. While parsing we detect which one.
	 *
	 * @param string $input This input may consist of several sentences, if they are properly separated.
	 * @return array an array of Sentence objects
	 */
	public function parse($input)
	{
		$sentences = array();
		$availableGrammars = $this->Echo1->getAvailableGrammars();

		if (trim($input) == '') {
			return $sentences;
		}

		if (empty($availableGrammars)) {
			return array();
		}

		// create an array of grammars in which the current one is in the front
		$grammars = array($this->CurrentGrammar);
		foreach ($availableGrammars as $Grammar) {
			if ($Grammar != $this->CurrentGrammar) {
				$grammars[] = $Grammar;
			}
		}

		// try to parse the sentence in each of the available grammars
		foreach ($grammars as $Grammar) {

			$Sentence = new Sentence();

			if ($this->parseInLanguage($input, $Grammar, $Sentence, $this->context)) {

				$sentences[] = $Sentence;
				$Sentence->language = $Grammar->getLanguage();

				// update current language
				$this->CurrentGrammar = $Grammar;

				// now parse the rest of the input, if there is one
				// this code works either in ltr and rtl languages (not that i tried ;)
				$restInput = str_replace($Sentence->surfaceText, '', $input);
				return array_merge($sentences, $this->parse($restInput, $this->context));
			}
		}

		return $sentences;
	}

	/**
	 * Parses a string assuming it is written in $language
	 *
	 * @param string $input
	 * @param string $language
	 * @param Sentence $Sentence
	 * @param array $context
	 * @return bool Parse successful?
	 */
	protected function parseInLanguage($input, Grammar $Grammar, $Sentence, $context)
	{
		$success = $Grammar->parse($input, $Sentence, $context);

		return $success;
	}

	/**
	 * Turns an array of meaning representations into a sentence, in the current language.
	 *
	 * "We can characterize the input to a single invocation of an NLG system as a four-tuple <k, c, u, d>
	 * where k is the KNOWLEDGE SOURCE, c is the COMMUNICATIVE GOAL, u is the USER MODEL, and
	 * d is the DISCOURSE HISTORY" - Building natural language systems (p. 43)
	 *
	 * @param array $semantics - (part of the) COMMUNICATIVE GOAL
	 * @param $context - the DISCOURSE HISTORY
	 * @return string A human readable sentence, or false if an error occurred
	 */
	public function generate(array $phraseSpecification, $context)
	{
		$Grammar = $this->CurrentGrammar;

		return $this->generateInLanguage($phraseSpecification, $Grammar, $context);
	}

	protected function generateInLanguage($phraseSpecification, Grammar $Grammar, $context)
	{
		$Sentence = new Sentence();
		$Sentence->phraseSpecification = $phraseSpecification;

		return $Grammar->generate($Sentence);
	}

	/**
	 * Parses $input into a series of Sentences, but returns only the first of these,
	 *
	 * @param string $input
	 * @return Sentence
	 */
	public function parseFirstLine($input)
	{
		$sentences = $this->parse($input);
		return $sentences ? $sentences[0] : false;
	}

	/**
	 * High-level: reply to the human readable $question with a human readable sentence
	 *
	 * @param string $question
	 * @return string The response
	 */
	public function answer($question)
	{
		$answer = '';

		$Sentence = $this->parseFirstLine($question);
		if ($Sentence) {

			$features = $Sentence->phraseSpecification['features'];

			$id = 0;

			self::addIds($features, $id);


			$head = $features['head'];

			$sem = $head['sem'];

			if (isset($head['sentenceType'])) {
				$sentenceType = $head['sentenceType'];

				// turn the question into an answer
				$features['head']['sentenceType'] = 'declarative';

				if ($sentenceType == 'yes-no-question') {

					// since this is a yes-no question, check the statement
					$result = $this->Echo1->check($sem, $sentenceType);

					if ($result) {
						$answer = 'Yes.';

						if (!$result) {
							$features['head']['negate'] = true;
						}
						$s = $this->generate($features, array());
						if ($s) {
							$answer .= ' ' . $s;
						}

					} else {
						$answer = 'No.';
					}

				} elseif ($sentenceType == 'wh-non-subject-question') {

					$answer = $this->Echo1->answerQuestionAboutObject($sem, $sentenceType);

					// incorporate the answer in the original question
					if ($answer !== false) {

						#todo: this should be made more generic
//r($features);
						if (isset($features['head']['sem']['arg2']['question'])) {
							unset($features['head']['sem']['arg2']['question']);
							$features['head']['sem']['arg2']['determiner'] = $answer;
//r($features);
							$sentence = $this->generate($features, array());
							if ($sentence) {
								$answer = $sentence;
							}
						}

					}

				} else {
					$answer = 'ok.';
				}
			}
		} else {
			$answer = $Sentence->getErrorMessage();
		}

		return $answer;
	}

	private static function addIds(&$structure, &$id)
	{
		if (!isset($structure['id'])) {
			$structure['id'] = ++$id;
		} else {
			foreach ($structure['id'] as $k => $v) {
				$id = $k;
				break;
			}
			$structure['id'] = $id;
		}
		foreach ($structure as &$value) {
			if (is_array(($value))) {
				self::addIds($value, $id);
			}
		}
	}
}