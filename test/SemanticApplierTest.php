<?php

namespace agentecho\test;

use agentecho\component\parser\SemanticStructureParser;
use agentecho\component\SemanticApplier;

require_once __DIR__ . '/../component/Autoload.php';

/**
 * @author Patrick van Bergen
 */
class SemanticApplierTest extends \PHPUnit_Framework_TestCase
{
	public function testApplyCommonRule()
	{
		$Parser = new SemanticStructureParser();
		$Applier = new SemanticApplier();

		/** @var AssignmentList $Rule  */
		$Rule = $Parser->parse("{
			S.sem = NP.sem and VP.sem and subject(S.event, S.subject);
			S.event = VP.event;
			S.subject = NP.object
		}");

		$childNodeSemantics = array(
			'NP' => $Parser->parse("name(NP.object, 'John')"),
			'VP' => $Parser->parse("isa(VP.event, Walk)"),
		);

		$Result = $Applier->apply($Rule, $childNodeSemantics);

		$this->assertSame('name(S.subject, "John") and isa(S.event, Walk) and subject(S.event, S.subject)', (string)$Result);
	}

	public function testApplyRuleWithTwoNPs()
	{
		$Parser = new SemanticStructureParser();
		$Applier = new SemanticApplier();

		/** @var AssignmentList $Rule  */
		$Rule = $Parser->parse("{
			S.sem = NP1.sem and NP2.sem and VP.sem and subject(S.event, S.subject) and object(S.event, S.object);
			S.event = VP.event;
			S.subject = NP1.object;
			S.object = NP2.object
		}");

		$childNodeSemantics = array(
			'NP1' => $Parser->parse("name(NP.object, 'John')"),
			'NP2' => $Parser->parse("isa(NP.object, Car)"),
			'VP' => $Parser->parse("isa(VP.event, Drive)"),
		);

		$Result = $Applier->apply($Rule, $childNodeSemantics);

		$this->assertSame('name(S.subject, "John") and isa(S.object, Car) and isa(S.event, Drive) and subject(S.event, S.subject) and object(S.event, S.object)', (string)$Result);
	}

	public function testChildAssignments()
	{
		$Parser = new SemanticStructureParser();
		$Applier = new SemanticApplier();

		/** @var AssignmentList $Rule  */
		$Rule = $Parser->parse("{
			PN.sem = name(PN.object, propernoun1.text + ' ' + propernoun2.text);
			PN.object = propernoun1.object;
			PN.object = propernoun2.object
		}");

		$childNodeTexts = array(
			'propernoun1' => 'John',
			'propernoun2' => 'Wilks',
		);

		$Result = $Applier->apply($Rule, array(), $childNodeTexts);

		$this->assertSame('name(PN.object, "John Wilks")', (string)$Result);
	}
}
