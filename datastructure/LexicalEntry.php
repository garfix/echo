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

	/** @var RelationList $Semantics */
	private $Semantics;

	/**
	 * @param \agentecho\datastructure\RelationList $Semantics
	 */
	public function setSemantics($Semantics)
	{
		$this->Semantics = $Semantics;
	}

	/**
	 * @return \agentecho\datastructure\RelationList
	 */
	public function getSemantics()
	{
		return isset($this->Semantics) ? $this->Semantics : new RelationList();
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
