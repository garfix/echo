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

$Echo = ChatbotEcho::getInstance();

// tell Echo who it is that is currently speaking to him
// for now: this is a different person every time
$objectId = uniqid('user/', true);
$Echo->addToWorkingMemory('context', 'speaker', $objectId);

# todo: Echo should respond with an English sentence
# Volgende stap: zorg ervoor dat de unit test loopt, door de microplanning en realisation uit te werken voor deze case.
# Het is niet nodig een generieke oplossing te bedenken, dat komt later.

#return;

$sentences = $Echo->parse('Book that flight. Boek die vlucht');
test(1, $sentences[0]->language, 'english');
test(2, $sentences[0]->getSyntax(), '[S [VP [verb book][NP [determiner that][noun flight]]]]');
test(5, $sentences[0]->getStructure(), "imperative");
test(3, $sentences[1]->language, 'dutch');
test(4, $sentences[1]->getSyntax(), '[S [VP [verb boek][NP [determiner die][noun vlucht]]]]');
test(5, $sentences[1]->getStructure(), "imperative");

$sentences = $Echo->parse('I am Patrick');
test(101, $sentences[0]->getSyntax(), '[S [NP [pronoun i]][VP [verb am][NP [proper-noun patrick]]]]');
test(102, $sentences[0]->getSemantics(), "[[$objectId name patrick]]");
test(103, $sentences[0]->getStructure(), "declarative");

$sentences = $Echo->parse('Who am I?');
test(111, $sentences[0]->getSyntax(), '[S [Wh-NP [wh-word who]][VP [verb am][NP [pronoun i]]]]');
test(112, $sentences[0]->getSemantics(), "[[$objectId name ?variable]]");
test(113, $sentences[0]->getStructure(), "wh-subject-question");

$Echo->tell(array($objectId, 'name', 'patrick'));
$answer = $Echo->ask(array($objectId, 'name', '?variable'));
test(121, $answer, 'patrick');
$answer = $Echo->answer('Who am I?');
test(122, $answer, 'You are Patrick.');
