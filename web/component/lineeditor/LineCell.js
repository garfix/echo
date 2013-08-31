function LineCell(lineEditor)
{
	var input = new Element('input');
	input.type = 'text';

	var container = new Element('span');
	container.addClassName('lineCellContainer');
	container.appendChild(input);

	this.lineEditor = lineEditor;
	this.input = input;
	this.container = container;

	var cell = this;

	input.observe('focus', function(event){ cell.onFocus(event); });
	input.observe('keypress', function(event){ cell.onKeyPress(event); });
	input.observe('change', function(event){ cell.onChange(event); });
	input.observe('input', function(event){ cell.onInput(event); });
}

LineCell.prototype.getInput = function()
{
	return this.input;
}

LineCell.prototype.getContainer = function()
{
	return this.container;
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

	this.lineEditor.loadPopupSuggests(this);
}

LineCell.prototype.onKeyPress = function(event)
{
	if (event.charCode != 0) {

		if (event.charCode == ' ') {
			if (this.getCaretPosition() == this.getLastCaretPosition()) {
				this.lineEditor.focusNextCell(this);
				event.preventDefault();
			}
		}

	} else if (event.keyCode == Event.KEY_DOWN) {
		var firstElement = this.lineEditor.popup.getFirstElement();
		if (firstElement) {
			firstElement.down('a').focus();
		}
	} else if (event.keyCode == Event.KEY_LEFT) {
		if (this.getCaretPosition() == 0) {
			this.lineEditor.focusPreviousCell(this);
			event.preventDefault();
		}
	} else if (event.keyCode == Event.KEY_RIGHT) {
		if (this.getCaretPosition() == this.getLastCaretPosition()) {
			this.lineEditor.focusNextCell(this);
			event.preventDefault();
		}
	}

	this.fitInputToText();
}

LineCell.prototype.fitInputToText = function()
{
	var width = this.lineEditor.calculateWidth(this.getText());

	this.input.setStyle({ width: width + 'px'});
}

LineCell.prototype.setCaretAtEnd = function()
{
	this.setCaretPosition(this.getLastCaretPosition());
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
	else if (oField.selectionStart || oField.selectionStart == '0') {
		iCaretPos = oField.selectionStart;
	}

	// Return results
	return (iCaretPos);
}

// http://stackoverflow.com/questions/512528/set-cursor-position-in-html-textbox
LineCell.prototype.setCaretPosition = function(caretPos)
{
	var elem = this.input;

    if(elem != null) {
        if(elem.createTextRange) {
            var range = elem.createTextRange();
            range.move('character', caretPos);
            range.select();
        }
        else {
            if(elem.selectionStart) {
                elem.focus();
                elem.setSelectionRange(caretPos, caretPos);
            }
            else
                elem.focus();
        }
    }
}