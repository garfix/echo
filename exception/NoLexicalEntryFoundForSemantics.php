<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class NoLexicalEntryFoundForSemantics extends EchoException
{
	protected $messageText = "No lexicon entry (part of speech: %s) matches these semantics: %s";
}
