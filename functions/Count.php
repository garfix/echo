<?php

namespace agentecho\functions;

/**
 * @author Patrick van Bergen
 */
class Count
{
	function invoke(array $parameters)
	{
		return count($parameters[0]);
	}
}
