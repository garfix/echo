<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class LexicalEntry
{
	/** @var string $wordForm */
	private $wordForm;

	/** @var string $partOfSpeech */
	private $partOfSpeech;

	/** @var LabeledDAG $Features */
	private $Features;

	/** @var PredicationList $Semantics */
	private $Semantics;

	/**
	 * @var LabeledDAG $PrefixedFeatures Like features, but $partOfSpeech is added as the first entry with the original root as its value.
	 */
	private $PrefixedFeatures = null;

	/**
	 * @param \agentecho\datastructure\LabeledDAG $Features
	 */
	public function setFeatures($Features)
	{
		$this->Features = $Features;
	}

	/**
	 * @return \agentecho\datastructure\LabeledDAG
	 */
	public function getFeatures()
	{
		return isset($this->Features) ? $this->Features : new LabeledDAG();
	}

	/**
	 * Returns the features of this entry, extended with $partOfSpeech as the new root.
	 *
	 * @return LabeledDAG|null
	 */
	public function getPrefixedFeatures()
	{
		if ($this->PrefixedFeatures === null) {
			$tree = $this->Features->getOriginalTree();
			$this->PrefixedFeatures = new LabeledDAG(array($this->partOfSpeech => $tree));
		}

		return $this->PrefixedFeatures;
	}

	/**
	 * @param \agentecho\datastructure\PredicationList $Semantics
	 */
	public function setSemantics($Semantics)
	{
		$this->Semantics = $Semantics;
	}

	/**
	 * @return \agentecho\datastructure\PredicationList
	 */
	public function getSemantics()
	{
		return isset($this->Semantics) ? $this->Semantics : new PredicationList();
	}

	/**
	 * @param string $partOfSpeech
	 */
	public function setPartOfSpeech($partOfSpeech)
	{
		$this->partOfSpeech = $partOfSpeech;
	}

	/**
	 * @return string
	 */
	public function getPartOfSpeech()
	{
		return $this->partOfSpeech;
	}

	/**
	 * @param string $wordForm
	 */
	public function setWordForm($wordForm)
	{
		$this->wordForm = $wordForm;
	}

	/**
	 * @return string
	 */
	public function getWordForm()
	{
		return $this->wordForm;
	}

	public function __toString()
	{
		$values = array();

		if ($this->wordForm) {
			$values[] = 'form: ' . "'" . $this->wordForm . "'";
		}

		if ($this->partOfSpeech) {
			$values[] = 'partOfSpeech: ' . "'" . $this->partOfSpeech . "'";
		}

		if ($this->Features) {
			$values[] = 'features: ' . $this->Features;
		}

		if ($this->Semantics) {
			$values[] = 'semantics: ' . $this->Semantics;
		}

		return '[' . implode(', ', $values) . ']';
	}
}
