<?php

namespace agentecho\web\view;

/**
 * @author Patrick van Bergen
 */
class SyntaxView extends TreeView
{
	public function getHtml($syntax)
	{
		return $this->getBranchSyntax($syntax, 0);
	}

	private function getBranchSyntax($branch, $depth)
	{
		$spacing = str_repeat('&nbsp;&nbsp;&nbsp;', $depth);
		$string = $spacing . '[' . $this->markAsType($branch['part-of-speech']);

		if (isset($branch['constituents'])) {
			foreach ($branch['constituents'] as $constituent) {
				$string .= $this->eol() . $this->getBranchSyntax($constituent, $depth + 1);
			}
		} elseif (isset($branch['word'])) {
			$string .= ' ' . $this->markAsValue($branch['word']);
		}

		$string .= ']';

		return $string;
	}
}
