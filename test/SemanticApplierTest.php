<?php

namespace agentecho\test;

use agentecho\component\parser\SemanticStructureParser;
use agentecho\component\SemanticApplier;
use agentecho\datastructure\AssignmentList;

require_once __DIR__ . '/../Autoload.php';

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
		$Rule = $Parser->parse("
			S.sem = NP.sem VP.sem subject(S.event, S.subject);
			S.event = VP.event;
			S.subject = NP.entity
		");

		$childNodeSemantics = array(
			'NP' => $Parser->parse("name(NP.entity, 'John')"),
			'VP' => $Parser->parse("isa(VP.event, Walk)"),
		);

		$Result = $Applier->apply($Rule, $childNodeSemantics);

		$this->assertSame('name(S.subject, "John") isa(S.event, Walk) subject(S.event, S.subject)', (string)$Result);
	}

	public function testApplyRuleWithTwoNPs()
	{
		$Parser = new SemanticStructureParser();
		$Applier = new SemanticApplier();

		/** @var AssignmentList $Rule  */
		$Rule = $Parser->parse("
			S.sem = NP1.sem NP2.sem VP.sem subject(S.event, S.subject) object(S.event, S.object);
			S.event = VP.event;
			S.subject = NP1.entity;
			S.object = NP2.entity
		");

		$childNodeSemantics = array(
			'NP1' => $Parser->parse("name(NP.entity, 'John')"),
			'NP2' => $Parser->parse("isa(NP.entity, Car)"),
			'VP' => $Parser->parse("isa(VP.event, Drive)"),
		);

		$Result = $Applier->apply($Rule, $childNodeSemantics);

		$this->assertSame('name(S.subject, "John") isa(S.object, Car) isa(S.event, Drive) subject(S.event, S.subject) object(S.event, S.object)', (string)$Result);
	}

	public function testChildAssignmentsSingleOperand()
	{
		$Parser = new SemanticStructureParser();
		$Applier = new SemanticApplier();

		/** @var AssignmentList $Rule  */
		$Rule = $Parser->parse("
			PN.sem = name(PN.entity, propernoun1.text);
			PN.entity = propernoun1.entity
		");

		$childNodeTexts = array(
			'propernoun1' => 'John'
		);

		$Result = $Applier->apply($Rule, array(), $childNodeTexts);

		$this->assertSame('name(PN.entity, "John")', (string)$Result);
	}

	public function testChildAssignmentsTwoOperands()
	{
		$Parser = new SemanticStructureParser();
		$Applier = new SemanticApplier();

		/** @var AssignmentList $Rule  */
		$Rule = $Parser->parse("
			PN.sem = name(PN.entity, propernoun1.text + ' ' + propernoun2.text);
			PN.entity = propernoun1.entity;
			PN.entity = propernoun2.entity
		");

		$childNodeTexts = array(
			'propernoun1' => 'John',
			'propernoun2' => 'Wilks',
		);

		$Result = $Applier->apply($Rule, array(), $childNodeTexts);

		$this->assertSame('name(PN.entity, "John Wilks")', (string)$Result);
	}

	public function testChildAssignmentsThreeOperands()
	{
		$Parser = new SemanticStructureParser();
		$Applier = new SemanticApplier();

		/** @var AssignmentList $Rule  */
		$Rule = $Parser->parse("
			PN.sem = name(PN.entity, propernoun1.text + ' ' + propernoun2.text + ' ' + propernoun3.text);
			PN.entity = propernoun1.entity;
			PN.entity = propernoun2.entity;
			PN.entity = propernoun3.entity
		");

		$childNodeTexts = array(
			'propernoun1' => 'Patrick',
			'propernoun2' => 'van',
			'propernoun3' => 'Bergen',
		);

		$Result = $Applier->apply($Rule, array(), $childNodeTexts);

		$this->assertSame('name(PN.entity, "Patrick van Bergen")', (string)$Result);
	}

	public function testRelationWithChildProperties()
	{
		$Parser = new SemanticStructureParser();
		$Applier = new SemanticApplier();

		/** @var AssignmentList $Rule  */
		$Rule = $Parser->parse("
			NBar.sem = NP1.sem NP2.sem modifier(NP2.entity, NP1.entity);
			NBar.entity = NP2.entity
		");

		$childNodeSemantics = array(
			'NP1' => $Parser->parse("name(this.entity, 'John')"),
			'NP2' => $Parser->parse("isa(this.entity, Car)")
		);

		$Result = $Applier->apply($Rule, $childNodeSemantics, array());

		$this->assertSame("name(NBar_NP1.entity, \"John\") isa(NBar.entity, Car) modifier(NBar.entity, NBar_NP1.entity)", (string)$Result);
	}

	public function testRuleWithIndexedAntecedent()
	{
		$Parser = new SemanticStructureParser();
		$Applier = new SemanticApplier();

		/** @var AssignmentList $Rule  */
		$Rule = $Parser->parse("
			NBar1.sem = NBar2.sem AdjP.sem modifier(NBar1.entity, AdjP.entity);
			NBar1.entity = NBar2.entity
		");

		$childNodeSemantics = array(
			'NBar2' => $Parser->parse("isa(this.entity, Car)"),
			'AdjP' => $Parser->parse("isa(this.entity, Red)")
		);

		$Result = $Applier->apply($Rule, $childNodeSemantics, array());

		$this->assertSame("isa(NBar.entity, Car) isa(NBar_AdjP.entity, Red) modifier(NBar.entity, NBar_AdjP.entity)", (string)$Result);
	}
}
