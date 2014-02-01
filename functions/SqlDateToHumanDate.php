<?php

namespace agentecho\functions;

use agentecho\datastructure\Atom;

/**
 * @author Patrick van Bergen
 */
class SqlDateToHumanDate
{
	function invoke(array $parameters)
	{
		$Date = new \DateTime($parameters[0]->getName());

		#todo: format in the correct locale

		return new Atom(date('d-m-Y', $Date->getTimestamp()));
	}
}
