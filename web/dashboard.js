function Dashboard(maxLength)
{
	this.maxLength = maxLength;
}

Dashboard.prototype.start = function()
{
	this.attachHandlers();
}

Dashboard.prototype.attachHandlers = function()
{
	var dashboard = this;

	for (var i = 0; i < this.maxLength; i++) {
		var select = document.getElementById('word' + i);

		select.observe('change', function(e){
			var selectSelect = e.findElement('select');
			dashboard.update(parseInt(selectSelect.id.replace(/word/, '')));
		});
	}
}

Dashboard.prototype.update = function(activeSelectIndex)
{
	// find the classnames that represent the sentences that are still possible
	var possibleSentences = this.findPossibleSentences(activeSelectIndex);

	// allow just these sentences in the latter words
	this.allowSentencesInLatterWords(activeSelectIndex, possibleSentences);
}

Dashboard.prototype.findPossibleSentences = function(activeSelectIndex)
{
	var sentences = [];

	for (var i = 0; i <= activeSelectIndex; i++) {
		var select = document.getElementById('word' + i);
		var option = select.options[select.selectedIndex];
		var optionSentences = $w(option.className);

		if (sentences.length == 0) {
			// first select
			sentences = optionSentences;
		} else {
			sentences = this.intersect(sentences, optionSentences);
		}
	}

	return sentences;
}

Dashboard.prototype.allowSentencesInLatterWords = function(activeSelectIndex, sentences)
{
	for (var i = activeSelectIndex + 1; i < this.maxLength; i++) {
		var select = document.getElementById('word' + i);

		var possibleOptionCount = 0;
		var lastOptionIndex = 0;

		for (var o = 1; o < select.options.length; o++) {
			var option = select.options[o];
			var optionSentences = $w(option.className);
			var intersect = this.intersect(sentences, optionSentences);

			if (intersect.length == 0) {
				option.hide();
				if (o == select.selectedIndex) {
					select.selectedIndex = 0;
				}
			} else {
				option.show();
				possibleOptionCount++;
				lastOptionIndex = o;
			}
		}

		if (possibleOptionCount == 1) {
			select.selectedIndex = lastOptionIndex;
		}
	}
}

Dashboard.prototype.intersect = function(arr1, arr2)
{
	var result = [];

	for (var i = 0; i < arr1.length; i++) {
		var value = arr1[i];
		if (arr2.indexOf(value) != -1) {
			result.push(value);
		}
	}

	return result;
}