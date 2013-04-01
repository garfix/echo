<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class Map
{
	private $mappings = array();

	public function addMapping(DataMapping $DataMapping)
	{
		$this->mappings[] = $DataMapping;
	}

	public function setMappings(array $mappings)
	{
		$this->mappings = $mappings;
	}

	public function getMappings()
	{
		return $this->mappings;
	}

	public function __toString()
	{
		$string = implode('; ', $this->mappings);
		return $string;
	}
}
