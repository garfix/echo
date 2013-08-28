function LineCell(lineEditor)
{
	var input = new Element('input');
	input.type = 'text';

	var popup = new Element('ul');
	popup.addClassName('popup');
	popup.hide();

	var container = new Element('span');
	container.addClassName('lineCellContainer');
	container.appendChild(input);
	container.appendChild(popup);

	this.lineEditor = lineEditor;
	this.input = input;
	this.container = container;
	this.popup = popup;

	var cell = this;

	input.observe('focus', function(event){ cell.onFocus(event); });
	input.observe('blur', function(event){ cell.onBlur(event); });
	input.observe('keypress', function(event){ cell.onKeyPress(event); });
	input.observe('change', function(event){ cell.onChange(event); });
	input.observe('input', function(event){ cell.onInput(event); });

	this.ignoreNextBlur = false;
}

LineCell.prototype.getContainer = function()
{
	return this.container;
}

LineCell.prototype.setText = function(text)
{
	this.input.value = text;
}

LineCell.prototype.getText = function(text)
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

LineCell.prototype.onBlur = function(event)
{
	if (this.ignoreNextBlur) {
		this.ignoreNextBlur = false;
	} else {
		this.hidePopup(event);
	}
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
	if (event.keyCode == Event.KEY_DOWN) {
		var firstElement = this.getFirstPopupElement();
		if (firstElement) {
			this.ignoreNextBlur = true;
			firstElement.down('a').focus();
		}
	}
}

LineCell.prototype.showPopup = function(event)
{
	this.popup.show();
}

LineCell.prototype.hidePopup = function(event)
{
	this.popup.hide();
}

LineCell.prototype.populatePopup = function(values)
{
	this.clearPopup();

	var cell = this;

	for (var i = 0; i < values.length; i++) {

		var value = values[i];

		var item = new Element('li');
		var link = new Element('a');

		link.href = '';

		link.onclick = function() {

			cell.hidePopup();

			// make sure the link is not activated
			return false;
		}

		link.onmousedown = function(event) {
			var anchor = event.findElement('a');
			var text = anchor.innerHTML;

			cell.setText(text);

			cell.lineEditor.updateInputValue();

			cell.lineEditor.focusNextCell(cell);

			// a blur will occur on the input; ignore it
			cell.ignoreNextBlur = true;
		}

		link.onkeypress = function(event) {

			if (event.keyCode == Event.KEY_UP) {

				var item = event.findElement('li');
				var prevItem = item.previous('li');
				if (prevItem) {
					prevItem.down('a').focus();
				} else {
					cell.input.focus();
				}
			} else if (event.keyCode == Event.KEY_DOWN) {

				var item = event.findElement('li');
				var nextItem = item.next('li');
				if (!nextItem) {
					nextItem = cell.getFirstPopupElement();
				}

				nextItem.down('a').focus();
			} else if (event.keyCode == Event.KEY_RETURN) {

				var anchor = event.findElement('a');
				var text = anchor.innerHTML;
				cell.setText(text);
				cell.lineEditor.focusNextCell(cell);
			}

		}

		link.insert(value);
		item.appendChild(link);
		this.popup.appendChild(item);
	}

}

LineCell.prototype.clearPopup = function()
{
	var items = this.popup.childElements();

	for (var i = 0; i < items.length; i++) {
		items[i].remove();
	}
}

LineCell.prototype.getFirstPopupElement = function()
{
	var items = this.popup.childElements();

	return (items.length == 0 ? null : items[0]);
}