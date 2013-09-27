<?php

namespace agentecho\exception;

class ProductionException extends EchoException
{
	protected $messageText = 'Production exception: unknown constituent: %s';
}