<?php

namespace agentecho\component;

use \agentecho\Settings;
use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Sentence;
use \agentecho\datastructure\SentenceContext;
use \agentecho\datastructure\LabeledDAG;
use \agentecho\grammar\Grammar;

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
//r($DAG);
		$result = $Grammar->getRuleForDAG($antecedent, $DAG);
		if ($result === false) {
			return false;
		}
//r($result);
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

				$word = $Grammar->getWordForFeatures($consequent, $value);
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