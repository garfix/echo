<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class SemanticsNotFoundException extends EchoException
{
	protected $messageText = 'No semantic attachment was defined for "%s" in the lexicon';
}
