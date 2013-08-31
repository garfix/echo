function Popup()
{
	this.cell = null;
	this.items = [];

	// create the element and add it to body
	ul = new Element('ul');
	ul.addClassName('popup');
	$$('body')[0].appendChild(ul);
	this.ul = ul;
}

Popup.prototype.setCell = function(cell)
{
	this.cell = cell;
}

Popup.prototype.getCell = function()
{
	return this.cell;
}

Popup.prototype.show = function()
{
	// show popup just below the active input
	var input = this.cell.getInput();
	var offset = input.cumulativeOffset();

	var left = offset.left;
	var top = offset.top + input.getHeight();

	this.ul.setStyle({ left: left + 'px', top: top + 'px' });
}

Popup.prototype.hide = function()
{

}

Popup.prototype.linkToCell = function(cell)
{
	this.cell = cell;

	if (cell.getText() == '') {
		this.hide();
	} else {
		this.cell.lineEditor.loadPopupSuggests(cell);
	}
}

Popup.prototype.populate = function(values)
{
	var popup = this;

	this.clear();

	if (values.length > 0) {

		for (var i = 0; i < values.length; i++) {

			var value = values[i];

			var item = new Element('li');
			var link = new Element('a');

			link.href = '';

			link.onclick = function(e) { popup.onClick(e); return false; }
			link.onkeypress = function(e) { popup.onKeyPress(e); }

			link.insert(value);
			item.appendChild(link);
			this.ul.appendChild(item);
		}

		this.show();
	} else {
		this.hide();
	}
}

Popup.prototype.clear = function()
{
	var items = this.ul.childElements();

	for (var i = 0; i < items.length; i++) {
		items[i].remove();
	}
}

Popup.prototype.onClick = function(event)
{
	var cell = this.cell;
	var anchor = event.findElement('a');
	var text = anchor.innerHTML;

	cell.setText(text);

	cell.lineEditor.updateInputValue();

	cell.lineEditor.focusNextCell(cell);
}

Popup.prototype.onKeyPress = function(event)
{
	var cell = this.cell;

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
			nextItem = this.getFirstPopupElement();
		}

		nextItem.down('a').focus();
	} else if (event.keyCode == Event.KEY_RETURN) {

		var anchor = event.findElement('a');
		var text = anchor.innerHTML;
		cell.setText(text);
		cell.lineEditor.focusNextCell(cell);
	}

}

Popup.prototype.getFirstElement = function()
{
	var items = this.ul.childElements();

	return (items.length == 0 ? null : items[0]);
}
