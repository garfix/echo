<?php

require_once __DIR__ . '/LabeledDAG.php';

/**
 * Given an array of lexemes (a sentence) and a grammar, this class provides zero or more syntactic representations.
 *
 * Note: this parser should hold no external references. If it has finished parsing it should be
 * completely discardible.
 *
 */
class EarleyParser
{
	/**
	 * Parses a sentence (given in an array of words) into a single chart structure
	 * that holds the syntactic structure.
	 * Implements the algorithmn found on in chapter 10 of "Speech And Language Processing".
	 *
	 * @param $words array An array of lowercase strings
	 * @param Grammar $Grammar The rules that structure the words.
	 *
	 * @return array Parse trees
	 */
	public function parse(Grammar $Grammar, $words)
	{
		$wordCount = count($words);

		// clear the chart
		$chart = array(
			'states' => array(),
			'sentences' => array()
		);
		for ($i = 0; $i <= $wordCount; $i++) {
			$chart[$i] = array();
		}

		$rule = array(
			'antecedent' => 'gamma',
			'consequents' => array('S')
		);

		$initialState = array(
			'rule' => $rule,
			'dotPosition' => 0,
			'startWordIndex' => 0,
			'endWordIndex' => 0,
			'dag' => self::createLabeledDag($rule),
		);
		$this->enqueue($chart, $words, $initialState, 0);

		// go through all word positions in the sentence
		for ($i = 0; $i <= $wordCount; $i++) {

			// go through all chart entries in this position (entries may be added while we're in the loop)
			for ($j = 0; $j < count($chart[$i]); $j++) {

				// a state is a complete entry in the chart (rule, dotPosition, startWordIndex, endWordIndex)
				$state = $chart[$i][$j];

				// check if the entry is parsed completely
				if ($this->isIncomplete($state)) {

					// fetch the next consequent in the rule of the entry
					$nextCat = $this->getNextCat($state);

					// is this an 'abstract' consequent like NP, VP, PP?
					if (!$Grammar->isPartOfSpeech($nextCat)) {

						// yes it is; add all entries that have this abstract consequent as their antecedent
						$this->predict($Grammar, $chart, $words, $state);

					} elseif ($i < $wordCount) {

						// no it isn't, it is a low-level part-of-speech like noun, verb or adverb
						// if the current word in the sentence has this part-of-speech, then
						// we add a completed entry to the chart ($part-of-speech => $word)
						$this->scan($Grammar, $chart, $words, $state);
					}
				} else {

					// proceed all other entries in the chart that have this entry's antecedent as their next consequent
					$this->complete($Grammar, $chart, $words, $state);
				}
			}
		}

		$parseTrees = $this->extractParseTrees($Grammar, $chart, $wordCount);

		if (ChatbotSettings::$debugParser) r($parseTrees);

		return $parseTrees;
	}

	/**
	 * Adds all entries to the chart that have the current consequent of $state as their antecedent.
	 *
	 * @param Grammar $Grammar
	 * @param array $chart
	 * @param array $words
	 * @param array $state
	 */
	protected function predict(Grammar $Grammar, &$chart, $words, $state)
	{
		$this->showDebug('predict', $words, $state);

		$rule = $state['rule'];
		$dotPosition = $state['dotPosition'];
		$B = $rule['consequents'][$dotPosition];
		$j = $state['endWordIndex'];

		foreach ($Grammar->getGrammarRulesForConstituent($B) as $newRule) {

#r($newRule);
#var_dump(self::createLabeledDag($newRule));
#exit;

			$predictedState = array(
				'rule' => $newRule,
				'dotPosition' => 0,
				'startWordIndex' => $j,
				'endWordIndex' => $j,
# gebruik de constraint van de n-de consequent
//				'constraints' => isset($rule['constraints']) ? $rule['constraints'] : array()
				//'features' => isset($rule['features'][$dotPosition]) ? $rule['features'][$dotPosition] : array()
				'dag' => self::createLabeledDag($newRule),
			);
			$this->enqueue($chart, $words, $predictedState, $j);
		}
	}

	/**
	 * If the current consequent in $state (which non-abstract, like noun, verb, adjunct) is one
	 * of the parts of speech associated with the current $word in the sentence,
	 * then a new, completed, entry is added to the chart: ($part-of-speech => $word)
	 *
	 * @param Grammar $Grammar
	 * @param $chart
	 * @param $words
	 * @param $state
	 */
	protected function scan(Grammar $Grammar, &$chart, $words, $state)
	{
		$this->showDebug('scan', $words, $state);

		$rule = $state['rule'];
		$B = $rule['consequents'][$state['dotPosition']];
		$j = $state['endWordIndex'];
		$word = $words[$j];

		if ($Grammar->isWordAPartOfSpeech($word, $B)) {
#var_dump($Grammar->getLabeledDagForWord($word, $B));
			$scannedState = array(
				'rule' => array(
					'antecedent' => $B,
					'consequents' => array($word)
				),
				'dotPosition' => 1,
				'startWordIndex' => $j,
				'endWordIndex' => $j + 1,
				//'constraints' => $Grammar->getFeatures($word, $B)
				'dag' => $Grammar->getLabeledDagForWord($word, $B),
			);

#Hallo:
#1) De 'features' index moet in de 'rule' komen te staan
#2) De inhoud van de 'features' is de ruwe data, niet de verwerkte DAG of tree


			$this->enqueue($chart, $words, $scannedState, $j + 1);
		}
	}

	/**
	 * This function is called whenever a state is completed.
	 * It's purpose is to complete other states.
	 *
	 * For example:
	 * - this $state is NP -> noun, it has been completed
	 * - now proceed all other states in the chart that are waiting for a NP at the current position
	 *
	 * @param array $chart
	 * @param array $words
	 * @param array $state
	 */
	protected function complete(Grammar $Grammar, &$chart, $words, $state)
	{
		$this->showDebug('complete', $words, $state);

		$j = $state['startWordIndex'];
		$k = $state['endWordIndex'];
		$B = $state['rule']['antecedent'];

		foreach ($chart[$j] as $chartedState) {
			$dotPosition = $chartedState['dotPosition'];
			$rule = $chartedState['rule'];
			$consequents = $rule['consequents'];

			if (($dotPosition >= count($consequents)) || ($consequents[$dotPosition] != $B)) {
				continue;
			}

			$i = $chartedState['startWordIndex'];
#r($state['rule']);
#r(self::createLabeledDag($state['rule']));
			$NewDag = $this->unifyStates(
				$state['dag'],
				$B,
				$chartedState['dag'],
				$B . '@' . $dotPosition
			);

			if ($NewDag !== false) {

				$completedState = array(
					'rule' => $rule,
					'dotPosition' => $dotPosition + 1,
					'startWordIndex' => $i,
					'endWordIndex' => $k,
					'dag' => $NewDag
				);

				// store the state's "children" to ease building the parse trees from the packed forest
				$completedState['children'] = !isset($chartedState['children']) ? array() : $chartedState['children'];
				$completedState['children'][] = $state['id'];

				$this->enqueue($chart, $words, $completedState, $k);

				if ($dotPosition + 1 == count($rule['consequents'])) {
					if ($rule['antecedent'] == 'S') {
						$chart['sentences'][] = $completedState;
					}
				}

			}

		}
	}

	/**
	 * Adds a state to the chart to the right position.
	 * A state that is already present is not entered again.
	 * Meaning is applied to the (completed) state here.
	 *
	 * @param array $chart
	 * @param array $state
	 * @param int $position
	 */
	protected function enqueue(&$chart, $words, &$state, $position)
	{
		static $stateIDs = 0;

		$addState = false;

		if (!$this->isStateInChart($state, $chart, $position)) {
			$addState = true;
		}

		if ($addState) {

			$this->showDebug('enqueue', $words, $state);

			$stateIDs++;
			$state['id'] = $stateIDs;
			$chart['states'][$stateIDs] = $state;
			$chart[$position][] = $state;
		}
	}

	protected function isStateInChart($state, $chart, $position)
	{
		foreach ($chart[$position] as $presentState) {
			if (
				$presentState['rule'] == $state['rule'] &&
				$presentState['dotPosition'] == $state['dotPosition'] &&
				$presentState['startWordIndex'] == $state['startWordIndex'] &&
				$presentState['endWordIndex'] == $state['endWordIndex']) {
					return true;
			}
		}
		return false;
	}

	protected function isIncomplete($state)
	{
		return ($state['dotPosition'] < count($state['rule']['consequents']));
	}

	protected function getNextCat($state)
	{
		return $state['rule']['consequents'][$state['dotPosition']];
	}

	protected static function createLabeledDag($rule)
	{
		if (isset($rule['features'])) {
			$features = $rule['features'];

			$tree = array();
			$tree[$rule['antecedent']] = $features['antecedent'];

			foreach ($features['consequents'] as $i => $consequent) {
				$tree[$rule['consequents'][$i] . '@' . $i] = $consequent;
			}
		} else {
			$tree = null;
		}
//r($tree);
		return new LabeledDAG($tree);
	}

	protected function unifyStates(LabeledDAG $Dag1, $cat1, LabeledDAG $Dag2, $cat2)
	{
		$SubDag1 = $Dag1->followPath($cat1);
		$SubDag2 = $Dag2->followPath($cat2);
#r($Dag2);
#r($cat2);
		// remove the index in the key of $SubDag2
		$keys = $SubDag2->getKeys();
		if (!empty($keys)) {
			$key = reset($keys);
			list($newKey,) = explode('@', $key);
			$SubDag2->replaceKey($key, $newKey);



		}

#r($SubDag2);
#var_dump($cat . $dotPosition);

		$UniDag = $SubDag1->unify($SubDag2);

		if ($newKey == 'pronoun') {
#			var_dump($Dag1);
			echo ($SubDag1);
			var_dump ($SubDag2);
		#	$UniDag = $SubDag1->unify($SubDag2);
			echo '#'.($UniDag).'#';
			echo "=====================================\n\n";
			exit;

		}

		return $UniDag;
	}

	protected function showDebug($function, $words, $state)
	{
		if (ChatbotSettings::$debugParser) {
			$rule = $state['rule'];
			$consequents = $rule['consequents'];
			$dotPosition = $state['dotPosition'];
			$start = $state['startWordIndex'];
			$end = $state['endWordIndex'];

			$post = array_merge(
				array_slice($consequents, 0, $dotPosition),
				array('.'),
				array_slice($consequents, $dotPosition));

			echo
				str_repeat('    ', $start) .
				$function . ' ' .
				$rule['antecedent'] . ' => ' . implode(' ', $post) . ' ' .
				"[" . implode(' ', array_slice($words, $start, ($end - $start))) . "]\n";
		}
	}

	/**
	 * Since the $chart contains a tangled forrest, it requires a special procedure to
	 * separate the trees.
	 *
	 * @param Grammar $Grammar
	 * @param array $chart
	 * @param int $wordCount
	 * @return array The parse trees.
	 */
	protected function extractParseTrees(Grammar $Grammar, $chart, $wordCount)
	{
		$parseTrees = array();
		foreach ($chart['sentences'] as $root) {

			// do not accept sentences that are only partial parses
			#note: if these are the only parses available, they may be of use
			if ($root['endWordIndex'] != $wordCount) {
				continue;
			}

			$parseTrees[] = $this->extractParseTreeBranch($Grammar, $chart, $root);
		}
		return $parseTrees;
	}

	protected function extractParseTreeBranch(Grammar $Grammar, $chart, $state)
	{
		$rule = $state['rule'];
		$branch = array('part-of-speech' => $rule['antecedent']);
		if ($Grammar->isPartOfSpeech($rule['antecedent'])) {
			$branch['word'] = $rule['consequents'][0];
		}
		if (isset($state['children'])) {
			$constituentIds = $state['children'];
			$constituents = array();
			foreach ($constituentIds as $constituentId) {
				$constituent = $chart['states'][$constituentId];
				$constituents[] = $this->extractParseTreeBranch($Grammar, $chart, $constituent);
			}
			$branch['constituents'] = $constituents;
		}
		return $branch;
	}

}

?>