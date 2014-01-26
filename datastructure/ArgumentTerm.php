<?php

namespace agentecho\datastructure;

/**
 * An extra interface because Relations and Function often need to be treated in the way.
 *
 * @author Patrick van Bergen
 */
interface ArgumentTerm
{
	public function setArguments(array $arguments);

	public function setArgument($index, $Argument);

	public function getArguments();

	public function getFirstArgument();

	public function getSecondArgument();

	public function getArgument($index);

	public function getArgumentCount();
}
