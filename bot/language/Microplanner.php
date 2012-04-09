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
//r($phraseStructure);exit;
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
		$result = $Grammar->getRuleForDAG($antecedent, $DAG);
		if ($result === false) {
			return false;
		}

		list ($rule, $UnifiedDAG) = $result;
//r($UnifiedDAG);
		$partialSentence = '';

		for ($i = 1; $i < count($rule); $i++) {

			$consequent = $rule[$i]['cat'];
echo "$consequent" . "\n";
			if ($Grammar->isPartOfSpeech($consequent)) {

				// find matching entry in lexicon
				$word = $Grammar->getWordForFeatures($consequent, $UnifiedDAG->getPathValue(array($consequent . '@' . $i)));
				if ($word === false) {
					return false;
				}

				$partialSentence .= "[$word]";

			} else {

				// restrict the unified DAG to this consequent
				$ConsequentDAG = $UnifiedDAG->followPath($consequent . '@' . $i)->renameLabel($consequent . '@' . $i, $consequent . '@0');
r($ConsequentDAG);
				#at this point a part of $UnifiedDAG's feature structure should be extracted


				// generate words for phrase
				$phrase = $this->planPhrase($consequent, $ConsequentDAG, $Grammar);
				if ($phrase === false) {
					return false;
				}

				$partialSentence .= ' ' . $phrase;
			}

			echo $partialSentence."\n";
		}

		return $partialSentence;
	}
}