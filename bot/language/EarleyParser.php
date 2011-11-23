<?php

require_once __DIR__ . '/LabeledDAG.php';

/**
 * An implementation of Earley's top-down chart parsing algorithm as described in
 * "Speech and Language Processing" - Daniel Jurafsky & James H. Martin (Prentice Hall, 2000)
 * It is the basic algorithm (p 381) extended with unification (page 431)
 */
class EarleyParser
{
	/**
	 * Parses a sentence (given in an array of words) into a single chart structure
	 * that holds the syntactic structure.
	 *
	 * @param Grammar $Grammar The rules that structure the words.
	 * @param $words array An array of lowercase strings
	 *
	 * @return array Parse trees
	 */
	public function parse(Grammar $Grammar, $words)
	{
		$wordCount = count($words);

		// prepare bookkeeping for extracting trees
		$treeInfo = array(
			'states' => array(),
			'sentences' => array()
		);

		// clear the chart
		$chart = array_fill(0, $wordCount + 1, array());

		// top down parsing starts with queueing the topmost state
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

		$this->enqueue($chart, $words, $initialState, 0, $treeInfo);

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
						$this->predict($Grammar, $chart, $words, $state, $treeInfo);

					} elseif ($i < $wordCount) {

						// no it isn't, it is a low-level part-of-speech like noun, verb or adverb
						// if the current word in the sentence has this part-of-speech, then
						// we add a completed entry to the chart ($part-of-speech => $word)
						$this->scan($Grammar, $chart, $words, $state, $treeInfo);
					}
				} else {

					// proceed all other entries in the chart that have this entry's antecedent as their next consequent
					$this->complete($chart, $words, $state, $treeInfo);
				}
			}
		}

		$parseTrees = $this->extractParseTrees($Grammar, $wordCount, $treeInfo);

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
	protected function predict(Grammar $Grammar, &$chart, $words, $state, &$treeInfo)
	{
		$this->showDebug('predict', $words, $state);

		$rule = $state['rule'];
		$dotPosition = $state['dotPosition'];
		$B = $rule['consequents'][$dotPosition];
		$j = $state['endWordIndex'];

		// go through all rules that have $B as their antecedent
		foreach ($Grammar->getGrammarRulesForConstituent($B) as $newRule) {

			$predictedState = array(
				'rule' => $newRule,
				'dotPosition' => 0,
				'startWordIndex' => $j,
				'endWordIndex' => $j,
				'dag' => self::createLabeledDag($newRule),
			);
			$this->enqueue($chart, $words, $predictedState, $j, $treeInfo);
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
	protected function scan(Grammar $Grammar, &$chart, $words, $state, &$treeInfo)
	{
		$this->showDebug('scan', $words, $state);

		$rule = $state['rule'];
		$B = $rule['consequents'][$state['dotPosition']];
		$j = $state['endWordIndex'];
		$word = $words[$j];

		if ($Grammar->isWordAPartOfSpeech($word, $B)) {

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

			$this->enqueue($chart, $words, $scannedState, $j + 1, $treeInfo);
		}
	}

	/**
	 * This function is called whenever a state is completed.
	 * It's purpose is to advance other states.
	 *
	 * For example:
	 * - this $state is NP -> noun, it has been completed
	 * - now proceed all other states in the chart that are waiting for an NP at the current position
	 */
	protected function complete(&$chart, $words, $completedState, &$treeInfo)
	{
		$this->showDebug('complete', $words, $completedState);

		$j = $completedState['startWordIndex'];
		$k = $completedState['endWordIndex'];
		$B = $completedState['rule']['antecedent'];

		foreach ($chart[$j] as $chartedState) {
			$dotPosition = $chartedState['dotPosition'];
			$rule = $chartedState['rule'];
			$consequents = $rule['consequents'];

			// check if the antecedent of the completed state matches the charted state's consequent at the dot position
			if (($dotPosition >= count($consequents)) || ($consequents[$dotPosition] != $B)) {
				continue;
			}

			$i = $chartedState['startWordIndex'];
#r($state['rule']);
#r(self::createLabeledDag($state['rule']));
			$NewDag = $this->unifyStates(
				$completedState['dag'],
				$chartedState['dag'],
				$B
			);

			if ($NewDag !== false) {

				$advancedState = array(
					'rule' => $rule,
					'dotPosition' => $dotPosition + 1,
					'startWordIndex' => $i,
					'endWordIndex' => $k,
					'dag' => $NewDag
				);

				// store the state's "children" to ease building the parse trees from the packed forest
				$advancedState['children'] = !isset($chartedState['children']) ? array() : $chartedState['children'];
				$advancedState['children'][] = $completedState['id'];

				$this->enqueue($chart, $words, $advancedState, $k, $treeInfo);

				if ($dotPosition + 1 == count($rule['consequents'])) {
					if ($rule['antecedent'] == 'S') {
						$treeInfo['sentences'][] = $advancedState;
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
	protected function enqueue(&$chart, $words, &$state, $position, &$treeInfo)
	{
		static $stateIDs = 0;

		if (!$this->isStateInChart($state, $chart, $position)) {

			$this->showDebug('enqueue', $words, $state);
#todo do the subsuming thing
			$stateIDs++;
			$state['id'] = $stateIDs;
			$treeInfo['states'][$stateIDs] = $state;
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
				$tree[$rule['consequents'][$i]] = $consequent;
			}
		} else {
			$tree = null;
		}

		return new LabeledDAG($tree);
	}

	protected function unifyStates(LabeledDAG $Dag1, LabeledDAG $Dag2, $cat)
	{
		$SubDag1 = $Dag1->followPath($cat);
		$SubDag2 = clone $Dag2;//->followPath($cat2);

//echo "- 1 --------------------\n\n";
//echo($SubDag1);
//echo "- 2 --------------------\n\n";
//echo($SubDag2);
//$UniDag = $SubDag1->unify($SubDag2);
//echo "- uni --------------------\n\n";
//echo($UniDag);
//echo "=====================================\n\n";

		$UniDag = $SubDag1->unify($SubDag2);


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
	 * Since the $chart contains a tangled forest, it requires a special procedure to
	 * separate the trees.
	 *
	 * @return array The parse trees.
	 */
	protected function extractParseTrees(Grammar $Grammar, $wordCount, $treeInfo)
	{
		$parseTrees = array();
		foreach ($treeInfo['sentences'] as $root) {

			// do not accept sentences that are only partial parses
			#note: if these are the only parses available, they may be of use
			if ($root['endWordIndex'] != $wordCount) {
				continue;
			}

			$parseTrees[] = $this->extractParseTreeBranch($Grammar, $root, $treeInfo);
		}
		return $parseTrees;
	}

	protected function extractParseTreeBranch(Grammar $Grammar, $state, $treeInfo)
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
				$constituent = $treeInfo['states'][$constituentId];
				$constituents[] = $this->extractParseTreeBranch($Grammar, $constituent, $treeInfo);
			}
			$branch['constituents'] = $constituents;
		}
		return $branch;
	}

}
