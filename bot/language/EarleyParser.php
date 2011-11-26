<?php

require_once __DIR__ . '/LabeledDAG.php';

/**
 * An implementation of Earley's top-down chart parsing algorithm as described in
 * "Speech and Language Processing" - Daniel Jurafsky & James H. Martin (Prentice Hall, 2000)
 * It is the basic algorithm (p 381) extended with unification (page 431)
 */
class EarleyParser
{
	private $Grammar = null;

	private $chart = array();

	private $words = array();

	private $treeInfo = array(
		'states' => array(),
		'sentences' => array()
	);

	private function __construct($Grammar, $words)
	{
		$this->Grammar = $Grammar;
		$this->words = $words;
	}

	/**
	 * Parses a sentence (given in an array of words) into a single chart structure
	 * that holds the syntactic structure.
	 *
	 * @param Grammar $Grammar The rules that structure the words.
	 * @param $words array An array of lowercase strings
	 *
	 * @return array Parse trees
	 */
	public static function getAllTrees(Grammar $Grammar, $words)
	{
		$Parser = new EarleyParser($Grammar, $words);
		$Parser->parseWords($words);
		$trees = $Parser->extractAllTrees();
		return $trees;
	}

	public static function getFirstTree(Grammar $Grammar, $words)
	{
		$Parser = new EarleyParser($Grammar, $words);
		$Parser->parseWords($words);
		$tree = $Parser->extractFirstTree();
		return $tree;
	}

	private function parseWords()
	{
		$this->initialize();

		$this->doTopDownChartParse();
	}

	private function initialize()
	{
		$this->chart = array_fill(0, count($this->words) + 1, array());

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

		$this->enqueue($initialState, 0);
	}

	/**
	 * Performs Earley's algorithm to turn $this->words into a parse forest.
	 */
	private function doTopDownChartParse()
	{
		// go through all word positions in the sentence
		$wordCount = count($this->words);
		for ($i = 0; $i <= $wordCount; $i++) {

			// go through all chart entries in this position (entries may be added while we're in the loop)
			for ($j = 0; $j < count($this->chart[$i]); $j++) {

				// a state is a complete entry in the chart (rule, dotPosition, startWordIndex, endWordIndex)
				$state = $this->chart[$i][$j];

				// check if the entry is parsed completely
				if ($this->isIncomplete($state)) {

					// fetch the next consequent in the rule of the entry
					$nextCat = $this->getNextCat($state);

					// is this an 'abstract' consequent like NP, VP, PP?
					if (!$this->Grammar->isPartOfSpeech($nextCat)) {

						// yes it is; add all entries that have this abstract consequent as their antecedent
						$this->predict($state);

					} elseif ($i < $wordCount) {

						// no it isn't, it is a low-level part-of-speech like noun, verb or adverb
						// if the current word in the sentence has this part-of-speech, then
						// we add a completed entry to the chart ($part-of-speech => $word)
						$this->scan($state);
					}
				} else {

					// proceed all other entries in the chart that have this entry's antecedent as their next consequent
					$this->complete($state);
				}
			}
		}
	}

	/**
	 * Adds all entries to the chart that have the current consequent of $state as their antecedent.
	 */
	private function predict(array $state)
	{
		$this->showDebug('predict', $state);

		$nextConsequent = $state['rule']['consequents'][$state['dotPosition']];
		$endWordIndex = $state['endWordIndex'];

		// go through all rules that have the next consequent as their antecedent
		foreach ($this->Grammar->getGrammarRulesForConstituent($nextConsequent) as $newRule) {

			$predictedState = array(
				'rule' => $newRule,
				'dotPosition' => 0,
				'startWordIndex' => $endWordIndex,
				'endWordIndex' => $endWordIndex,
				'dag' => self::createLabeledDag($newRule),
			);
			$this->enqueue($predictedState, $endWordIndex);
		}
	}

	/**
	 * If the current consequent in $state (which non-abstract, like noun, verb, adjunct) is one
	 * of the parts of speech associated with the current $word in the sentence,
	 * then a new, completed, entry is added to the chart: ($part-of-speech => $word)
	 */
	private function scan(array $state)
	{
		$this->showDebug('scan', $state);

		$nextConsequent = $state['rule']['consequents'][$state['dotPosition']];
		$endWordIndex = $state['endWordIndex'];
		$endWord = $this->words[$endWordIndex];

		if ($this->Grammar->isWordAPartOfSpeech($endWord, $nextConsequent)) {

			$scannedState = array(
				'rule' => array(
					'antecedent' => $nextConsequent,
					'consequents' => array($endWord)
				),
				'dotPosition' => 1,
				'startWordIndex' => $endWordIndex,
				'endWordIndex' => $endWordIndex + 1,
				'dag' => $this->Grammar->getLabeledDagForWord($endWord, $nextConsequent),
			);

			$this->enqueue($scannedState, $endWordIndex + 1);
		}
	}

	/**
	 * This function is called whenever a state is completed.
	 * Its purpose is to advance other states.
	 *
	 * For example:
	 * - this $state is NP -> noun, it has been completed
	 * - now proceed all other states in the chart that are waiting for an NP at the current position
	 */
	private function complete(array $completedState)
	{
		$this->showDebug('complete', $completedState);

		$completedAntecedent = $completedState['rule']['antecedent'];

		foreach ($this->chart[$completedState['startWordIndex']] as $chartedState) {

			$dotPosition = $chartedState['dotPosition'];
			$rule = $chartedState['rule'];
			$consequents = $rule['consequents'];

			// check if the antecedent of the completed state matches the charted state's consequent at the dot position
			if (($dotPosition >= count($consequents)) || ($consequents[$dotPosition] != $completedAntecedent)) {
				continue;
			}

			$NewDag = $this->unifyStates($completedState['dag'], $chartedState['dag'], $completedAntecedent);
			if ($NewDag !== false) {

				$advancedState = array(
					'rule' => $rule,
					'dotPosition' => $dotPosition + 1,
					'startWordIndex' => $chartedState['startWordIndex'],
					'endWordIndex' => $completedState['endWordIndex'],
					'dag' => $NewDag
				);

				// store extra information to make it easier to extract parse trees later
				$this->storeCompletedStateInfo($completedState, $chartedState, $advancedState);

				$this->enqueue($advancedState, $completedState['endWordIndex']);
			}
		}
	}

	private function storeCompletedStateInfo(array $completedState, array $chartedState, array &$advancedState)
	{
		// store the state's "children" to ease building the parse trees from the packed forest
		$advancedState['children'] = !isset($chartedState['children']) ? array() : $chartedState['children'];
		$advancedState['children'][] = $completedState['id'];

		if ($chartedState['dotPosition'] + 1 == count($chartedState['rule']['consequents'])) {
			if ($chartedState['rule']['antecedent'] == 'S') {
				$this->treeInfo['sentences'][] = $advancedState;
			}
		}
	}

	/**
	 * Adds a state to the chart to the right position.
	 * A state that is already present is not entered again.
	 * Meaning is applied to the (completed) state here.
	 *
	 * @param array $state
	 * @param int $position
	 */
	private function enqueue(array $state, $position)
	{
		static $stateIDs = 0;

		if (!$this->isStateInChart($state, $position)) {

			$this->showDebug('enqueue', $state);
#todo do the subsuming thing
			$stateIDs++;
			$state['id'] = $stateIDs;
			$this->treeInfo['states'][$stateIDs] = $state;
			$this->chart[$position][] = $state;
		}
	}

	private function isStateInChart(array $state, $position)
	{
		foreach ($this->chart[$position] as $presentState) {
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

	private function isIncomplete(array $state)
	{
		return ($state['dotPosition'] < count($state['rule']['consequents']));
	}

	private function getNextCat(array $state)
	{
		return $state['rule']['consequents'][$state['dotPosition']];
	}

	private static function createLabeledDag(array $rule)
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

	private function unifyStates(LabeledDAG $Dag1, LabeledDAG $Dag2, $cat)
	{
		$SubDag1 = $Dag1->followPath($cat);
		$SubDag2 = clone $Dag2;//->followPath($cat2);

		$UniDag = $SubDag1->unify($SubDag2);

		return $UniDag;
	}

	private function showDebug($function, array $state)
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
				"[" . implode(' ', array_slice($this->words, $start, ($end - $start))) . "]\n";
		}
	}

	/**
	 * Since the $chart contains a tangled forest, it requires a special procedure to
	 * separate the trees.
	 *
	 * @return array The parse trees.
	 */
	private function extractAllTrees()
	{
		$parseTrees = array();
		foreach ($this->treeInfo['sentences'] as $root) {

			// do not accept sentences that are only partial parses
			if ($root['endWordIndex'] != count($this->words)) {
				continue;
			}

			$parseTrees[] = $this->extractParseTreeBranch($root);
		}
		return $parseTrees;
	}

	private function extractFirstTree()
	{
		foreach ($this->treeInfo['sentences'] as $root) {

			// do not accept sentences that are only partial parses
			if ($root['endWordIndex'] != count($this->words)) {
				continue;
			}

			$tree = $this->extractParseTreeBranch($root);
			return $tree;
		}
		return null;
	}

	/**
	 * Turns a parse state into a parse tree branch
	 * @return array A branch.
	 */
	private function extractParseTreeBranch(array $state)
	{
		$rule = $state['rule'];

		$branch = array(
			'part-of-speech' => $rule['antecedent']
		);

		if ($this->Grammar->isPartOfSpeech($rule['antecedent'])) {
			$branch['word'] = $rule['consequents'][0];
		}

		if (isset($state['children'])) {

			$constituents = array();
			foreach ($state['children'] as $constituentId) {
				$constituent = $this->treeInfo['states'][$constituentId];
				$constituents[] = $this->extractParseTreeBranch($constituent);
			}
			$branch['constituents'] = $constituents;
		}

		return $branch;
	}

}
