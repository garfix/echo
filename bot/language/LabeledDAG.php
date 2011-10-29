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

	private function extractLabel($label)
	{
		// extract the name and id of the feature
		if (!preg_match('/^([a-z_0-9]+)(-(\d+))?$/', $label, $matches)) {
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

	protected function __clone()
	{
		// this will do the trick. note that both fields need to be done at once, since they contain cross-references
		list($this->dag, $this->bins) = unserialize(serialize(array($this->dag, $this->bins)));
	}

	public function merge(LabeledDAG $DAG)
	{
		$uniqId = self::createUniqueId();
		return $this->mergeDag($this->dag, $DAG->dag, $DAG->bins, $uniqId);
	}

	/**
	 * Merge $newDag into the existing $dag.
	 * @param array $dag
	 * @param array $newDag
	 */
	protected function mergeDag(&$dag, $newDag, $newBins, $uniqId)
	{
		// go through each label
		foreach ($newDag as $label => $subDag) {

			// is the $subDag a value?
			if (!is_array($subDag)) {

				// yes it is
				// check for conflicts
				if (isset($dag[$label]) && $dag[$label] != $subDag) {

					return false;

				} else {
					// assign new value
					$dag[$label] = $subDag;
				}

			} else {

				// it is a substructure
				// check if the substructure is available in the source dag
				if (!isset($dag[$label])) {

					// it is not
					// create a new bin. Make sure this bin will be shared by other labels in the dag.
					// we do this by looking up the internal label of the bin and using it for the new bin
					// this internal label needs to be modified because it conflicts with the namespace of labels in this DAG
					$internalLabel = array_search($subDag, $newBins) . '-' . $uniqId;
					$this->bins[$internalLabel] = array();

					$dag[$label] = &$this->bins[$internalLabel];
				}

				// merge the new structure with the existing one
				$success = $this->mergeDag($dag[$label], $subDag, $newBins, $uniqId);
				if (!$success) {
					return false;
				}

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

	protected static function createUniqueId()
	{
		static $id = 0;

		return 'uniq' . ++$id;
	}

}
