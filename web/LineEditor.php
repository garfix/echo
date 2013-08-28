<?php

/**
 * @author Patrick van Bergen
 */
class LineEditor
{
	const MAX_WORD_COUNT = 20;

	private $name = 'lineEditor';

	private $possibleLines = array();
	private $linePieces = array('');

	public function __construct()
	{
		$this->maxWordCount = self::MAX_WORD_COUNT;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function setLinePieces(array $linePieces)
	{
		$this->linePieces = $linePieces;
	}

	public function getLine()
	{
		return implode(' ', $this->linePieces);
	}

	public function setPossibleLines(array $lines)
	{
		$this->possibleLines = $lines;
	}

	public function __toString()
	{
		# dit veld bevat de uiteindelijke contents, maar wordt direct hidden gemaakt en vervangen in js door een aantal andere text inputs
		$value = htmlspecialchars(implode(',', $this->linePieces));
		$html = "
			<div class='lineEditor'>
				<input class='form' type='text' name= '{$this->name}' value='$value'>
				<div class='pieces'>
				</div>
			</div>";

		return $html;
	}

	public function getJavascriptFiles()
	{
		return array('LineCell.js', 'LineEditor.js');
	}

	public function getStylesheet()
	{
		return 'LineEditor.css';
	}
}
