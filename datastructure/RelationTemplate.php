<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class RelationTemplate extends Relation
{
	public function __toString()
	{
		return '{{ ' . $this->arguments[0] . ' }}';
	}
}
