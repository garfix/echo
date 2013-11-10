<?php

namespace agentecho\component;

use agentecho\phrasestructure\PhraseStructure;
use agentecho\phrasestructure\Sentence;
use agentecho\datastructure\SentenceContext;
use agentecho\datastructure\LabeledDAG;
use agentecho\grammar\Grammar;
use agentecho\exception\ProductionException;

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
			$phraseSpecification = array('head' => array('syntax' => $this->buildPhraseStructure($Structure)));
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
		list($lexicalItems, $partsOfSpeech) = $this->plan($SentenceContext->getPhraseSpecification(), $Grammar);
		if (!$lexicalItems) {
			return false;
		}

        $SentenceContext->lexicalItems = $lexicalItems;

# todo: split items into words
$words = $lexicalItems;

        $SentenceContext->words = $words;
		$SentenceContext->partsOfSpeech = $partsOfSpeech;

        $SentenceContext->surfaceText = $this->createSurfaceText($SentenceContext, $Grammar);

		return $SentenceContext->surfaceText;
	}

	private function createSurfaceText(SentenceContext $SentenceContext, Grammar $Grammar)
	{
		$words = $SentenceContext->words;
		$partsOfSpeech = $SentenceContext->partsOfSpeech;

		$words[0] = ucfirst($words[0]);

		$text = '';

		// add all words and precede each one with a space,
		// except the first word, and comma's
		$i = 0;
		foreach ($words as $index => $word) {

			$partOfSpeech = $partsOfSpeech[$i];

			if ($index > 0) {
				$Features = $Grammar->getFeaturesForWord($word, $partOfSpeech);

				$space = $Features->getPathValue(array($partOfSpeech, 'space'));
				$capitalize = $Features->getPathValue(array($partOfSpeech, 'capitalize'));

				if ($space != 'after_only') {
					$text .= ' ';
				}

				if ($capitalize) {
					$word = ucfirst($word);
				}

			}
			$text .= $word;
			$i++;
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
		$structure['type'] = lcfirst(basename(str_replace('\\', '/', get_class($PhraseStructure))));

		foreach ($PhraseStructure->getAttributes() as $name => $value) {
#todo: their should be no need to lowercase
			if ($value instanceof PhraseStructure) {
				$structure[lcfirst($name)] = $this->buildPhraseStructure($value);
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

		if (!empty($phraseSpecification['head']['syntax']['type'])) {
			$constituent = $this->getSyntaxToken($phraseSpecification['head']['syntax']['type']);
		}

		$FeatureDAG = new LabeledDAG(array(
			$constituent => $phraseSpecification
		));

		list($words, $partsOfSpeech) = $this->planPhrase($constituent, $FeatureDAG, $Grammar);

		return array($words, $partsOfSpeech);
	}

	private function planPhrase($antecedent, LabeledDAG $DAG, Grammar $Grammar)
	{
		$result = $this->getRuleForDAG($Grammar, $antecedent, $DAG);
		if ($result === false) {
			return false;
		}

		list ($GenerationRule, $UnifiedDAG) = $result;

		$words = array();
		$partsOfSpeech = array();

		$Production = $GenerationRule->getProduction();

		for ($i = 0; $i < $Production->getConsequentCount(); $i++) {

			$consequent = $Production->getConsequentCategory($i);

			if ($Grammar->isPartOfSpeech($consequent)) {

				// find matching entry in lexicon
				$value = $UnifiedDAG->getPathValue(array($Production->getConsequent($i)));
				if (!$value) {
					$value = array();
				}

				$word = $Grammar->getWordForFeatures($consequent, $value);
				if ($word === false) {
					return false;
				}

				$words[] = $word;
				$partsOfSpeech[] = $consequent;

			} else {

				// restrict the unified DAG to this consequent
				$ConsequentDAG = $UnifiedDAG->followPath($Production->getConsequent($i))->renameLabel($Production->getConsequent($i), $Production->getConsequentCategory($i));

				// generate words for phrase
				$result = $this->planPhrase($consequent, $ConsequentDAG, $Grammar);
				if ($result === false) {
					return false;
				}
				list($phraseWords, $phrasePartsOfSpeech) = $result;

				$words =  array_merge($words, $phraseWords);
				$partsOfSpeech = array_merge($partsOfSpeech, $phrasePartsOfSpeech);
			}

		}

		return array($words, $partsOfSpeech);
	}

	/**
	 * Returns the first rule that has $antecedent and that matches $features.
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
		$generationRules = $Grammar->getGenerationRulesForAntecedent($antecedent);

		if (empty($generationRules)) {
			throw new ProductionException($antecedent);
		}

		foreach ($generationRules as $GenerationRule) {

			#todo: find a way so that we don't need to create a DAG for each rule checked!

			$Production = $GenerationRule->getProduction();
			$antecedent2 = $Production->getAntecedent();
			$antecedentCategory = $Production->getAntecedentCategory();

			$FeatureDAG2 = clone $FeatureDAG;
			$FeatureDAG2->renameLabel($antecedentCategory, $antecedent2);

			if (!$GenerationRule->getCondition()) {
				continue;
			}

			$pattern = $GenerationRule->getCondition()->getRoot();

			if ($FeatureDAG2->match($pattern)) {

				$Dag = $GenerationRule->getFeatures();
				$UnifiedDag = $Dag->unify($FeatureDAG2);

				if ($UnifiedDag) {
					return array($GenerationRule, $UnifiedDag);
				}
			}

		}

		return false;
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