<?php

namespace agentecho\test;

use agentecho\component\SemanticStructureParser;
use agentecho\component\DataMapper;

require_once __DIR__ . '/../component/Autoload.php';

/**
 * @author Patrick van Bergen
 */
class MapperTest extends \PHPUnit_Framework_TestCase
{
	public function testMapperSimple()
	{
		$Mapper = new DataMapper(__DIR__ . '/../resources/testMapper.map');
		$Parser = new SemanticStructureParser();

		// map 1 to 1
		$this->testMapper($Parser, $Mapper, 'blue(?a, ?b)', 'oak(?b, ?a)');

		// map 1 to 1 and 1 to 2
		$this->testMapper($Parser, $Mapper, 'blue(?a, ?b) and yellow(?b, ?c)', 'oak(?b, ?a) and pine(?b, ?s1) and larch(?s1, ?c)');

		// map 2 to 1
		$this->testMapper($Parser, $Mapper, 'green(?x, ?y) and orange(?y, ?z)', 'berch(?x, ?z)');
	}

	private function testMapper(SemanticStructureParser $Parser, DataMapper $Mapper, $in, $out)
	{
		$Question = $Parser->parse($in);
		$Relations = $Mapper->mapPredications($Question);

		$result = (string)$Relations;
		$this->assertEquals($out, $result);
	}
}
