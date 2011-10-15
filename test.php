#!/usr/bin/php
<?php

require_once __DIR__ . '/bot/ChatbotEcho.php';

function test($case, $got, $expected) {
	if ($expected != $got) {
		echo 'Test failed: ' . $case . "\n";
		echo "Expected: " . print_r($expected, true) . "\n";
		echo "got:      " . print_r($got, true) . "\n\n";
	}
}

function testAll($Echo)
{
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
	test(111, $sentences[0]->getSyntax(), '[S [Wh-NP [wh-word who]][VP [verb am][NP [pronoun i]]]]');
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

$sentences = $Echo->parse('I am Patrick');
test(101, $sentences[0]->getSyntax(), '[S [NP [pronoun i]][VP [verb am][NP [proper-noun patrick]]]]');
test(102, $sentences[0]->getPhraseStructure(), "[predicate: *identify, participants: [*identity: [type: object, name: patrick], *actor: [referring-expression: *current-speaker, type: object]], type: clause]");
test(103, $sentences[0]->getStructure(), "declarative");




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
