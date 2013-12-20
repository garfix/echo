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

	/** @var PredicationList $Semantics */
	private $Semantics;

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

		if ($this->Semantics) {
			$values[] = 'semantics: ' . $this->Semantics;
		}

		return '[' . implode(', ', $values) . ']';
	}
}
