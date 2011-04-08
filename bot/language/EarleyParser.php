<?php

/**
 * Given an array of lexemes (a sentence), this class provides zero or more syntactic representations.
 *
 * Note: this parser should hold no external references. If it has finished parsing it should be
 * completely discardible.
 *
 */
class EarleyParser
{
	/**
	 * Parses a sentence (given in an array of words) into a single chart structure
	 * that holds both the syntactic and a semantic structure.
	 * Implements the algorithmn found on in chapter 11 of "Speech And Language Processing",
	 * extended with the semantics part in chapter 15.
	 *
	 * @param $words array An array of lowercase strings
	 * @param Grammar $Grammar The rules that structure the words.
	 * @todo This parser does not handle compound lexemes (i.e. "science fiction").
	 *
	 * @return array Parse trees
	 */
	public function parse(Grammar $Grammar, $words)
	{
		// clear the chart
		$wordCount = count($words);
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
			'endWordIndex' => 0
		);
		$this->enqueue($chart, $initialState, 0);

		for ($i = 0; $i <= $wordCount; $i++) {
			for ($j = 0; $j < count($chart[$i]); $j++) {
				$state = $chart[$i][$j];
				if ($this->isIncomplete($state)) {
					$nextCat = $this->getNextCat($state);
					if (!$Grammar->isPartOfSpeech($nextCat)) {
						$this->predict($Grammar, $chart, $state);
					} elseif ($i < $wordCount) {
						$this->scan($Grammar, $chart, $words, $state);
					}
				} else {
					$this->complete($chart, $state);
				}
			}
		}

		$parseTrees = $this->extractParseTrees($Grammar, $chart, $wordCount);

		return $parseTrees;
	}

	protected function predict(Grammar $Grammar, &$chart, $state)
	{
		$rule = $state['rule'];
		$B = $rule['consequents'][$state['dotPosition']];
		$j = $state['endWordIndex'];

		foreach ($Grammar->getGrammarRulesForConstituent($B) as $rule) {
			$predictedState = array(
				'rule' => $rule,
				'dotPosition' => 0,
				'startWordIndex' => $j,
				'endWordIndex' => $j
			);
			$this->enqueue($chart, $predictedState, $j);
		}
	}

	protected function scan(Grammar $Grammar, &$chart, $words, $state)
	{
		$rule = $state['rule'];
		$B = $rule['consequents'][$state['dotPosition']];
		$j = $state['endWordIndex'];
		$word = $words[$j];

		if ($Grammar->isWordAPartOfSpeech($word, $B)) {
			$scannedState = array(
				'rule' => array(
					'antecedent' => $B,
					'consequents' => array($words[$j])
				),
				'dotPosition' => 1,
				'startWordIndex' => $j,
				'endWordIndex' => $j + 1,
			);
			$this->enqueue($chart, $scannedState, $j + 1);
		}
	}

	/**
	 * Increments the dotposition of a state, if it's current antecedent is fullfilled.
	 * The state is not really changes; a new state is queued.
	 *
	 * @param unknown_type $chart
	 * @param unknown_type $state
	 */
	protected function complete(&$chart, $state)
	{
		$j = $state['startWordIndex'];
		$k = $state['endWordIndex'];
		$B = $state['rule']['antecedent'];
		$stateID = $state['id'];

		foreach ($chart[$j] as $chartedState) {
			$dotPosition = $chartedState['dotPosition'];
			$rule = $chartedState['rule'];
			$consequents = $rule['consequents'];
			if (($dotPosition >= count($consequents)) || ($consequents[$dotPosition] != $B)) {
				continue;
			}

			$i = $chartedState['startWordIndex'];
			$rule = $chartedState['rule'];
#print_r($rule);
			$completedState = array(
				'rule' => $rule,
				'dotPosition' => $dotPosition + 1,
				'startWordIndex' => $i,
				'endWordIndex' => $k,
			);

			// store the state's "children" to ease building the parse trees from the packed forest
			$completedState['children'] = !isset($chartedState['children']) ? array() : $chartedState['children'];
			$completedState['children'][] = $state['id'];

			$this->enqueue($chart, $completedState, $k);

			if ($dotPosition + 1 == count($rule['consequents'])) {
				if ($rule['antecedent'] == 'S') {
					$chart['sentences'][] = $completedState;
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
	protected function enqueue(&$chart, &$state, $position)
	{
		static $stateIDs = 0;

		$addState = false;

		if (!$this->isStateInChart($state, $chart, $position)) {
			$addState = true;
		}

		if ($addState) {
			$stateIDs++;
			$state['id'] = $stateIDs;
			$chart['states'][$stateIDs] = $state;
			$chart[$position][] = $state;
		}
	}

	protected function isStateInChart($state, $chart, $position)
	{
		$found = false;
		foreach ($chart[$position] as $presentState) {
			if ($presentState['rule'] == $state['rule']) {
				$found = true;
				# dit is misschien niet genoeg
			}
		}
		return $found;
	}

	protected function isIncomplete($state)
	{
		return ($state['dotPosition'] < count($state['rule']['consequents']));
	}

	protected function getNextCat($state)
	{
		return $state['rule']['consequents'][$state['dotPosition']];
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