<?php

namespace agentecho\component;

use \agentecho\grammar\Grammar;
use \agentecho\Settings;
use \agentecho\datastructure\LabeledDAG;

class Microplanner
{
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
		if (!empty($phraseSpecification['type'])) {
			if ($phraseSpecification['type'] == 'conjunction') {

				$constituent = 'CP';
                $phraseSpecification['head']['sem'] = $phraseSpecification;

			}
		}
//r($phraseSpecification);
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
}