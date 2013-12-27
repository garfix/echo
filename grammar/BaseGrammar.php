<?php

namespace agentecho\grammar;

use agentecho\component\parser\GenerationRulesParser;
use agentecho\component\parser\LexiconParser;
use agentecho\component\parser\ParseRulesParser;
use agentecho\datastructure\Constant;
use agentecho\datastructure\ParseRules;
use agentecho\datastructure\GenerationRules;
use agentecho\datastructure\Relation;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Property;
use agentecho\datastructure\Atom;
use agentecho\datastructure\Lexicon;

/**
 * This class contains some basic grammar functions.
 */
abstract class BaseGrammar implements Grammar
{
	/** @var ParseRules */
	protected $ParseRules = null;

	/** @var GenerationRules */
	protected $GenerationRules = null;

	/** @var Lexicon $Lexicon */
	protected $Lexicon = null;

	public function __construct()
	{
		$this->Lexicon = new Lexicon();
		$this->ParseRules = new ParseRules();
		$this->GenerationRules = new GenerationRules();
	}

	protected function loadParseGrammar($filePath)
	{
		$text = file_get_contents($filePath);
		$Parser = new ParseRulesParser();
		$Rules = $Parser->parse($text);
		foreach ($Rules->getRules() as $Rule) {
			$this->ParseRules->addRule($Rule);
		}
	}

	protected function loadGenerationGrammar($filePath)
	{
		$text = file_get_contents($filePath);
		$Parser = new GenerationRulesParser();
		$Rules = $Parser->parse($text);
		foreach ($Rules->getRules() as $Rule) {
			$this->GenerationRules->addRule($Rule);
		}
	}

	protected function loadLexicon($filePath)
	{
		$text = file_get_contents($filePath);
		$Parser = new LexiconParser();
		$Lexicon = $Parser->parse($text);
		foreach ($Lexicon->getEntries() as $Entry) {
			$this->Lexicon->addEntry($Entry);
		}
	}

	public function wordExists($word)
	{
		return $this->Lexicon->wordExists($word);
	}

	/**
	 * Returns true if the given syntactic category is a non-abstract part-of-speech.
	 *
	 * @param string $category
	 * @return bool
	 */
	public function isPartOfSpeech($category)
	{
		return in_array($category, array(
			'adjective',
			'adverb',
			'conjunction',
			'copula',
			'degreeAdverb',
			'determiner',
			'noun',
			'pronoun',
			'numeral',
			'verb',
			'propernoun',
			'whAdverb', // adverb with a wh-word function: when, where, how ( http://www.comp.leeds.ac.uk/amalgam/tagsets/upenn.html )
			'whwordNP', // WH-word that may be followed by an NP (e.g. which, what)
			'aux',
			'auxBe', // am, are, is, ...
			'auxDo', // do, does, did, ...
			'auxPsv', // "was" made, "werd" gemaakt
			'preposition',
			'passivisationPreposition',
			'possessiveMarker', // 's (see http://www.comp.leeds.ac.uk/amalgam/tagsets/upenn.html)
			'punctuationMark',
			'insertion'.
			'pastParticipleVerb', // bitten
			'simpleVerb', // bites (present), bit (past)
		));
	}

	/**
	 * Returns true if $word belongs to the $partOfSpeech category.
	 *
	 * @param string $word
	 * @param string $partOfSpeech
	 * @return bool
	 */
	public function isWordAPartOfSpeech($word, $partOfSpeech)
	{
		$result = false;

		if ($this->Lexicon->isWordAPartOfSpeech($word, $partOfSpeech)) {

			$result = true;

		} elseif (is_numeric($word)) {

			if ($partOfSpeech == 'numeral') {

				return true;

			}

		} else {

			// all words can be proper nouns
			if ($partOfSpeech == 'propernoun') {

				// and start with a capital
				if (preg_match('/^[A-Z]/', $word)) {
					$result = true;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns all parse rules with a given  $antecedent
	 * @param $antecedent
	 * @return array[ParseRule]
	 */
	public function getParseRulesForAntecedent($antecedent)
	{
		return $this->ParseRules->getRulesForAntecedent($antecedent);
	}

	/**
	 * Returns all generation rules with a given  $antecedent
	 * @param $antecedent
	 * @return array[GenerationRule]
	 */
	public function getGenerationRulesForAntecedent($antecedent)
	{
		return $this->GenerationRules->getRulesForAntecedent($antecedent);
	}

	/**
	 * Returns the semantics of a word.
	 * @param $word
	 * @param $partOfSpeech
	 * @return RelationList|null
	 */
	public function getSemanticsForWord($word, $partOfSpeech)
	{
		$Entry = $this->Lexicon->getEntry($word, $partOfSpeech);
		if ($Entry) {
			return $Entry->getSemantics();
		} elseif ($partOfSpeech == 'propernoun') {
			return new RelationList();
		} elseif ($partOfSpeech == 'numeral') {
			$List = new RelationList();

			$Prop = new Property();
			$Prop->setName('entity');
			$Prop->setObject(new Atom('this'));

			$Pred = new Relation();
			$Pred->setPredicate('determiner');
			$Pred->setArguments(array($Prop, new Constant($word)));

			$List->setRelations(array($Pred));
			return $List;
		} else {
			return null;
		}
	}

	/**
	 * Returns a word, given its semantics.
	 *
	 * @param string $partOfSpeech
	 * @param RelationList $Semantics
	 * @return mixed An array of [word, partOfSpeech], or false;
	 */
	public function getWordForSemantics($partOfSpeech, RelationList $Semantics)
	{
		$relations = $Semantics->getRelations();

		// check for name(propernoun.entity, "Some Name")
		if ($partOfSpeech == 'propernoun') {
			if (count($relations) == 1) {
				$Relation = reset($relations);
				if ($Relation->getPredicate() == 'name') {
					$name = $Relation->getArgument(1)->getName();
					return [$name, 'propernoun'];
				}
			}
		}

		// check for determiner(determiner.entity, 43)
		if ($partOfSpeech == 'determiner') {
			if (count($relations) == 1) {
				$Relation = reset($relations);
				if ($Relation->getPredicate() == 'determiner') {
					if ($Relation->getArgument(1) instanceof Atom) {
						$name = $Relation->getArgument(1)->getName();
						if (is_numeric($name)) {
							return [$name, 'determiner'];
						}
					}
				}
			}
		}

		return $this->Lexicon->getWordForSemantics($partOfSpeech, $Semantics);
	}
}