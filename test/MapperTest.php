<?php

namespace agentecho\test;

use agentecho\component\parser\SemanticStructureParser;
use agentecho\component\DataMapper;

require_once __DIR__ . '/../Autoload.php';

/**
 * @author Patrick van Bergen
 */
class MapperTest extends \PHPUnit_Framework_TestCase
{
	public function testMapperSimple()
	{
		$Mapper = new DataMapper(__DIR__ . '/helpers/testMapper.map');
		$Parser = new SemanticStructureParser();

		// map 1 to 1
		$this->performMapper($Parser, $Mapper, 'blue(?a, ?b)', 'oak(?b, ?a)', false, false);

		// map 1 to 1 and 1 to 2
		$this->performMapper($Parser, $Mapper, 'blue(?a, ?b) yellow(?b, ?c)', 'oak(?b, ?a) pine(?b, ?s1) larch(?s1, ?c)', false, false);

		// map 2 to 1
		$this->performMapper($Parser, $Mapper, 'green(?x, ?y) orange(?y, ?z)', 'birch(?x, ?z)', false, false);

		// some predicate occurs twice
		$this->performMapper($Parser, $Mapper, 'blue(?a, ?b) yellow(?b, ?c) blue(?c, ?d)', 'oak(?b, ?a) oak(?d, ?c) pine(?b, ?s1) larch(?s1, ?c)', false, false);

		// some predicate could not be matched
		$exception = false;
		try {
			$this->performMapper($Parser, $Mapper, 'blue(?a, ?b) pink(?b, ?c)', false, false, false);
		} catch (\agentecho\exception\DataMappingFailedException $E) {
			$exception = true;
		}
		$this->assertTrue($exception);
	}

	public function testRecursiveMapping()
	{
		$Mapper = new DataMapper(__DIR__ . '/helpers/testMapper.map');
		$Parser = new SemanticStructureParser();

		$out = 'home_2_main_street(?p, ?s2) main_street_2_town(?s2, ?s1) town_2_forest(?s1, ?s3) forest_2_school(?s3, ?p)';
		$this->performMapper($Parser, $Mapper, 'home_2_school(?p)', $out, true, true);
	}

	private function performMapper(SemanticStructureParser $Parser, DataMapper $Mapper, $in, $out, $iterate, $allowUnprocessed)
	{
		$Question = $Parser->parse($in);
		$Relations = $Mapper->mapRelations($Question, $iterate, $allowUnprocessed);

		$result = (string)$Relations;
		$this->assertEquals($out, $result);
	}
}
