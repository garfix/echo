<?php

namespace agentecho\templatefunctions;

use agentecho\datastructure\RelationList;

class Question
{
	function invoke(array $parameters, RelationList $Question)
	{
		return $Question->createClone();
	}
}