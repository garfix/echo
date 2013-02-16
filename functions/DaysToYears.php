<?php

namespace agentecho\functions;

/**
 * @author Patrick van Bergen
 */
class DaysToYears
{
	function invoke(array $parameters)
	{
		return (int)floor($parameters[0] / 365.25);
	}
}
