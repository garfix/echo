<?php

/**
 * This this an implementation of a Directed Acyclic Graph with named arcs.
 */
class LabeledDAG
{
	private $dag = array();

	public function __construct($tree = null)
	{
		$this->createDag('root', $tree);
	}

	/**
	 * Turns a tree structure with labeled arcs into a labeled dag (directed acyclic graph) in which the numbered labeled arcs point to the same nodes.
	 * @param array $clause
	 * @return array A dag
	 */
	public function createDag($dagLabel, $tree)
	{
		$node = array();

		if (is_scalar($tree)) {

			// end of recursion
			$node['value'] = $tree;

		} elseif (is_array($tree)) {

			foreach ($tree as $label => $subTree) {

				// create a $name for the user and $internalLabel for internal node management
				list($name, $internalLabel) = $this->extractLabel($label);

				// traverse the subtree
				$this->createDag($internalLabel, $subTree);

				// add an child to the dag
				$node['children'][$name] = $internalLabel;
			}
		}

		// check if the node existed already
		if (!isset($this->dag[$dagLabel])) {

			// it is new
			$this->dag[$dagLabel] = $node;

		} else {

			// the node exists, so merge
			// does the new node have children?
			if (!empty($node['children'])) {

				// ensure that there is a 'children' field in the existing node
				if (!isset($this->dag[$dagLabel]['children'])) {
					$this->dag[$dagLabel]['children'] = array();
				}

				// merge new children
				$this->dag[$dagLabel]['children'] += $node['children'];
			}

			// does the new node has a value?
			if (isset($node['value'])) {
				$this->dag[$dagLabel]['value'] = $node['value'];
			}
		}
	}

	/**
	 * Returns the outermost keys of the dag.
	 *
	 * @return array
	 */
	public function getKeys()
	{
		return array_keys($this->dag);
	}

	/**
	 * Replaces the outermost $key by $newKey.
	 *
	 * @param $key
	 * @param $newKey
	 */
	public function replaceKey($key, $newKey)
	{
		if ($newKey != $key) {
			$this->dag[$newKey] = $this->dag[$key];
			unset($this->dag[$key]);
		}
	}

	/**
	 * Splits the $label into a name and an id.
	 *
	 * @param $label
	 *
	 * @return array An (name, id) array
	 */
	private function extractLabel($label)
	{
		// extract the name and id of the feature
		if (!preg_match('/^([a-z][a-z@_0-9]*)(-(\d+))?$/i', $label, $matches)) {
			trigger_error('Error in identifier: ' . $label, E_USER_ERROR);
		}
		$name = $matches[1];
		$id = isset($matches[3]) ? $matches[3] : self::createUniqueId();
		$internalLabel = $name . '-' . $id;

		return array($name, $internalLabel);
	}

	/**
	 * Returns a new LabeleDAG object that is the unification of this DAG object and $DAG
	 * @param LabeledDAG $DAG
	 * @return LabeledDAG
	 */
	public function unify(LabeledDAG $DAG)
	{
		$NewDAG = clone $this;
		$success = $NewDAG->merge($DAG);

		return $success ? $NewDAG : false;
	}

	/**
	 * Merges $DAG into this dag.
	 * Fails if both dags contain incompatible scalar values
	 *
	 * @param LabeledDAG $DAG
	 * @return bool Successful merge?
	 */
	public function merge(LabeledDAG $DAG)
	{
		$uniqId = self::createUniqueId();
		$map = array();

		return $this->mergeNode('root', 'root', $DAG->dag, $uniqId, $map);
	}

	/**
	 * Merge $newDag into the existing $dag.
	 * Fails if both dags contain incompatible scalar values.
	 *
	 * @param array $dag
	 * @param array $newDag
	 * @return bool Successful merge?
	 */
	protected function mergeNode($thisDagInternalLabel, $newDagInternalLabel , $newDag, $uniqId, &$map)
	{
		$newNode = $newDag[$newDagInternalLabel];

		// get or create this dag's equivalent node
		if (isset($this->dag[$thisDagInternalLabel])) {
			$thisNode = $this->dag[$thisDagInternalLabel];
		} else {
			$thisNode = array();
		}

		if (isset($newNode['value'])) {

			if (isset($thisNode['value'])) {
				if ($thisNode['value'] != $newNode['value']) {
					return false;
				}
#todo check for incompatible types 'children'
			} else {
				$thisNode['value'] = $newNode['value'];
			}

		} elseif (isset($newNode['children'])) {

#todo check for incompatible types 'value'

			// make sure the labels in $newDag that are present in $dag are processed first
			$children = $newNode['children'];
			if (isset($thisNode['children'])) {
				$children = array_merge(array_intersect_key($thisNode['children'], $newNode['children']), $newNode['children']);
			}

			// go through all children
			foreach ($children as $label => $newDagInternalChildLabel) {

				$thisDagInternalChildLabel = $this->createInternalLabelForMerge($label, $newDagInternalChildLabel, $thisNode, $uniqId, $map);

				$success = $this->mergeNode($thisDagInternalChildLabel, $newDagInternalChildLabel, $newDag, $uniqId, $map);
				if (!$success) {
					return false;
				}

				$thisNode['children'][$label] = $thisDagInternalChildLabel;
			}
		}

		$this->dag[$thisDagInternalLabel] = $thisNode;

		return true;
	}

	private function createInternalLabelForMerge($label, $newDagInternalLabel, $thisNode, $uniqId, &$map)
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
		} elseif (!isset($this->dag[$newDagInternalLabel])) {

			// reuse the existing label
			$internalLabel = $newDagInternalLabel;

			// make sure this label can be used for futher nodes
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

		// check if this dag has
		if (!isset($this->dag['root']['children'][$label])) {
#todo this should not happen
			return $NewDAG;
		}

		// find the internal label of the node that $label points to
		$internalLabel = $this->dag['root']['children'][$label];

		// create a subset root
		$NewDAG->dag['root']['children'][$label] = $internalLabel;

		// initialize list of nodes to copy
		$todo = array($internalLabel);

		// go though all nodes to copy
		while (!empty($todo)) {

			$internalLabel = array_pop($todo);

			// copy node
			$NewDAG->dag[$internalLabel] = $this->dag[$internalLabel];

			// copy node's children
			if (isset($this->dag[$internalLabel]['children'])) {
				foreach ($this->dag[$internalLabel]['children'] as $childLabel) {
					$todo[] = $childLabel;
				}
			}
		}

		return $NewDAG;
	}

	public function setPathValue(array $path, $value)
	{
		if (is_array($value)) {
			trigger_error('Value should be a scalar.', E_USER_ERROR);
		}

		// init node label
		$internalLabel = 'root';

		// walk the path
		foreach ($path as $label) {

			if (!isset($this->dag[$internalLabel]['children'][$label])) {
				trigger_error('Label not found: ' . $label, E_USER_ERROR);
			}

			// update node label
			$internalLabel = $this->dag[$internalLabel]['children'][$label];
		}

		// update the value
		$this->dag[$internalLabel]['value'] = $value;
	}

	public function getPathValue($path)
	{
		// init node label
		$internalLabel = 'root';

		// walk the path
		foreach ($path as $label) {

			if (!isset($this->dag[$internalLabel]['children'][$label])) {
				return null;
			}

			// update node label
			$internalLabel = $this->dag[$internalLabel]['children'][$label];
		}

		// return the value
		if (!isset($this->dag[$internalLabel]['value'])) {
			return null;
		} else {
			return $this->dag[$internalLabel]['value'];
		}
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

	private function traverse($internalLabel, array $path, array &$paths)
	{
		$path[] = $internalLabel;

		$node = $this->dag[$internalLabel];

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

	protected static function createUniqueId()
	{
		static $id = 0;

		return 'gen' . ++$id;
	}

}
