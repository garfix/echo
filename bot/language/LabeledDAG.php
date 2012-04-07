<?php

/**
 * This this an implementation of a Directed Acyclic Graph with labeled arcs.
 */
class LabeledDAG
{
	/** @var An array of nodes. Each node contains either children or a value. */
	private $nodes = array();

	/**
	 * Creates the DAG based on a description in a tree.
	 * An example description:
	 *
	 * 	$tree = array(
	 *	    'noun' => array('head-1' => array('tense-2' => 'past', 'person' => 1)),
	 *	    'verb' => array('head-1' => null),
	 *	    'VP' => array('head' => array('tense-2' => null)),
	 *	);
	 *
	 *  In this example 'head-1' forms the label 'head' and both 'head-1's point to the same node. 'tense-2' is similar.
	 */
	public function __construct($tree = null)
	{
		$this->createNode('root', $tree);
	}

	/**
	 * Turns a (sub)tree structure with labeled arcs into a single labeled node.
	 */
	private function createNode($internalLabel, $tree)
	{
		// create the node
		$node = array();

		// fill it
		if (is_scalar($tree)) {

			// set the node's value and end the recursion
			$node['value'] = $tree;

		} elseif (is_array($tree)) {

			foreach ($tree as $label => $subTree) {

				// create a $name for the user and $internalLabel for internal node management
				list($name, $internalSubLabel) = $this->extractLabel($label);

				// traverse the subtree
				$this->createNode($internalSubLabel, $subTree);

				// add an child to the node
				$node['children'][$name] = $internalSubLabel;
			}
		}

		// check if the node existed already
		if (!isset($this->nodes[$internalLabel])) {

			// it is new
			$this->nodes[$internalLabel] = $node;

		} else {

			// the node exists, so merge
			// does the new node have children?
			if (!empty($node['children'])) {

				// ensure that there is a 'children' field in the existing node
				if (!isset($this->nodes[$internalLabel]['children'])) {
					$this->nodes[$internalLabel]['children'] = array();
				}

				// merge new children
				$this->nodes[$internalLabel]['children'] += $node['children'];
			}

			// does the new node have a value?
			if (isset($node['value'])) {
				// overwrite it
				$this->nodes[$internalLabel]['value'] = $node['value'];
			}
		}
	}

	/**
	 * Splits the $label into a short label and a longer internal label;.
	 *
	 * @param $label
	 * @return array An (name, id) array
	 */
	private function extractLabel($label)
	{
		// extract the name and id of the feature
		$regexp =
			'/^' .
			'(?P<name1>[a-z][a-z@_0-9]*)(-(?P<id1>\d+))?' .
			'(\{(?P<name2>[a-z][a-z@_0-9]*)(-(?P<id2>\d+))?\})?' .
			'$/i';

		if (!preg_match($regexp, $label, $matches)) {
			trigger_error('Error in identifier: ' . $label, E_USER_ERROR);
		}

		if (!empty($matches['name2'])) {
			$id = isset($matches['id2']) ? $matches['id2'] : self::createUniqueId();
			$internalLabel = $matches['name2'] . '-' . $id;
			$externalLabel = $matches['name1'];

			if (!empty($matches['id1'])) {
				trigger_error('The alias may not contain an id: ' . $label, E_USER_ERROR);
			}
		} else {
			$id = isset($matches['id1']) ? $matches['id1'] : self::createUniqueId();
			$internalLabel = $matches['name1'] . '-' . $id;
			$externalLabel = $matches['name1'];
		}

		return array($externalLabel, $internalLabel);

//		// extract the name and id of the feature
//		$regexp =
//			'/^' .
//			'(?P<name>[a-z][a-z@_0-9]*)(-(?P<id>\d+))?' .
//			'(\{(?P<alias>[a-z][a-z@_0-9]*)\})?' .
//			'$/i';
//
//		if (!preg_match($regexp, $label, $matches)) {
//			trigger_error('Error in identifier: ' . $label, E_USER_ERROR);
//		}
//
//		$id = isset($matches['id']) ? $matches['id'] : self::createUniqueId();
//		if (!empty($matches['alias'])) {
//			$internalLabel = $matches['name'] . '-' . $id;
//			$externalLabel = $matches['alias'];
//		} else {
//			$internalLabel = $matches['name'] . '-' . $id;
//			$externalLabel = $matches['name'];
//		}
//
//		return array($externalLabel, $internalLabel);


//		// extract the name and id of the feature
//		if (!preg_match('/^([a-z][a-z@_0-9]*)(-(\d+))?$/i', $label, $matches)) {
//			trigger_error('Error in identifier: ' . $label, E_USER_ERROR);
//		}
//		$name = $matches[1];
//		$id = isset($matches[3]) ? $matches[3] : self::createUniqueId();
//		$internalLabel = $name . '-' . $id;
//
//		return array($name, $internalLabel);
	}

	/**
	 * Returns a new LabeledDAG object that is the unification of this DAG object and $DAG,
	 * or false if unification fails.
	 * @return LabeledDAG|false
	 */
	public function unify(LabeledDAG $DAG)
	{
		$NewDAG = clone $this;
		$map = array();
		$success = $NewDAG->mergeNode('root', 'root', $DAG->nodes, $map);

		return $success ? $NewDAG : false;
	}

	/**
	 * Merges the node specified with $thisDagInternalLabel of this dag with
	 *the node specified with $newDagInternalLabel of $newDag into the existing $dag.
	 * Fails if both dags contain incompatible scalar values.
	 *
	 * @param array $dag
	 * @param array $newDag
	 * @return bool Successful merge?
	 */
	private function mergeNode($thisDagInternalLabel, $newDagInternalLabel , $newDag, &$map)
	{
		// look up the node in the new dag
		$newNode = $newDag[$newDagInternalLabel];

		// get or create this dag's equivalent node
		if (isset($this->nodes[$thisDagInternalLabel])) {
			$thisNode = $this->nodes[$thisDagInternalLabel];
		} else {
			$thisNode = array();
		}

		// the new node has either a value or children
		if (isset($newNode['value'])) {

			// if this node has a value, it should be the same
			if (isset($thisNode['value'])) {
				if ($thisNode['value'] != $newNode['value']) {
					return false;
				}

			} elseif (isset($thisNode['children'])) {

				r($thisNode);
				r($newNode);
				trigger_error('This node has children while new node has a value.', E_USER_ERROR);

			} else {

				$thisNode['value'] = $newNode['value'];

			}

		} elseif (isset($newNode['children'])) {

			$children = $newNode['children'];
			if (isset($thisNode['children'])) {

				// combine this node's children with the new node's children
				$children = array_merge(array_intersect_key($thisNode['children'], $children), $children);

			} elseif (isset($thisNode['value'])) {

				r($thisNode);
				trigger_error('This node has a value while new node has children.', E_USER_ERROR);

			}

			// go through all children
			foreach ($children as $label => $newDagInternalChildLabel) {

				// create an internal label for the new node
				$thisDagInternalChildLabel = $this->createInternalLabelForMerge($label, $newDagInternalChildLabel, $thisNode, $map);

				// merge the child nodes
				$success = $this->mergeNode($thisDagInternalChildLabel, $newDagInternalChildLabel, $newDag, $map);
				if (!$success) {
					return false;
				}

				// link the child to this node
				$thisNode['children'][$label] = $thisDagInternalChildLabel;
			}
		}

		// register the node
		$this->nodes[$thisDagInternalLabel] = $thisNode;

		return true;
	}

	/**
	 * Important helper function to create internal labels that allow for a successful merge.
	 *
	 * @return string An internal label
	 */
	private function createInternalLabelForMerge($label, $newDagInternalLabel, $thisNode, &$map)
	{
		// check if the new dag shares the label with this dag
		if (isset($thisNode['children'][$label])) {
			// same label => same node
			$internalLabel = $thisNode['children'][$label];

			// store the fact that the internal label has changed
			$map[$newDagInternalLabel] = $internalLabel;

		// check if the new dag has a shared node that has been mapped before (in the previous if)
		} elseif (isset($map[$newDagInternalLabel])) {

			// use that mapped label
			$internalLabel = $map[$newDagInternalLabel];

		// reuse the internal label of the new dag, if it isn't used yet
		} elseif (!isset($this->nodes[$newDagInternalLabel])) {

			// reuse the existing label
			$internalLabel = $newDagInternalLabel;

			// make sure this label can be used for further nodes
			$map[$newDagInternalLabel] = $internalLabel;

		} else {
			// create a new internal label
			list(, $internalLabel) = self::extractLabel($label);
		}

		return $internalLabel;
	}

	/**
	 * Returns a new DAG that forms a subset of this DAG that begins with $label
	 * @param string $label
	 * @return LabeledDAG
	 */
	public function followPath($label)
	{
		$NewDAG = new LabeledDAG();

		// sanity check: following a label that is not available
		if (!isset($this->nodes['root']['children'][$label])) {
			return $NewDAG;
		}

		// find the internal label of the node that $label points to
		$internalLabel = $this->nodes['root']['children'][$label];

		// create a subset root
		$NewDAG->nodes['root']['children'][$label] = $internalLabel;

		// initialize list of nodes to copy
		$todo = array($internalLabel);

		// go though all nodes to copy
		while (!empty($todo)) {

			$internalLabel = array_pop($todo);

			// copy node
			$NewDAG->nodes[$internalLabel] = $this->nodes[$internalLabel];

			// copy node's children
			if (isset($this->nodes[$internalLabel]['children'])) {
				foreach ($this->nodes[$internalLabel]['children'] as $childLabel) {
					$todo[] = $childLabel;
				}
			}
		}

		return $NewDAG;
	}

	public function renameLabel($label, $newLabel)
	{
		if (isset($this->nodes['root']['children'][$label])) {
			$node = $this->nodes['root']['children'][$label];
			unset($this->nodes['root']['children'][$label]);
			$this->nodes['root']['children'][$newLabel] = $node;
		}

		return $this;
	}

	/**
	 * Sets the value at the specified path through the dag.
	 */
	public function setPathValue(array $path, $value)
	{
		if (is_array($value)) {
			trigger_error('Value should be a scalar.', E_USER_ERROR);
		}

		// init node label
		$internalLabel = 'root';

		// walk the path
		foreach ($path as $label) {

			if (!isset($this->nodes[$internalLabel]['children'][$label])) {
				trigger_error('Label not found: ' . $label, E_USER_ERROR);
			}

			// update node label
			$internalLabel = $this->nodes[$internalLabel]['children'][$label];
		}

		// update the value
		$this->nodes[$internalLabel]['value'] = $value;
	}

	/**
	 * Returns the value at the specified path through the dag.
	 * @return mixed|null Returns the value if the path exists. If it doesn't, it returns null.
	 */
	public function getPathValue($path)
	{
		// init node label
		$internalLabel = 'root';

		// walk the path
		foreach ($path as $label) {

			if (!isset($this->nodes[$internalLabel]['children'][$label])) {
				return null;
			}

			// update node label
			$internalLabel = $this->nodes[$internalLabel]['children'][$label];
		}

		// return the value
		if (isset($this->nodes[$internalLabel]['value'])) {
			return $this->nodes[$internalLabel]['value'];
		} elseif (isset($this->nodes[$internalLabel])) {
			return $this->getTreeForNode($internalLabel);
		} else {
			return null;
		}
	}

	public function getTree()
	{
		$tree = $this->getTreeForNode('root');
		return $tree;
	}

	private function getTreeForNode($internalLabel)
	{
		$node = $this->nodes[$internalLabel];

		$tree = null;

		if (isset($node['children'])) {
			$tree = array();
			foreach ($node['children'] as $childLabel => $childInternalLabel) {
				$tree[$childLabel] = $this->getTreeForNode($childInternalLabel);
			}
		}
		if (isset($node['value'])) {
			$tree = $node['value'];
		}

		return $tree;
	}

	public function __toString()
	{
		// a single path from start to end
		$path = array();
		// all traversed paths
		$paths = array();

		// traverse all paths
		$this->traverse('root', $path, $paths);

		// create a string that describes all paths
		$string = "";
		foreach ($paths as $pathIndex => $currentPath) {
			foreach ($currentPath as $stepIndex => $step) {

				// check if this copies the field above
				if ($this->isStepCopy($pathIndex, $stepIndex, $paths)) {
					$string .= '"                   ';
				} else {
					$string .= $step . str_repeat(' ', (20 - strlen($step)));
				}
			}
			$string .= "\n";

		}

		return $string;
	}

	/**
	 * Take the next step through the dag, that starts with $internalLabel.
	 * A path is added to $path each time the $path is completed.
	 *
	 * @param $path A stack to keep track of the current path through the dag.
	 * @param $paths A set of all paths through the dag.
	 */
	private function traverse($internalLabel, array $path, array &$paths)
	{
		$path[] = $internalLabel;

		$node = $this->nodes[$internalLabel];

		if (empty($node['children'])) {

			if (isset($node['value'])) {
				$last = count($path) - 1;
				$path[$last] .= ' = ' . $node['value'];
			}

			$paths[] = $path;

		} else {

			foreach ($node['children'] as $label => $internalChildLabel) {
				$this->traverse($internalChildLabel, $path, $paths);
			}

		}
	}

	/**
	 * Determines if step $stepIndex of the $pathIndex-th $paths is a copy of the same step in a step above
	 */
	private function isStepCopy($pathIndex, $stepIndex, $paths)
	{
		$field = $paths[$pathIndex][$stepIndex];
		$parentField = $stepIndex == 0 ? null : $paths[$pathIndex][$stepIndex - 1];

		for ($i = $pathIndex - 1; $i >= 0; $i--) {
			// the parent field needs to be the same
			if ($stepIndex == 0 || $paths[$i][$stepIndex - 1] == $parentField) {
				// the field needs to be the same
				if ($paths[$i][$stepIndex] == $field) {
					return true;
				}
			} else {
				break;
			}
		}

		return false;
	}

	private static function createUniqueId()
	{
		static $id = 0;

		return 'gen' . ++$id;
	}

}