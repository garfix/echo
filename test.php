#!/usr/bin/php
<?php

require_once __DIR__ . '/bot/ChatbotEcho.php';

function test($case, $got, $expected) {
	if ($expected !== $got) {
		echo 'Test failed: ' . $case . "\n";
		echo "Expected: " . print_r($expected, true) . "\n";
		echo "got:      " . print_r($got, true) . "\n\n";
	}
}

function testNamedDAG()
{
	require_once __DIR__ . '/bot/language/EarleyParser.php';

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
}

function testAll($Echo)
{
	testNamedDAG();

	// tell Echo who it is that is currently speaking to him
	// for now: this is a different person every time
	$objectId = uniqid('user/', true);
	$Echo->addToWorkingMemory('context', 'speaker', $objectId);

	$sentences = $Echo->parse('Book that flight. Boek die vlucht');
	test(1, $sentences[0]->language, 'english');
	test(2, $sentences[0]->getSyntax(), '[S [VP [verb book][NP [determiner that][noun flight]]]]');
	test(3, $sentences[0]->getStructure(), "imperative");
#	test(4, $sentences[1]->language, 'dutch');
#	test(5, $sentences[1]->getSyntax(), '[S [VP [verb boek][NP [determiner die][noun vlucht]]]]');
#	test(6, $sentences[1]->getStructure(), "imperative");

	$sentences = $Echo->parse('Who am I?');
	test(111, $sentences[0]->getSyntax(), '[S [Wh-NP [whword who]][VP [verb am][NP [pronoun i]]]]');
	test(112, $sentences[0]->getPhraseStructure(), "[predicate: *identify, participants: [*identity: [referring-expression: *current-speaker, type: object], *patient: [question: *person, type: object]], type: clause, act: question-about-object]");
	test(113, $sentences[0]->getStructure(), "wh-subject-question");

	$answer = $Echo->answer("Was Lord Byron influenced by the author of Paradise Lost?");
	test(201, $answer, 'Yes.');
	$answer = $Echo->answer("Werd Lord Byron beïnvloed door de auteur van Paradise Lost?");
	test(202, $answer, 'Yes.');

	$answer = $Echo->answer("How many children did Lord Byron have?");
	test(203, $answer, 2);
	$answer = $Echo->answer("Hoeveel kinderen had Lord Byron?");
	test(204, $answer, 2);

	$answer = $Echo->answer("Where was Lord Byron born?");
	test(205, $answer, 'England');
	$answer = $Echo->answer("Waar werd Lord Byron geboren?");
	test(206, $answer, 'England');

	$answer = $Echo->answer("When was Lord Byron born?");
	test(207, $answer, '1788-01-22');
	$answer = $Echo->answer("Wanneer werd Lord Byron geboren?");
	test(208, $answer, '1788-01-22');

	$answer = $Echo->answer("Was Ada Lovelace the daughter of Lord Byron?");
	test(209, $answer, 'Yes.');
	$answer = $Echo->answer("Was Ada Lovelace een dochter van Lord Byron?");
	test(210, $answer, 'Yes.');
}

$Echo = ChatbotEcho::getInstance();

//	testAll($Echo);

testNamedDAG();
return;
# should fail: I are Patrick

#$sentences = $Echo->parse('I am Patrick');
$sentences = $Echo->parse('I are Patrick');
test(101, $sentences[0]->getSyntax(), '[S [NP [pronoun i]][VP [verb am][NP [proper-noun patrick]]]]');
//test(102, $sentences[0]->getPhraseStructure(), "[predicate: *identify, participants: [*identity: [type: object, name: patrick], *actor: [referring-expression: *current-speaker, type: object]], type: clause]");
//test(103, $sentences[0]->getStructure(), "declarative");




//	$sentences = $Echo->parse('I am Patrick');

$objectId = uniqid('user/', true);

//	$answer = $Echo->answer("Where did Lord Byron die?");
//	test(209, $answer, 'Aetolia-Acarnania');


//	$Echo->tell(array($objectId, 'name', 'patrick'));
//	$answer = $Echo->ask(array($objectId, 'name', '?variable'));
//	test(121, $answer, 'patrick');
//	$answer = $Echo->answer('I am Patrick. Who am I?');
//	test(122, $answer, 'You are Patrick.');

// These links may help:

// Stanford parser
// http://nlp.stanford.edu:8080/parser/index.jsp

// Grammar terms
// http://www.chompchomp.com/terms.htm
