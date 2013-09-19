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

	this.ul.setStyle({ left: left + 'px', top: top + 'px', display: 'inherit' });
}

Popup.prototype.hide = function()
{
	this.ul.setStyle({ display: 'none' });
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

Popup.prototype.populate = function(values, selectedText)
{
	var popup = this;
	var count = 0;

	this.clear();

	for (var name in values) {
		var html = values[name];

		count++;

		var item = new Element('li');
		var link = new Element('a');

		link.href = '';

		link.onclick = function(e) { popup.onClick(e); return false; }
		link.onkeydown = function(e) { popup.onKeyDown(e); }

		link.insert(html);
		link.setAttribute('data-text', name);

		item.appendChild(link);
		this.ul.appendChild(item);
	}


	if (count > 0) {
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
	var anchor = event.findElement('a');
	var text = anchor.getAttribute('data-text');

	this.selectValue(text);
}

Popup.prototype.onKeyDown = function(event)
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
		event.preventDefault();

	} else if (event.keyCode == Event.KEY_DOWN) {

		var item = event.findElement('li');
		var nextItem = item.next('li');
		if (!nextItem) {
			nextItem = this.getFirstElement();
		}

		nextItem.down('a').focus();
		event.preventDefault();

	} else if (event.keyCode == Event.KEY_RETURN) {

		var anchor = event.findElement('a');
		var text = anchor.getAttribute('data-text');

		this.selectValue(text);
		event.preventDefault();
	}

}

Popup.prototype.selectValue = function(text)
{
	var cell = this.cell;

	cell.setText(text);
	cell.lineEditor.updateInputValue();
	cell.lineEditor.focusNextCell(cell);
}

Popup.prototype.getFirstElement = function()
{
	var items = this.ul.childElements();

	return (items.length == 0 ? null : items[0]);
}

Popup.prototype.getValues = function()
{
	var suggests = [];
	var anchors = this.ul.select('a');

	for (var i = 0; i < anchors.length; i++) {
		suggests.push(anchors[i].getAttribute('data-text'));
	}

	return suggests;
}
