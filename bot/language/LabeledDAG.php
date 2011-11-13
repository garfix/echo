<?php

/**
 * This this an implementation of a Directed Acyclic Graph with named arcs.
 */
class LabeledDAG
{
	private $bins = array();
	private $dag = array();

	public function __construct($tree = null)
	{
		if ($tree !== null) {
			$this->dag = $this->createDag($tree);
		}
	}

	/**
	 * Turns a tree structure with labeled arcs into a labeled dag (directed acyclic graph) in which the numbered labeled arcs point to the same nodes.
	 * @param array $clause
	 * @return array A dag
	 */
	public function createDag($tree)
	{
		if (!is_array($tree)) {

			// end of recursion
			$dag = $tree;

		} else {

			// initialize dag
			$dag = array();

			foreach ($tree as $label => $subTree) {

				list($name, $internalLabel) = $this->extractLabel($label);

				// create a dag for the label
				$subDag = $this->createDag($subTree);

				// the value of the label may either be null, a tree or a scalar
				if (is_null($subDag)) {
					// if the bin existed, leave it unchanged, otherwise create it
					if (!isset($this->bins[$internalLabel])) {
						$this->bins[$internalLabel] = null;
					}
				} elseif (is_array($subDag)) {
					// if the bin existed, merge it, otherwise create it
					if (isset($this->bins[$internalLabel])) {
						$this->bins[$internalLabel] = array_merge($this->bins[$internalLabel], $subDag);
					} else {
						$this->bins[$internalLabel] = $subDag;
					}
				} else {
					// it is a scalar
					// we can check here for a possible inconsistency in the tree
					if (isset($this->bins[$internalLabel])) {
						if ($subDag !== $this->bins[$internalLabel]) {
							trigger_error('The DAG contains two values for the same position.', E_USER_ERROR);
						}
					}
					$this->bins[$internalLabel] = $subDag;
				}

				// join the subdag to the dag via the labeled arc
				$dag[$name] = &$this->bins[$internalLabel];

			}

		}

		return $dag;
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

	public function __clone()
	{
		// this will do the trick. note that both fields need to be done at once, since they contain cross-references
		list($this->dag, $this->bins) = unserialize(serialize(array($this->dag, $this->bins)));
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
		return $this->mergeDag($this->dag, $DAG->dag, $DAG->bins, $uniqId, $map);
	}

	/**
	 * Merge $newDag into the existing $dag.
	 * Fails if both dags contain incompatible scalar values.
	 *
	 * @param array $dag
	 * @param array $newDag
	 * @return bool Successful merge?
	 */
	protected function mergeDag(&$dag, $newDag, $newBins, $uniqId, &$map)
	{
		// make sure the labels in $newDag that are present in $dag are processed first
		$newDag = array_merge(array_intersect_assoc($dag, $newDag), $newDag);

		// go through each label
		foreach ($newDag as $label => $subDag) {

			// create an internal label if a new bin needs to be created
			// it should be recognizable for subdags that are processed later
			// it should also make sure that it doesn't collide with the existing labels of $dag (hence the $uniqId)
			$labelInNewDag = array_search($subDag, $newBins);
			$internalLabel = ($labelInNewDag ?: $label)  . '-' . $uniqId;

			// make sure the node exists in this dag
			if (!isset($dag[$label])) {
				// check if this subdag matches case [A], see below
				if (isset($map[$internalLabel])) {
					// it does, use the bin that was referenced before
					$dag[$label] = $map[$internalLabel];
				} else {
					// create a new bin. Make sure this bin will be shared by other labels in the dag.
					$this->bins[$internalLabel] = null;
					$dag[$label] = &$this->bins[$internalLabel];
				}
			} else {
				// this $subDag matches the one in $dag
				// [A] now store a reference for the case in which another path reaches the same $subDag;
				// a path that is _not_ present in $dag
				$map[$internalLabel] = $dag[$label];
			}

			if (is_array($subDag)) {
				if (is_null($dag[$label])) {
					$dag[$label] = array();
				} elseif (is_scalar($dag[$label])) {
					trigger_error('Merge of scalar and array', E_USER_ERROR);
				}

				// merge the new structure with the existing one
				// if this merge fails, fail too
				if (!$this->mergeDag($dag[$label], $subDag, $newBins, $uniqId, $map)) {
					return false;
				}

			} elseif (is_scalar($subDag)) {
				if (is_null($dag[$label])) {
					$dag[$label] = $subDag;
				} elseif (is_array($dag[$label])) {
					trigger_error('Merge of scalar and array', E_USER_ERROR);
				} else {
					if ($subDag != $dag[$label]) {
						return false;
					}
				}
			} else {
				// $subDag is null
			}
		}

		return true;
	}

	/**
	 * Returns a new DAG that forms a subset of this DAG that begins with $label
	 * @param string $label
	 * @return LabeledDAG
	 */
	public function followPath($path)
	{
if (!isset($this->dag[$path])) {
#	die($path);
	return new LabeledDAG();
}


		$NewDAG = new LabeledDAG();
		$NewDAG->dag = array();

		if (is_array($this->dag[$path])) {
			$this->copyDag($this->dag, $NewDAG->dag, $NewDAG->bins, $path);
		} else {
			$NewDAG->dag[$path] = $this->dag[$path];
		}

		return $NewDAG;
	}

	public function setPathValue($path, $value)
	{
		if (empty($path)) {
			trigger_error('Path cannot be empty.', E_USER_ERROR);
		}

		if (is_array($value)) {
			trigger_error('Value should be a scalar.', E_USER_ERROR);
		}

		$dag = &$this->dag;
		$length = count($path);

		for ($i = 0; $i < $length - 1; $i++) {
			$label = $path[$i];
			if (!isset($dag[$label])) {
				trigger_error('Label not found: ' . $label, E_USER_ERROR);
			}
			$dag = &$dag[$label];
		}

		$label = $path[$length - 1];

		$dag[$label] = $value;
	}

	public function getPathValue($path)
	{
		$dag = $this->dag;

		foreach ($path as $label) {
			if (!isset($dag[$label])) {
				return null;
			} else {
				$dag = $dag[$label];
			}
		}

		return $dag;
	}

	private function copyDag(&$dag, &$newDag, &$newBins, $path = null)
	{
		foreach ($dag as $label => $subDag) {

			if ($path !== null) {
				if ($label != $path) {
					continue;
				}
			}

			// is the $subDag a value?
			if (!is_array($subDag)) {

				// yes it is
				$newDag[$label] = $subDag;

			} else {

				$internalLabel = array_search($subDag, $this->bins);
				$newBins[$internalLabel] = array();

				$newDag[$label] = &$newBins[$internalLabel];

				$this->copyDag($dag[$label], $newDag[$label], $newBins);
			}
		}
	}

	public function __toString()
	{
		return print_r($this->dag, true);
	}

	protected static function createUniqueId()
	{
		static $id = 0;

		return 'uniq' . ++$id;
	}

}
