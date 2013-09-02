function LineEditor(editorElement)
{
	this.editorElement = editorElement;
	this.formElement = editorElement.select('input.form')[0];
	this.pieceContainer = editorElement.select('div.pieces')[0];
	this.sizeMeter = editorElement.select('span.sizeMeter')[0];
	this.cells = [];
	this.popup = new Popup();

	this.init();
}

LineEditor.prototype.init = function()
{
	// hide the element that contains the full value to be submitted
	this.formElement.hide();

	// create inputs for each of the line pieces
	var pieces = this.getLinePieces();
	for (var i = 0; i < pieces.length; i++) {

		var cell = this.createCell();
		cell.setText(pieces[i]);
	}

	// add an empty cell
	var cell = this.createCell();
	cell.setFocus();

	// link popup to cell
	this.popup.linkToCell(cell);
}

LineEditor.prototype.getLinePieces = function()
{
	return this.formElement.value.split(',');
}

LineEditor.prototype.getLastCell = function()
{
	return this.cells[this.cells.length - 1];
}

LineEditor.prototype.createCell = function()
{
	var cell = new LineCell(this);

	cell.fitInputToText();

	this.pieceContainer.appendChild(cell.getElement());
	this.cells.push(cell);

	return cell;
}

LineEditor.prototype.removeCell = function(cell)
{
	cell.remove();
	var index = this.cells.indexOf(cell);
	if (index != -1) {
		this.cells.splice(index, 1);
	}
}

LineEditor.prototype.updateInputValue = function(event)
{
	var value = '';

	for (var i = 0; i < this.cells.length; i++) {
		var cell = this.cells[i];
		var text = cell.getText();

		if (text == '') {
			break;
		}

		if (i > 0) {
			value += ',';
		}

		value += text;
	}

	this.formElement.value = value;
}

LineEditor.prototype.getCellIndex = function(cell)
{
	var index = 0;

	for (var i = 0; i < this.cells.length; i++) {
		if (this.cells[i] == cell) {
			index = i;
		}
	}

	return index;
}

LineEditor.prototype.getCellByIndex = function(index)
{
	if (this.cells.length -1 >= index) {
		return this.cells[index];
	} else {
		return null;
	}
}

LineEditor.prototype.getWordsUpTo = function(index)
{
	var words = '';

	for (var i = 0; i <= index; i++) {
		var cell = this.cells[i];
		var text = cell.getText();

		if (i > 0) {
			words += ',';
		}

		words += text;
	}

	return words;
}

LineEditor.prototype.onCellFocus = function(cell)
{
	this.removeEmptyCellAtEnd(cell);
	this.popup.setCell(cell);
	this.loadPopupSuggests(cell);
}

LineEditor.prototype.removeEmptyCellAtEnd = function(cell)
{
	var lastCell = this.getLastCell();
	if (this.cells.length > 1) {
		if (lastCell != cell) {
			if (lastCell.getText() == '') {
				this.removeCell(lastCell);
			}
		}
	}
}

LineEditor.prototype.loadPopupSuggests = function(cell)
{
	var index = this.getCellIndex(cell);
	var value = this.getWordsUpTo(index);
	var lineEditor = this;

	new Ajax.Request('index.php?action=suggest&value=' + encodeURI(value), {
		onSuccess: function(response) {
			var response = response.responseJSON;

			suggests = response.suggests;

			lineEditor.popup.populate(suggests, cell.getText());
		}
	});
}

LineEditor.prototype.focusNextCell = function(cell)
{
	var cellIndex = this.getCellIndex(cell);

	cellIndex++;
	var nextCell = this.getCellByIndex(cellIndex);
	if (!nextCell) {
		nextCell = this.createCell();
	}

	nextCell.setFocus();
	nextCell.setCaretAtStart()
}

LineEditor.prototype.focusPreviousCell = function(cell)
{
	var cellIndex = this.getCellIndex(cell);

	cellIndex--;
	var prevCell = this.getCellByIndex(cellIndex);
	if (prevCell) {
		prevCell.setFocus();
		prevCell.setCaretAtEnd();
	}
}

LineEditor.prototype.calculateWidth = function(text)
{
	// place the text in an element that resizes as a result of it
	this.sizeMeter.innerHTML = text;

	// return this element's current width
	return this.sizeMeter.getWidth() + 6; // 2 x 3px padding
}

// create all line editors
$$('div.lineEditor').each( function(editorElement){ new LineEditor(editorElement); } );
