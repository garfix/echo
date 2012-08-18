<?php

namespace agentecho\component;

use \agentecho\Settings;
use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Sentence;
use \agentecho\datastructure\SentenceContext;
use \agentecho\datastructure\LabeledDAG;
use \agentecho\grammar\Grammar;
use \agentecho\exception\ProductionException;

/**
 * This class creates turns a phrase structure into surface text.
 */
class Producer
{
	/**
	 * Turns an object structure of a phrase or sentence into surface text,
	 *
	 * @param PhraseStructure $Structure
	 * @return string
	 */
	public function produce(PhraseStructure $Structure, Grammar $Grammar)
	{
		if ($Structure instanceof Sentence) {
			$phraseSpecification = array('head' => $this->buildPhraseStructure($Structure));
		} else {
			$phraseSpecification = array('head' => array('sem' => $this->buildPhraseStructure($Structure)));
		}

        $SentenceContext = new SentenceContext();
		$SentenceContext->setPhraseSpecification($phraseSpecification);
        $SentenceContext->RootObject = $Structure;

        return $this->generate($SentenceContext, $Grammar);
	}

	/**
	 * This function turns structured meaning into a line of text.
	 *
	 * @param SentenceContext $Sentence A sentence that contains a speech act, and meaning.
	 * @return string|false Either a sentence in natural language, or false, in case of failure
	 */
	public function generate(SentenceContext $SentenceContext, Grammar $Grammar)
	{
		// turn the intention of the sentence into a syntactic structure
		$lexicalItems = $this->plan($SentenceContext->getPhraseSpecification(), $Grammar);
		if (!$lexicalItems) {
			return false;
		}

        $SentenceContext->lexicalItems = $lexicalItems;

# todo: split items into words
$words = $lexicalItems;

        $SentenceContext->words = $words;

        $SentenceContext->surfaceText = $this->createSurfaceText($SentenceContext);

		return $SentenceContext->surfaceText;
	}

	private function createSurfaceText(SentenceContext $SentenceContext)
	{
		$words = $SentenceContext->words;

		$words[0] = ucfirst($words[0]);

		$text = '';

		// add all words and preceed each one with a space,
		// except the first word, and comma's
		foreach ($words as $index => $word) {
			if ($index > 0) {
				if ($word != ',') {
					$text .= ' ';
				}
			}
			$text .= $word;
		}

        if ($SentenceContext->getRootObject() instanceof Sentence) {
            $text .= '.';
        }

        return $text;
	}

	/**
	 * Turns a phrase object structure into an array structure.
	 *
	 * @param PhraseStructure $PhraseStructure
	 * @return array
	 */
	private function buildPhraseStructure(PhraseStructure $PhraseStructure)
	{
		$structure = array();
		$structure['type'] = strtolower(basename(str_replace('\\', '/', get_class($PhraseStructure))));

		foreach ($PhraseStructure->getAttributes() as $name => $value) {
			if ($value instanceof PhraseStructure) {
				$structure[strtolower($name)] = $this->buildPhraseStructure($value);
			} else {
				$structure[$name] = $value;
			}
		}

		return $structure;
	}
	/**
	 * Turns phrase specification into a surface representation.
	 *
	 * This process consists of these context tasks (Building Natural Language Generation Systems, p. 49):
	 * - Lexicalisation (choosing words and syntactic constructions)
	 * - Referring expression Generation (what expressions refer to entities)
	 * and this structure task:
	 * - Aggregation (mapping semantic structures to linguistic structures)
	 *
	 * @return array|bool An array of words, or false.
	 */
	public function plan(array $phraseSpecification, Grammar $Grammar)
	{
		$constituent = 'S';

#todo: algemener maken

		if (!empty($phraseSpecification['head']['sem']['type'])) {
			$constituent = $this->getSyntaxToken($phraseSpecification['head']['sem']['type']);
		}

		$FeatureDAG = new LabeledDAG(array(
			$constituent . "@0" => $phraseSpecification
		));

		$words = $this->planPhrase($constituent, $FeatureDAG, $Grammar);

		return $words;
	}

	private function planPhrase($antecedent, LabeledDAG $DAG, Grammar $Grammar)
	{
//r($DAG);exit;
		$result = $this->getRuleForDAG($Grammar, $antecedent, $DAG);
		if ($result === false) {
			return false;
		}
//r($result);exit;
		list ($rule, $UnifiedDAG) = $result;
		$words = array();

		for ($i = 1; $i < count($rule); $i++) {

			$consequent = $rule[$i]['cat'];

			if (Settings::$debugGenerator) {
				echo $consequent . "\n";
			}

			if ($Grammar->isPartOfSpeech($consequent)) {

				// find matching entry in lexicon
				$value = $UnifiedDAG->getPathValue(array($consequent . '@' . $i));
				if (!$value) {
					$value = array();
				}

				$word = $this->getWordForFeatures($Grammar, $consequent, $value);
				if ($word === false) {
					return false;
				}

				$words[] = $word;

			} else {

				// restrict the unified DAG to this consequent
				$ConsequentDAG = $UnifiedDAG->followPath($consequent . '@' . $i)->renameLabel($consequent . '@' . $i, $consequent . '@0');

				// generate words for phrase
				$phrase = $this->planPhrase($consequent, $ConsequentDAG, $Grammar);
				if ($phrase === false) {
					return false;
				}

				$words =  array_merge($words, $phrase);
			}

		}

		return $words;
	}

	/**
	 * @param $partOfSpeech
	 * @param array $features
	 * @return bool|int|string
	 * @throws \agentecho\exception\ProductionException
	 */
	public function getWordForFeatures(Grammar $Grammar, $partOfSpeech, array $features)
	{
		$word = false;

		if ($partOfSpeech == 'propernoun') {
			if (isset($features['head']['sem']['name'])) {
				$word = $features['head']['sem']['name'];
			}
		} elseif ($partOfSpeech == 'determiner') {
			if (is_numeric($features['head']['sem']['determiner']['category'])) {
				$word = $features['head']['sem']['determiner']['category'];
			} else {
				$word = $this->getWord($Grammar, $partOfSpeech, $features);
			}
		} else {
			$word = $this->getWord($Grammar, $partOfSpeech, $features);
			if (!$word) {
				$E = new ProductionException(ProductionException::TYPE_WORD_NOT_FOUND_FOR_PARTOFSPEECH);
				$E->setValue($partOfSpeech);
				throw $E;
			}
		}

		return $word;
	}

	/*
	 * TODO: SLOW IMPLEMENTATION
	 */
	private function getWord(Grammar $Grammar, $partOfSpeech, $features)
	{
		$lexicon = $Grammar->getLexicon();

		$predicate = isset($features['head']['sem']['predicate']) ? $features['head']['sem']['predicate'] : null;
		$tense = isset($features['head']['sem']['tense']) ? $features['head']['sem']['tense'] : null;
		$determiner = isset($features['head']['sem']['determiner']) ? $features['head']['sem']['determiner'] : null;
		$category = isset($features['head']['sem']['category']) ? $features['head']['sem']['category'] : null;
		$isa = isset($features['head']['sem']['category']) ? $features['head']['sem']['category'] : null;

		foreach ($lexicon as $word => $data) {

			// check if the word belongs to this part of speech
			if (!isset($data[$partOfSpeech])) {
				continue;
			}

			if ($isa) {
				if (!isset($data[$partOfSpeech]['features']['head']['sem']['category'])) {
					continue;
				}
				if ($data[$partOfSpeech]['features']['head']['sem']['category'] != $isa) {
					continue;
				}
			}

			if ($predicate) {
				if (!isset($data[$partOfSpeech]['features']['head']['sem']['predicate'])) {
					continue;
				}
				if ($data[$partOfSpeech]['features']['head']['sem']['predicate'] != $predicate) {
					continue;
				}
			}

			if ($tense) {
				if (!isset($data[$partOfSpeech]['features']['head']['sem']['tense'])) {
					continue;
				}
				if ($data[$partOfSpeech]['features']['head']['sem']['tense'] != $tense) {
					continue;
				}
			}

			if ($determiner) {
				if (!isset($data[$partOfSpeech]['features']['head']['sem']['category'])) {
					continue;
				}
				if ($data[$partOfSpeech]['features']['head']['sem']['category'] != $determiner['category']) {
					continue;
				}
			}

			if ($category) {
				if (!isset($data[$partOfSpeech]['features']['head']['sem']['category'])) {
					continue;
				}
				if ($data[$partOfSpeech]['features']['head']['sem']['category'] != $category) {
					continue;
				}
			}

			return $word;

		}

		return false;
	}

	/**
	 * Returns the first rule that have $antecedent and that match $features.
	 *
	 * Actually it returns an array of two components:
	 * 1) the 'rule' part of a generation rule
	 * 2) a unification of $DAG and the DAG created by the 'rule' part of the generation rule
	 *
	 * @param $antecedent
	 * @param LabeledDAG $DAG
	 * @return bool|array
	 */
	private function getRuleForDAG(Grammar $Grammar, $antecedent, LabeledDAG $FeatureDAG)
	{
		$generationRules = $Grammar->getGenerationRules();

		if (!isset($generationRules[$antecedent])) {
			$E = new ProductionException(ProductionException::TYPE_UNKNOWN_CONSTITUENT);
			$E->setValue($antecedent);
			throw $E;
		}
//r($FeatureDAG);
		foreach ($generationRules[$antecedent] as $generationRule) {

			$pattern = array($antecedent . '@0' => $generationRule['condition']);
//r($pattern);
			if ($FeatureDAG->match($pattern)) {
//echo 'qq';
//r($pattern);
				$rawRule = $generationRule['rule'];
				$Dag = self::createLabeledDag($rawRule);
//r($Dag);
				$UnifiedDag = $Dag->unify($FeatureDAG);
//r($UnifiedDag);
				if ($UnifiedDag) {
					return array($rawRule, $UnifiedDag);
				}
			}
		}
//exit;
		return false;
	}

	private static function createLabeledDag(array $rule)
	{
		$tree = array();
		foreach ($rule as $index => $line) {
			if (isset($line['features'])) {
				$tree[$line['cat'] . '@' . $index] = $line['features'];
			}
		}

		return new LabeledDAG($tree);
	}

	private function getSyntaxToken($phraseStructureClass)
	{
		if ($phraseStructureClass == 'conjunction') {
			return 'CP';
		} else {
			return 'S';
		}
#todo extend
	}
}