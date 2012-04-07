<?php

class Microplanner
{
	/**
	 * Turns an intention (with meaning and speech act) into a syntax tree.
	 *
	 * This process consists of these context tasks (Building Natural Language Generation Systems, p. 49):
	 * - Lexicalisation (choosing words and syntactic constructions)
	 * - Referring expression Generation (what expressions refer to entities)
	 * and this structure task:
	 * - Aggregation (mapping semantic structures to linguistic structures)
	 *
	 * @param array $semantics
	 * @return array A syntax tree
	 */
	public function plan(array $phraseStructure, Grammar $Grammar)
	{
r($phraseStructure);exit;
		$this->removeIds($phraseStructure);

		$FeatureDAG = new LabeledDAG(array(
			"S@0" => $phraseStructure
		));

		$sentence = $this->planPhrase('S', $FeatureDAG, $Grammar);
		return $sentence;
	}

	private function removeIds(array &$structure)
	{
		foreach ($structure as $key => &$value) {
			if (preg_match('/id[\d]*/', $key)) {
				unset($structure[$key]);
			} elseif (is_array($value)) {
				$this->removeIds($value);
			}
		}
	}

	private function planPhrase($antecedent, LabeledDAG $DAG, Grammar $Grammar)
	{
		static $d = 0;

		$d++;

		if ($d == 4) {
			$d--;
			return '';
		}

// dit is niet eerlijk: als eenmaal een regel slaagt, wordt de rest niet meer geprobeerd; en misschien kan de hele zin
// alleen maar slagen als hier een andere regel wordt geprobeerd

		// go through all grammar rules to find a match for the feature set
		foreach ($Grammar->getRulesForDAG($antecedent, $DAG) as $result) {

			list ($rule, $UnifiedDAG) = $result;

			$partialSentence = $this->planPhraseByRule($rule, $UnifiedDAG, $Grammar);
			if ($partialSentence) {
				break;
			}

		}

		echo $partialSentence."\n";

		$d--;

		return $partialSentence ? $partialSentence : false;
	}

	private function planPhraseByRule($rule, $UnifiedDAG, $Grammar)
	{
		$partialSentence = '';

		for ($i = 1; $i < count($rule); $i++) {

			$consequent = $rule[$i]['cat'];
//r($consequent);
			// restrict the unified DAG to this consequent
			$ConsequentDAG = $UnifiedDAG->followPath($consequent . '@' . $i)->renameLabel($consequent . '@' . $i, $consequent . '@0');
			//r($ConsequentDAG);
			if ($Grammar->isPartOfSpeech($consequent)) {

				// generate word
				// find matching entry in lexicon
				$word = $Grammar->getWordForFeatures($consequent, $ConsequentDAG->getPathValue(array($consequent . '@0')));

				if ($word !== false) {

					//$name = $ConsequentDAG->getPathValue(array($consequent . '@0', 'head', 'sem', 'name'));
					//echo '#>' . $consequent;

					$partialSentence .= "[$word]";

				} else {

					if (!in_array($consequent, array('noun', 'propernoun', 'pronoun', 'determiner', 'preposition', 'aux'))) {
						//r("-----\n");
						//	r($consequent);
						//	r($word);
						//r($partialSentence);
						//r("\n-----\n");
						//exit;
					}


					return false;
					$partialSentence = false;
					break;
				}


			} else {
				// generate phrase

				//r($consequent);
				$phrase = $this->planPhrase($consequent, $ConsequentDAG, $Grammar);
				if ($phrase !== false) {
					$partialSentence .= ' ' . $phrase;
				} else {
					$partialSentence = false;
					return false;
					break;
				}


			}

		}
		return $partialSentence;
	}
}