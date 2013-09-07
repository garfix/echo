<?php

namespace agentecho\web\component;

/**
 * @author Patrick van Bergen
 */
class LineEditor extends HtmlElement
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

		$LineEditor = new Div();
		$LineEditor->addClass('lineEditor');

			$LineEditor->add($Input = new Input());
			$Input->addClass('form');
			$Input->setType('text');
			$Input->setName($this->name);
			$Input->setValue($value);

			$LineEditor->add($Pieces = new Div());
			$Pieces->addClass('pieces');

			$LineEditor->add($SizeMeter = new Span());
			$SizeMeter->addClass('sizeMeter');

		return (string)$LineEditor;
	}

	public function getJavascriptFiles()
	{
		return array('component/lineeditor/Popup.js', 'component/lineeditor/LineCell.js', 'component/lineeditor/LineEditor.js');
	}

	public function getStyleSheetFiles()
	{
		return array('component/lineeditor/LineEditor.css');
	}
}