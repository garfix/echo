function LineCell(lineEditor)
{
	var input = new Element('input');
	input.type = 'text';
	input.addClassName('cell');

	this.lineEditor = lineEditor;
	this.input = input;

	var cell = this;

	input.observe('focus', function(event){ cell.onFocus(event); });
	input.observe('keydown', function(event){ cell.onKeyDown(event); });
	input.observe('change', function(event){ cell.onChange(event); });
	input.observe('input', function(event){ cell.onInput(event); });
}

LineCell.prototype.remove = function()
{
	this.input.remove();
}

LineCell.prototype.getElement = function()
{
	return this.input;
}

LineCell.prototype.getInput = function()
{
	return this.input;
}

LineCell.prototype.setText = function(text)
{
	this.input.value = text;
	this.fitInputToText();
}

LineCell.prototype.getText = function()
{
	return this.input.value;
}

LineCell.prototype.setFocus = function()
{
	this.input.focus();
}

LineCell.prototype.onFocus = function(event)
{
	this.lineEditor.onCellFocus(this);
}

LineCell.prototype.onChange = function(event)
{
	this.lineEditor.updateInputValue();
}

LineCell.prototype.onInput = function(event)
{
	this.lineEditor.updateInputValue();

	this.fitInputToText();

	this.lineEditor.loadPopupSuggests(this);
}

LineCell.prototype.onKeyDown = function(event)
{
	if (event.keyCode == 32) {

		if (this.getCaretPosition() == this.getLastCaretPosition()) {
			if (this.lineEditor.popup.getValues().indexOf(this.getText()) != -1) {
				this.lineEditor.focusNextCell(this);
				event.preventDefault();
			}
		}

	} else if (event.keyCode == Event.KEY_DOWN) {
		var firstElement = this.lineEditor.popup.getFirstElement();
		if (firstElement) {
			firstElement.down('a').focus();
		}
	} else if (event.keyCode == Event.KEY_BACKSPACE) {
		if (this.getCaretPosition() == 0) {
			this.lineEditor.focusPreviousCell(this);
			event.preventDefault();
		}
	} else if (event.keyCode == Event.KEY_LEFT) {
		if (this.getCaretPosition() == 0) {
			this.lineEditor.focusPreviousCell(this);
			event.preventDefault();
		}
	} else if (event.keyCode == Event.KEY_RIGHT) {
		var caret = this.getCaretPosition();
		if (caret && (caret == this.getLastCaretPosition())) {
			this.lineEditor.focusNextCell(this);
			event.preventDefault();
		}
	}
}

LineCell.prototype.fitInputToText = function()
{
	var text = this.getText();

	if (text == '') {
		var width = 50;
	} else {
		var width = this.lineEditor.calculateWidth(text);
	}

	this.input.setStyle({ width: width + 'px'});
}

LineCell.prototype.setCaretAtEnd = function()
{
	this.setCaretPosition(this.getLastCaretPosition());
}

LineCell.prototype.setCaretAtStart = function()
{
	this.setCaretPosition(0);
}

LineCell.prototype.getLastCaretPosition = function()
{
	return this.input.value.length;
}

// http://stackoverflow.com/questions/2897155/get-cursor-position-within-an-text-input-field
LineCell.prototype.getCaretPosition = function()
{
	var oField = this.input;

	// Initialize
	var iCaretPos = 0;

	// IE Support
	if (document.selection) {

		// Set focus on the element
		oField.focus ();

		// To get cursor position, get empty selection range
		var oSel = document.selection.createRange ();

		// Move selection start to 0 position
		oSel.moveStart ('character', -oField.value.length);

		// The caret position is selection length
		iCaretPos = oSel.text.length;
	}

	// Firefox support
	else if (typeof oField.selectionStart != 'undefined') {
		iCaretPos = oField.selectionStart;
	}

	// Return results
	return (iCaretPos);
}

// http://stackoverflow.com/questions/512528/set-cursor-position-in-html-textbox
LineCell.prototype.setCaretPosition = function(caretPos)
{
	var elem = this.input;

	if (elem.createTextRange) {
		var range = elem.createTextRange();
		range.move('character', caretPos);
		range.select();
	}
	else {
		if (typeof elem.selectionStart != 'undefined') {
			elem.focus();
			elem.setSelectionRange(caretPos, caretPos);
		}
		else
			elem.focus();
	}
}