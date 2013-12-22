<?php

namespace agentecho\component;

use agentecho\datastructure\ParseRule;
use agentecho\datastructure\ProductionRule;
use agentecho\grammar\Grammar;
use agentecho\exception\SemanticsNotFoundException;
use agentecho\component\parser\SemanticStructureParser;

/**
 * An implementation of Earley's top-down chart parsing algorithm as described in
 * "Speech and Language Processing" (first edition) - Daniel Jurafsky & James H. Martin (Prentice Hall, 2000)
 * It is the basic algorithm (p 381) extended with semantics (p 570)
 */
class EarleyParser
{
	static $debugParser = false;

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
	 * @param \agentecho\grammar\Grammar $Grammar
	 * @param array $words
	 * @return array A structure:
	 * - success: boolean
	 * - tree: either a tree, or null
	 * - errorMessages: either null or a message
	 * - lastParsedIndex: the index of the last word that was used in a parse
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
		$Production = new ProductionRule();
		$Production->setAntecedent('gamma');
		$Production->setConsequents(array('S'));
		$Rule = new ParseRule();
		$Rule->setProduction($Production);

		$initialState = array(
			'rule' => $Rule,
			'dotPosition' => 1,
			'startWordIndex' => 0,
			'endWordIndex' => 0,
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

		$nextConsequent = $state['rule']->getProduction()->getConsequentCategory($state['dotPosition'] - 1);

		$endWordIndex = $state['endWordIndex'];

		// go through all rules that have the next consequent as their antecedent
		foreach ($this->Grammar->getParseRulesForAntecedent($nextConsequent) as $newRule) {

			$predictedState = array(
				'rule' => $newRule,
				'dotPosition' => 1,
				'startWordIndex' => $endWordIndex,
				'endWordIndex' => $endWordIndex,
				'semantics' => null,
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

		$nextConsequent = $state['rule']->getProduction()->getConsequentCategory($state['dotPosition'] - 1);

		$endWordIndex = $state['endWordIndex'];
		$endWord = $this->words[$endWordIndex];

		if ($this->Grammar->isWordAPartOfSpeech($endWord, $nextConsequent)) {

			$Semantics = $this->Grammar->getSemanticsForWord($endWord, $nextConsequent);

			if ($Semantics === false) {
				throw new SemanticsNotFoundException($endWord);
			}

			$Production = new ProductionRule();
			$Production->setAntecedent($nextConsequent);
			$Production->setConsequents(array($endWord), false);
			$NewRule = new ParseRule();
			$NewRule->setProduction($Production);

			$scannedState = array(
				'rule' => $NewRule,
				'dotPosition' => 2,
				'startWordIndex' => $endWordIndex,
				'endWordIndex' => $endWordIndex + 1,
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
	 * @param array $completedState
	 * @return bool Tree complete?
	 */
	private function complete(array $completedState)
	{
		$this->showDebug('complete', $completedState);

		$treeComplete = false;

		$completedAntecedent = $completedState['rule']->getProduction()->getAntecedentCategory();

		foreach ($this->chart[$completedState['startWordIndex']] as $chartedState) {

			$dotPosition = $chartedState['dotPosition'];
			$rule = $chartedState['rule'];

			// check if the antecedent of the completed state matches the charted state's consequent at the dot position
			if (($dotPosition > $rule->getProduction()->getConsequentCount()) || ($rule->getProduction()->getConsequentCategory($dotPosition - 1) != $completedAntecedent)) {
				continue;
			}

			$advancedState = array(
				'rule' => $rule,
				'dotPosition' => $dotPosition + 1,
				'startWordIndex' => $chartedState['startWordIndex'],
				'endWordIndex' => $completedState['endWordIndex'],
				'semantics' => null
			);

			// store extra information to make it easier to extract parse trees later
			$treeComplete = $this->storeStateInfo($completedState, $chartedState, $advancedState);

			if ($treeComplete) {
				break;
			}

			$this->enqueue($advancedState, $completedState['endWordIndex']);
		}

		return $treeComplete;
	}

	/**
	 * Store extra information to make it easier to extract parse trees later
	 * @param array $completedState
	 * @param array $chartedState
	 * @param array $advancedState
	 * @return bool Tree complete?
	 */
	private function storeStateInfo(array $completedState, array $chartedState, array &$advancedState)
	{
		$treeComplete = false;

		// store the state's "children" to ease building the parse trees from the packed forest
		$advancedState['children'] = !isset($chartedState['children']) ? array() : $chartedState['children'];
		$advancedState['children'][] = $completedState['id'];

		// rule complete?

		$consequentCount = $chartedState['rule']->getProduction()->getConsequentCount();

		if ($chartedState['dotPosition'] == $consequentCount) {

			// complete sentence?
			$antecedent = $chartedState['rule']->getProduction()->getAntecedentCategory();

			if ($antecedent == 'gamma') {

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
		$Rule = $state['rule']->getSemantics();

		if ($Rule) {

			$childSemantics = $this->listChildSemantics($state);

			$Applier = new SemanticApplier();

#todo: don't calculate these over and over again
			$childNodeTexts = $this->listChildTexts($state);

			// combine the semantics of the children to determine the semantics of the parent
			$Semantics = $Applier->apply($Rule, $childSemantics, $childNodeTexts);
			$state['semantics'] = $Semantics;
		} else {
			$i = 0;
		}

		return true;
	}

	/**
	 * Create an array of semantics for each of the state's children.
	 * Deals with cases like S => NP NP
	 * @param array $state
	 * @return array
	 */
	private function listChildSemantics(array $state)
	{
		$childSemantics = array();

		$i = 0;
		foreach ($state['children'] as $childNodeId) {

			$childState = $this->treeInfo['states'][$childNodeId];

			$childId = $state['rule']->getProduction()->getConsequent($i);

			$childSemantics[$childId] = $childState['semantics'];
			$i++;
		}

		return $childSemantics;
	}

	private function getChildId(array &$childSemantics, $cat)
	{
		// childId = NP, if there is already an NP, turn it into NP1, and name the current NP "NP2"
		$childId = $cat;
		$childIdIndex = 0;

		// check if the category previously raised to index 1
		if  (isset($childSemantics[$cat . '1'])) {
			$childId = $cat . '1';
			$childIdIndex = 1;
		}

		while (isset($childSemantics[$childId])) {
			// there exists already a child id like this
			if ($childIdIndex == 0) {
				// it is an id without an index
				// rename the existing NP to NP1
				$value = $childSemantics[$cat];
				unset($childSemantics[$cat]);
				$childIdIndex++;
				$childId = $cat . $childIdIndex;
				$childSemantics[$childId] = $value;
			}
			// create the next id
			$childIdIndex++;
			$childId = $cat . $childIdIndex;
		}

		return $childId;
	}

	private function listChildTexts(array $state)
	{
		$childTexts = array();

		$i = 0;
		foreach ($state['children'] as $childNodeId) {

			$cat = $state['rule']->getProduction()->getConsequentCategory($i);

			$childState = $this->treeInfo['states'][$childNodeId];
			$childId = $this->getChildId($childTexts, $cat);
			$childTexts[$childId] = implode(' ', $this->getWordRange($childState['startWordIndex'], $childState['endWordIndex'] - 1));
			$i++;
		}

		return $childTexts;
	}

	private function getWordRange($startIndex, $endIndex)
	{
		return array_slice($this->words, $startIndex, $endIndex - $startIndex + 1);
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
				$presentState['endWordIndex'] == $state['endWordIndex']
				) {
					return true;
			}
		}
		return false;
	}

	private function isIncomplete(array $state)
	{
		$consequentCount = $state['rule']->getProduction()->getConsequentCount() + 1;

		return ($state['dotPosition'] < $consequentCount);
	}

	private function getNextCat(array $state)
	{
		return $state['rule']->getProduction()->getConsequentCategory($state['dotPosition'] - 1);
	}

	public static function createSemanticStructure($semanticSpecification)
	{
		if ($semanticSpecification === null) {
			return false;
		} else {
			$Parser = new SemanticStructureParser();
			$SemanticStructure = $Parser->parse($semanticSpecification);
			return $SemanticStructure;
		}
	}

	private function showDebug($function, array $state)
	{
		if (self::$debugParser) {
			$rule = $state['rule'];
			$dotPosition = $state['dotPosition'];
			$start = $state['startWordIndex'];
			$end = $state['endWordIndex'];

			$post = array();
			for ($i = 0; $i < $rule->getProduction()->getConsequentCount(); $i++) {
				if ($i + 1 == $dotPosition) {
					$post[] = '.';
				}
				$post[] = $rule->getProduction()->getConsequentCategory($i - 1);
			}
			if ($i == $dotPosition) {
				$post[] = '.';
			}

			echo
				str_repeat('    ', $start) .
				$function . ' ' .
				$rule[0]['cat'] . ' => ' . implode(' ', $post) . ' ' .
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
	 * @param array $state
	 * @return array A branch.
	 */
	private function extractParseTreeBranch(array $state)
	{
		$rule = $state['rule'];

		$antecedent = $rule->getProduction()->getAntecedent();
		$antecedentCategory = $rule->getProduction()->getAntecedentCategory();

		if ($antecedent == 'gamma') {
			$constituentId = $state['children'][0];
			$constituent = $this->treeInfo['states'][$constituentId];
			return $this->extractParseTreeBranch($constituent);
		}

		$branch = array(
			'part-of-speech' => $antecedentCategory
		);

		if ($this->Grammar->isPartOfSpeech($antecedent)) {
			$branch['word'] = $rule->getProduction()->getConsequentCategory(0);
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
