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
		$sentence = $this->planPhrase('S', $phraseStructure, $Grammar);
		return $sentence;
	}

	private function removeIds(array &$structure)
	{
		foreach ($structure as $key => &$value) {
echo $key.' ';
			if (preg_match('/id[\d]*/', $key)) {
//die('found');
				unset($structure[$key]);
			} elseif (is_array($value)) {
				$this->removeIds($value);
			}
		}
	}

	private function planPhrase($antecedent, array $phraseStructure, Grammar $Grammar)
	{
		$partialSentence = '';
echo '#'.$antecedent.' ';
		// go through all grammar rules to find a match for the feature set
		foreach ($Grammar->getRulesForFeatures($antecedent, $phraseStructure) as $result) {

			list ($rule, $UnifiedDAG) = $result;

			for ($i = 1; $i < count($rule); $i++) {

				$consequent = $rule[$i];

				if ($Grammar->isPartOfSpeech($consequent)) {
					// generate word
					// find matching entry in lexicon

				} else {
					// generate phrase

					#todo
					$partialStructure = $phraseStructure;

					$partialSentence .= "[$consequent] " . $this->planPhrase($consequent, $partialStructure, $Grammar);
				}

			}

		}

		return $partialSentence;
	}

	public function plan1(array $semantics)
	{
		$tree = array(
			'part-of-speech' => 'S'
		);

		foreach ($semantics as $triple) {
			list ($subject, $predicate, $object) = $triple;
			if ($predicate == 'name') {
				$np = array(
					'part-of-speech' => 'NP',
					'constituents' => array(
						array(
							'part-of-speech' => 'pronoun',
# todo: referring expression
							'word' => $subject
						)
					)
				);
				$vp = array(
					'part-of-speech' => 'VP',
					'constituents' => array(
						array(
							'part-of-speech' => 'verb',
							'word' => 'be'
						),
						array(
							'part-of-speech' => 'NP',
							'constituents' => array(
								array(
									'part-of-speech' => 'propernoun',
									'word' => $object
								)
							)
						)
					)
				);
				$tree['constituents'] = array($np, $vp);
			}
		}
		return $tree;
	}
}