<?php

namespace agentecho\component;

use \agentecho\grammar\Grammar;
use \agentecho\datastructure\LabeledDAG;
use \agentecho\Settings;

/**
 * An implementation of Earley's top-down chart parsing algorithm as described in
 * "Speech and Language Processing" (first edition) - Daniel Jurafsky & James H. Martin (Prentice Hall, 2000)
 * It is the basic algorithm (p 381) extended with unification (p 431) and semantics (p 570)
 */
class EarleyParser
{
	const ANTECEDENT = 0;
	const CONSEQUENT = 1;

	/** @var Grammar Contains call-backs for all language-specific information */
	private $Grammar = null;

	/** @var array Per word, all states that need to be processed */
	private $chart = array();

	/** @var array Words to be parsed */
	private $words = array();

	/** @var Stop parsing when first tree is found? */
	private $singleTree = false;

	/** @var array A structure to help splitting the parse forest into trees */
	private $treeInfo = array(
		'states' => array(),
		'sentences' => array()
	);

	/** @var A message that is created for the user when something goes wrong */
	private $errorMessage = null;

	/** @var The index of the last successfully parsed index */
	private $lastParsedIndex = null;

	private function __construct(Grammar $Grammar, array $words, $singleTree)
	{
		$this->Grammar = $Grammar;
		$this->words = $words;
		$this->singleTree = $singleTree;
	}

	public function getErrorMessage()
	{
		return $this->errorMessage;
	}

	public function getLastParsedIndex()
	{
		return $this->lastParsedIndex;
	}

	/**
	 * Returns all trees that can be parsed from $words, given a $Grammar.
	 *
	 * @param Grammar $Grammar The rules that structure the words.
	 * @param $words array An array of lowercase strings
	 *
	 * @return array Parse trees
	 */
	public static function getAllTrees(Grammar $Grammar, array $words)
	{
		$Parser = new EarleyParser($Grammar, $words, false);
		$Parser->parseWords($words);
		$trees = $Parser->extractAllTrees();
		return $trees;
	}

	/**
	 * Returns the first tree that can be parsed from $words, given a $Grammar.
	 *
	 * @return array A structure:
	 * - success: boolean
	 * - tree: either a tree, or null
	 * - errorMessages: either null or a message
	 */
	public static function getFirstTree(Grammar $Grammar, array $words)
	{
		$Parser = new EarleyParser($Grammar, $words, true);
		$Parser->parseWords($words);

		$tree = $Parser->extractFirstTree();

		return array(
			'success' => $tree !== null,
			'tree' => $tree,
			'errorMessage' => $Parser->getErrorMessage(),
			'lastParsedIndex' => $Parser->getLastParsedIndex()
		);
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
			array('cat' => 'gamma'),
			array('cat' => 'S')
		);

		$initialState = array(
			'rule' => $rule,
			'dotPosition' => self::CONSEQUENT,
			'startWordIndex' => 0,
			'endWordIndex' => 0,
			'dag' => self::createLabeledDag($rule),
			'semantics' => null,
		);

		$this->enqueue($initialState, 0);
	}

	/**
	 * Performs Earley's algorithm to turn $this->words into a packed forest.
	 */
	private function doTopDownChartParse()
	{
		// go through all word positions in the sentence
		$wordCount = count($this->words);
		for ($i = 0; $i <= $wordCount; $i++) {

			// go through all chart entries in this position (entries will be added while we're in the loop)
			for ($j = 0; $j < count($this->chart[$i]); $j++) {

				// a state is a complete entry in the chart (rule, dotPosition, startWordIndex, endWordIndex, dag)
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

					$this->lastParsedIndex = $i;

					// proceed all other entries in the chart that have this entry's antecedent as their next consequent
					$treeComplete = $this->complete($state);

					if ($this->singleTree && $treeComplete) {
						return;
					}
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

		$nextConsequent = $state['rule'][$state['dotPosition']]['cat'];
		$endWordIndex = $state['endWordIndex'];

		// go through all rules that have the next consequent as their antecedent
		foreach ($this->getRulesForAntecedent($nextConsequent) as $newRule) {

# correct? probably not!
if (isset($newRule[0]['semantics'])) {
//	$Semantics = $this->createSemanticStructure($newRule[0]['semantics']);
	$Semantics = null;
} else {
	$Semantics =  null;
}

			$predictedState = array(
				'rule' => $newRule,
				'dotPosition' => self::CONSEQUENT,
				'startWordIndex' => $endWordIndex,
				'endWordIndex' => $endWordIndex,
				'dag' => self::createLabeledDag($newRule),
				'semantics' => $Semantics,
			);
			$this->enqueue($predictedState, $endWordIndex);
		}
	}

	public function getRulesForAntecedent($antecedent)
	{
		$parseRules = $this->Grammar->getParseRules();
		if (isset($parseRules[$antecedent])) {
			return $parseRules[$antecedent];
		} else {
			return array();
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

		$nextConsequent = $state['rule'][$state['dotPosition']]['cat'];
		$endWordIndex = $state['endWordIndex'];
		$endWord = $this->words[$endWordIndex];

		if ($this->Grammar->isWordAPartOfSpeech($endWord, $nextConsequent)) {

			$features = $this->Grammar->getFeaturesForWord($endWord, $nextConsequent);
			$DAG = new LabeledDAG(array($nextConsequent . '@' . '0' => $features));
			$Semantics = $this->createSemanticStructure($this->Grammar->getSemanticsForWord($endWord, $nextConsequent));

if ($Semantics === null) {
	$a = 0;
}

			$scannedState = array(
				'rule' => array(
					array('cat' => $nextConsequent),
					array('cat' => $endWord)
				),
				'dotPosition' => 2,
				'startWordIndex' => $endWordIndex,
				'endWordIndex' => $endWordIndex + 1,
				'dag' => $DAG,
				'semantics' => $Semantics,
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
	 *
	 * @return bool Tree complete?
	 */
	private function complete(array $completedState)
	{
		$this->showDebug('complete', $completedState);

		$treeComplete = false;
		$completedAntecedent = $completedState['rule'][self::ANTECEDENT]['cat'];

		foreach ($this->chart[$completedState['startWordIndex']] as $chartedState) {

			$dotPosition = $chartedState['dotPosition'];
			$rule = $chartedState['rule'];

			// check if the antecedent of the completed state matches the charted state's consequent at the dot position
			if (($dotPosition >= count($rule)) || ($rule[$dotPosition]['cat'] != $completedAntecedent)) {
				continue;
			}

			$completedAntecedentLabel = $completedAntecedent . '@' . '0';
			$chartedConsequentLabel = $rule[$dotPosition]['cat'] . '@' . $dotPosition;

			$NewDag = $this->unifyStates($completedState['dag'], $chartedState['dag'], $completedAntecedentLabel, $chartedConsequentLabel);
			if ($NewDag !== false) {

				$advancedState = array(
					'rule' => $rule,
					'dotPosition' => $dotPosition + 1,
					'startWordIndex' => $chartedState['startWordIndex'],
					'endWordIndex' => $completedState['endWordIndex'],
					'dag' => $NewDag,
					'semantics' => null
				);

				// store extra information to make it easier to extract parse trees later
				$treeComplete = $this->storeStateInfo($completedState, $chartedState, $advancedState);

				if ($treeComplete) {
					break;
				}

				$this->enqueue($advancedState, $completedState['endWordIndex']);
			}
		}

		return $treeComplete;
	}

	/**
	 * Store extra information to make it easier to extract parse trees later
	 * @return bool Tree complete?
	 */
	private function storeStateInfo(array $completedState, array $chartedState, array &$advancedState)
	{
		$treeComplete = false;

		// store the state's "children" to ease building the parse trees from the packed forest
		$advancedState['children'] = !isset($chartedState['children']) ? array() : $chartedState['children'];
		$advancedState['children'][] = $completedState['id'];

		// rule complete?
		$consequentCount = count($chartedState['rule']) - 1;
		if ($chartedState['dotPosition'] == $consequentCount) {

			// complete sentence?
			if ($chartedState['rule'][self::ANTECEDENT]['cat'] == 'gamma') {

				// that matches all words?
				if ($completedState['endWordIndex'] == count($this->words)) {

					$this->treeInfo['sentences'][] = $advancedState;

					// set a flag to allow the parser to stop at the first complete parse
					$treeComplete = true;
				}
			}
		}

		return $treeComplete;
	}

	/**
	 * Adds a state to the chart to the right position.
	 * A state that is already present is not entered again.
	 * Meaning is applied to the (completed) state here.
	 *
	 * The function at its current state is a reflection of the algorithm at page 570 of chapter 15.
	 *
	 * @param array $state
	 * @param int $position
	 */
	private function enqueue(array $state, $position)
	{
		// check for completeness
		if ($this->isIncomplete($state)) {

			if (!$this->isStateInChart($state, $position)) {

				$this->pushState($state, $position);
			}

		} elseif ($this->unifyState($state)) {

			if ($this->applySemantics($state)) {

#echo $state['semantics']."\n";

				if (!$this->isStateInChart($state, $position)) {

					$this->pushState($state, $position);
				}

			}

		}
	}

	private function unifyState(array &$state)
	{
#todo
		return true;
	}

	/**
	 * Applies the semantics part of the state's rule
	 *
	 * @param array $state
	 * @return bool
	 */
	private function applySemantics(array &$state)
	{
		$head = reset($state['rule']);
		if (!isset($head['semantics'])) {
			return true;
		} else {
			$semanticSpecification = $head['semantics'];
		}

		$Rule = self::createSemanticStructure($semanticSpecification);
		if ($Rule) {

			$childSemantics = array();

			$i = 1;
			foreach ($state['children'] as $childNodeId) {
#todo: does not handle multiple categories (i.e. S => NP NP)
				$cat = $state['rule'][$i]['cat'];
				$childState = $this->treeInfo['states'][$childNodeId];
				$childSemantics[$cat] = $childState['semantics'];
				$i++;
			}

			$Applier = new SemanticApplier();
			$sem = $Applier->apply($Rule, $childSemantics);
			$state['semantics'] = $sem;
		}

		return true;
	}

	private function pushState($state, $position)
	{
		static $stateIDs = 0;

		$this->showDebug('enqueue', $state);

		$stateIDs++;

		$state['id'] = $stateIDs;
		$this->treeInfo['states'][$stateIDs] = $state;
		$this->chart[$position][] = $state;
	}

	private function isStateInChart(array $state, $position)
	{
		foreach ($this->chart[$position] as $presentState) {
			if (
				$presentState['rule'] == $state['rule'] &&
				$presentState['dotPosition'] == $state['dotPosition'] &&
				$presentState['startWordIndex'] == $state['startWordIndex'] &&
				$presentState['endWordIndex'] == $state['endWordIndex'] &&
				// this could be replaced by a fast test for subsumption of both dags;
				// however, accepting the duplicate state is probably faster than the fastest test for subsumption
				// an added complexity would be the implication of semantic structures
				true
				) {
					return true;
			}
		}
		return false;
	}

	private function isIncomplete(array $state)
	{
		$consequentCount = count($state['rule']);
		return ($state['dotPosition'] < $consequentCount);
	}

	private function getNextCat(array $state)
	{
		return $state['rule'][$state['dotPosition']]['cat'];
	}

	/**
	 * @param array $rule
	 * @return LabeledDAG
	 */
	public static function createLabeledDag(array $rule)
	{
		$tree = array();
		foreach ($rule as $index => $line) {
			if (isset($line['features'])) {
				$tree[$line['cat'] . '@' . $index] = $line['features'];
			}
		}

		return new LabeledDAG($tree);
	}

	public static function createSemanticStructure($semanticSpecification)
	{
		if ($semanticSpecification === null) {
			return null;
		} else {
			$Parser = new SemanticStructureParser();
			$SemanticStructure = $Parser->parse($semanticSpecification);
			return $SemanticStructure;
		}
	}

	/**
	 * Returns a new LabeledDAG object that is the unification of $ChartedDag and a single path in $CompletedDag.
	 *
	 * @param LabeledDAG $CompletedDag
	 * @param LabeledDAG $ChartedDag
	 * @param $antecedent A label in $CompletedDag that will be unified with $ChartedDag
	 * @param $consequent Same label, except for the @ index
	 * @return false|LabeledDAG
	 */
	private function unifyStates(LabeledDAG $CompletedDag, LabeledDAG $ChartedDag, $antecedent, $consequent)
	{
		$PartialCompletedDag = $CompletedDag->followPath($antecedent)->renameLabel($antecedent, $consequent);

		$UniDag = $PartialCompletedDag->unify($ChartedDag);

		return $UniDag;
	}

	private function showDebug($function, array $state)
	{
		if (Settings::$debugParser) {
			$rule = $state['rule'];
			$dotPosition = $state['dotPosition'];
			$start = $state['startWordIndex'];
			$end = $state['endWordIndex'];

			$post = array();
			for ($i = self::CONSEQUENT; $i < count($rule); $i++) {
				if ($i == $dotPosition) {
					$post[] = '.';
				}
				$post[] = $rule[$i]['cat'];
			}
			if ($i == $dotPosition) {
				$post[] = '.';
			}

			echo
				str_repeat('    ', $start) .
				$function . ' ' .
				$rule[self::ANTECEDENT]['cat'] . ' => ' . implode(' ', $post) . ' ' .
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

			$parseTrees[] = $this->extractParseTreeBranch($root);
		}
		return $parseTrees;
	}

	private function extractFirstTree()
	{
		if (!empty($this->treeInfo['sentences'])) {
			$root = $this->treeInfo['sentences'][0];
			$tree = $this->extractParseTreeBranch($root);
		} else {
			$tree = null;
		}

		return $tree;
	}

	/**
	 * Turns a parse state into a parse tree branch
	 * @return array A branch.
	 */
	private function extractParseTreeBranch(array $state)
	{
		$rule = $state['rule'];

		$antecedent = $rule[self::ANTECEDENT]['cat'];

		if ($antecedent == 'gamma') {
			$constituentId = $state['children'][0];
			$constituent = $this->treeInfo['states'][$constituentId];
			return $this->extractParseTreeBranch($constituent);
		}

		$branch = array(
			'part-of-speech' => $antecedent
		);

		if ($this->Grammar->isPartOfSpeech($antecedent)) {
			$branch['word'] = $rule[self::CONSEQUENT]['cat'];
		}

		$dagTree = $state['dag']->getTree();
		$branch['features'] = null;
		if (isset($dagTree[$antecedent . '@0'])) {
			$branch['features'] = $dagTree[$antecedent . '@0'];
		}

		$branch['semantics'] = $state['semantics'];

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
