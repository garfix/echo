<?php
/**
 * Language generation
 *
 * http://aclweb.org/aclwiki/index.php?title=Natural_Language_Generation_Portal
 * http://aclweb.org/aclwiki/index.php?title=Downloadable_NLG_systems
 *
 * Grammar: Context-Free Grammar (CFG) / Phrase-Structure Grammar
 */
require_once(__DIR__ . '/Sentence.php');

/**
 * This module takes care of all language related functions for the agent: parsing and sentence generation.
 * It delegates these tasks to grammars, one per language.
 */
class LanguageProcessor
{
	/** @var Languages for which a Grammar class is available */
	private $availableLanguages = array('english', 'dutch');

	/** @var Start parsing in the language the conversation is in */
	private $currentLanguage = 'english';

	/**
	 * The raw input is parsed into a syntactic / semantic structure.
	 * and these can only be corrected if the most likely grammatical class is known.
	 * The input may be from any of the known languages. While parsing we detect which one.
	 *
	 * @param string $input This input may consist of several sentences, if they are properly separated.
	 * @return array an array of Sentence objects
	 */
	public function parse($input, $workingMemory)
	{
		$sentences = array();

		if (trim($input) == '') {
			return $sentences;
		}

		// create an array of languages in which the current one is in the front
		$languages = array($this->currentLanguage);
		foreach ($this->availableLanguages as $language) {
			if ($language != $this->currentLanguage) {
				$languages[] = $language;
			}
		}

		// try to parse the sentence in each of the available languages
		foreach ($languages as $language) {

			$Sentence = new Sentence();

			if ($this->parseInLanguage($input, $language, $Sentence, $workingMemory)) {

				$sentences[] = $Sentence;
				$Sentence->language = $language;

				// update current language
				$this->currentLanguage = $language;

				// now parse the rest of the input, if there is one
				// this code works either in ltr and rtl languages (not that i tried ;)
				$restInput = str_replace($Sentence->surfaceText, '', $input);

				return array_merge($sentences, $this->parse($restInput, $workingMemory));
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
	 * @param array $workingMemory
	 * @return bool Parse successful?
	 */
	protected function parseInLanguage($input, $language, $Sentence, $workingMemory)
	{
		$success = $this->getGrammar($language)->parse($input, $Sentence, $workingMemory);

		return $success;
	}

	/**
	 * Turns an array of meaning representations into a sentence, in the current language.
	 *
	 * @param array $semantics
	 * @param string A human readable sentence, or false if an error occurred
	 */
	public function generate(array $semantics, $workingMemory)
	{
		$language = $this->currentLanguage;

		return $this->generateInLanguage($semantics, $language, $workingMemory);
	}

	protected function generateInLanguage($semantics, $language, $workingMemory)
	{
		$Interpretation = new SentenceInterpretation();
		$Interpretation->semantics = $semantics;

		$Sentence = new Sentence();
		$Sentence->interpretations[] = $Interpretation;

		return $this->getGrammar($language)->generate($Sentence);
	}

	/**
	 * Returns a Grammar for a given language.
	 *
	 * @param string $language
	 * @return Grammar
	 */
	protected function getGrammar($language)
	{
		static $grammars = array();

		if (!isset($grammars[$language])) {
			$GrammarType = ucfirst($language) . 'Grammar';
			require_once(__DIR__ . '/' . $GrammarType . '.php');
			$grammars[$language] = new $GrammarType();
		}

		return $grammars[$language];
	}
}
