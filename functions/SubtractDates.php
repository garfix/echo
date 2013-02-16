<?php

namespace agentecho\functions;

/**
 * @author Patrick van Bergen
 */
class SubtractDates
{
	public function invoke(array $parameters)
	{
		$Date1 = new \DateTime($parameters[0]);
		$Date2 = new \DateTime($parameters[1]);
		$Diff = $Date1->diff($Date2);
		return $Diff->format("%a");
	}
}
