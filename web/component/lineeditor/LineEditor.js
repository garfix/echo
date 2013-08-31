function LineEditor(editorElement)
{
	this.formElement = editorElement.select('input.form')[0];
	this.pieceContainer = editorElement.select('div.pieces')[0];
	this.cells = [];

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

	this.pieceContainer.appendChild(cell.getContainer());
	this.cells.push(cell);

	return cell;
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
	this.loadPopupSuggests(cell);
}

LineEditor.prototype.loadPopupSuggests = function(cell)
{
	var index = this.getCellIndex(cell);
	var value = this.getWordsUpTo(index);

	new Ajax.Request('index.php?action=suggest&value=' + value, {
		onSuccess: function(response) {
			var response = response.responseJSON;

			suggests = response.suggests;

			cell.populatePopup(suggests);
			cell.showPopup();

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

// create all line editors
$$('div.lineEditor').each( function(editorElement){ new LineEditor(editorElement); } );
