function SideTabs()
{

}

SideTabs.show = function(tabsId, tabIndex)
{
	var buttons = document.getElementsByClassName('tabButton_' + tabsId);
	for (var i = 0; i < buttons.length; i++) {
		var button = buttons[i];
		if (button.id == 'tabButton_' + tabsId + '_' + tabIndex) {
			$(button).addClassName('active');
		} else {
			$(button).removeClassName('active');
		}
	}

	var panels = document.getElementsByClassName('tabPanel_' + tabsId);
	for (var i = 0; i < panels.length; i++) {
		var panel = panels[i];
		if (panel.id == 'tabPanel_' + tabsId + '_' + tabIndex) {
			$(panel).addClassName('active');
		} else {
			$(panel).removeClassName('active');
		}
	}
}