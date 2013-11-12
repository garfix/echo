<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class NoLexicalEntryFoundForSemantics extends EchoException
{
	protected $messageText = "No lexicon entry matches these semantics: %s";
}
