#!/usr/bin/php
<?php

require_once __DIR__ . '/bot/tests/LabeledDagTest.php';
require_once __DIR__ . '/bot/tests/LanguageTest.php';

function test($case, $got, $expected) {
	if ($expected !== $got) {
		echo 'Test failed: ' . $case . "\n";
		echo "Expected: " . print_r($expected, true) . "\n";
		echo "got:      " . print_r($got, true) . "\n\n";
	}
}

#testLabeledDAG();
#testLanguage();
#return;

# should fail: I are Patrick

$Echo = ChatbotEcho::getInstance();

$sentences = $Echo->parse('I am Patrick');
test(101, $sentences[0]->getSyntax(), '[S [NP [pronoun i]][VP [verb am][NP [propernoun patrick]]]]');

$sentences = $Echo->parse('I are Patrick');
test(102, $sentences, array());





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
