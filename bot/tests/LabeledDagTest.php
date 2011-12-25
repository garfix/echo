<?php

require_once __DIR__ . '/../language/LabeledDAG.php';

function testLabeledDAG()
{
	$tree1 = array(
		'aaa' => array('head-1' => null),
		'bbb' => array('head' => array('agreement-2' => null)),
		'ccc' => array('head-1' => array('agreement-2' => null)),
	);

	$tree2 = array(
		'aaa' => array('head-1' => array('tense-2' => 'past', 'agreement' => 'yes')),
		'ddd' => array('head-1' => null),
		'eee' => array('head' => array('tense-2' => null)),
	);

	$tree3 = array(
		'colors-1' => array('red-1' => 1, 'blue' => 2),
		'dogs' => array('blackie' => 3, 'johnson' => 4),
		'skies' => array('structures' => array('a' => array('c-1' => null), 'b' => array('c-1' => 5)))
	);

	$tree4 = array(
		'aaa' => 1,
		'bbb' => 2,
	);

	$tree5 = array(
		'aaa' => 1,
		'bbb' => 3,
	);

	$tree6 = array(
		'pronoun' => array('head' => array('agreement' => array('person' => 1, 'number' => 's')))
	);

	$tree7 = array(
		'NP' => array('head-1' => null),
		'pronoun' => array('head-1' => null),
	);

	$tree8 = array(
		'verb' => array('head' => array('agreement' => array('person' => 1, 'number' => 'p')))
	);

	$tree9 = array(
		'VP' => array('head-1' => null),
		'verb' => array('head-1' => array('agreement' => null)),
		'NP' => array()
	);

	$tree10 = array(
	);

	$tree11 = array(
		'a' => array('head-1' => null),
		'b' => array('head-1' => null),
	);

	$F1 = new LabeledDAG($tree1);
	$F2 = new LabeledDAG($tree1);
	$F3 = new LabeledDAG($tree2);
	$F4 = $F2->unify($F3);
	$F5 = new LabeledDAG($tree3);
	$F6 = $F5->followPath('skies');
	$F7 = new LabeledDAG($tree4);
	$F8 = new LabeledDAG($tree5);
	$F9 = $F7->unify($F8);
	$F10 = new LabeledDAG(array('color' => null));
	$F11 = new LabeledDAG(array('color' => array('a' => 1, 'b' => 2)));
	$F12 = $F10->unify($F11);
	$F13 = new LabeledDAG($tree6);
	$F14 = new LabeledDAG($tree7);
	$F15 = $F13->unify($F14);
	$F16 = new LabeledDAG($tree8);
	$F17 = new LabeledDAG($tree9);
	$F18 = $F16->unify($F17);
	$F19 = new LabeledDAG($tree10);
	$F20 = new LabeledDAG($tree11);
	$F21 = $F19->unify($F20);
	$F21->setPathValue(array('b', 'head'), 1);

	$F1->setPathValue(array('ccc', 'head', 'agreement'), 'no');

	// check that a shared child is implemented correctly
	test(300, $F1->getPathValue(array('aaa', 'head', 'agreement')), 'no');
	test(301, $F1->getPathValue(array('bbb', 'head', 'agreement')), 'no');
	test(302, $F1->getPathValue(array('ccc', 'head', 'agreement')), 'no');
	// check that $F2 is not changed by the unification
	test(310, $F2->getPathValue(array('ccc', 'head', 'agreement')), null);
	// check that $F3 is not changed by the unification
	test(311, $F3->getPathValue(array('ccc', 'head', 'agreement')), null);
	// check that $F4 shows unification
	test(312, $F4->getPathValue(array('ccc', 'head', 'agreement')), 'yes');
	test(313, $F4->getPathValue(array('ddd', 'head', 'agreement')), 'yes');
	// check that $F6 contains the followed path
	test(320, $F6->getPathValue(array('skies', 'structures', 'a', 'c')), 5);
	// check that $F6 does not contain removed paths from $F5
	test(321, $F5->getPathValue(array('dogs', 'blackie')), 3);
	test(322, $F6->getPathValue(array('dogs', 'blackie')), null);
	// check for failing unifications
	test(330, $F9, false);
	test(331, $F12->getPathValue(array('color', 'a')), 1);
	test(332, $F15->getPathValue(array('NP', 'head', 'agreement', 'person')), 1);
	// regression test
	test(333, $F18->getPathValue(array('NP', 'person')), null);
	test(334, $F21->getPathValue(array('a', 'head')), 1);

	$tree = array(
		'color-1' => null,
		'colour{color-1}' => null,
	);
	$F22 = new LabeledDAG($tree);
	$F22->setPathValue('color', 'red');
	test(341, $F22->getPathValue('colour'), 'red');
}