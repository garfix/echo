<?php

namespace agentecho\web\component;

/**
 * @author Patrick van Bergen
 */
class SideTabs extends HtmlElement
{
	private $tabs = array();

	private $activeTab = 0;

	public function __construct()
	{
		$this->setId('sideTabs');
	}

	public function addTab($title, $contents, $isHtml = false)
	{
		$this->tabs[] = array(
			'title' => $title,
			'contents' => $contents,
			'isHtml' => $isHtml,
		);
	}

	public function setActiveTab($index)
	{
		$this->activeTab = $index;
	}

	public function __toString()
	{
		$Tabs = new Div();
		$Tabs->addClass('tab');

		$Left = new Div();
		$Left->addClass('tabLeft');
		$Tabs->add($Left);

		$Right = new Div();
		$Right->addClass('tabRight');
		$Tabs->add($Right);

		foreach ($this->tabs as $index => $tab) {

			$Button = new Anchor();
			$Button->addClass('tabButton');
			$Button->addClass('tabButton_' . $this->getId());
			$Button->setId('tabButton_' . $this->getId() . '_' . $index);
			$Button->setHref('');
			$Button->onClick('SideTabs.show("' . $this->getId() . '", "' . $index . '"); return false');
			$Button->addText($tab['title']);
			$Left->add($Button);

			$Panel = new Div();
			$Panel->addClass('tabPanel');
			$Panel->addClass('tabPanel_' . $this->getId());
			$Panel->setId('tabPanel_' . $this->getId() . '_' . $index);
			$Panel->addText($tab['contents']);
			$Right->add($Panel);

			if ($index == $this->activeTab) {
				$Button->addClass('active');
				$Panel->addClass('active');
			}
		}

		return (string)$Tabs;
	}

	public function getStyleSheetFiles()
	{
		return array('component/sidetabs/SideTabs.css');
	}

	public function getJavascriptFiles()
	{
		return array('component/sidetabs/SideTabs.js');
	}
}
