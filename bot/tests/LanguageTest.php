<?php

require_once __DIR__ . '/../ChatbotEcho.php';

function testLanguage()
{
	$Echo = ChatbotEcho::getInstance();

	// tell Echo who it is that is currently speaking to him
	// for now: this is a different person every time
	$objectId = uniqid('user/', true);
	$Echo->addToWorkingMemory('context', 'speaker', $objectId);

$Sentence = $Echo->parseFirstLine('Boek die vlucht');
test(6, $Sentence->language, 'dutch');
test(7, $Sentence->getSyntaxString(), '[S [VP [verb boek][NP [determiner die][noun vlucht]]]]');
test(8, $Sentence->getStructure(), "imperative");
test(9, $Sentence->syntaxTree['features']['head']['agreement']['person'], 2);
test(10, $Sentence->syntaxTree['features']['head']['agreement']['number'], 's');
return;


	// S => VP ; parse sentences in two languages in the same line
	$sentences = $Echo->parse('Book that flight. Boek die vlucht');
	test(1, $sentences[0]->language, 'english');
	test(2, $sentences[0]->getSyntaxString(), '[S [VP [verb book][NP [determiner that][noun flight]]]]');
	test(3, $sentences[0]->getStructure(), "imperative");
	test(4, $sentences[0]->syntaxTree['features']['head']['agreement']['person'], 2);
	test(5, $sentences[0]->syntaxTree['features']['head']['agreement']['number'], 's');

#	test(4, $sentences[1]->language, 'dutch');
#	test(5, $sentences[1]->getSyntax(), '[S [VP [verb boek][NP [determiner die][noun vlucht]]]]');
#	test(6, $sentences[1]->getStructure(), "imperative");

	// agreement success
	$Sentence = $Echo->parseFirstLine('I am Patrick');
	test(101, $Sentence->getSyntaxString(), '[S [NP [pronoun i]][VP [verb am][NP [propernoun patrick]]]]');
	test(102, $Sentence->syntaxTree['features']['head']['agreement']['person'], 1);
	test(103, $Sentence->syntaxTree['features']['head']['agreement']['number'], 's');

	// agreement fail
	$Sentence = $Echo->parseFirstLine('I are Patrick');
	test(105, $Sentence, false);

	// S => WhNP VP ; referring expression "I" ; agreement feature
	$Sentence = $Echo->parseFirstLine('Who am I?');
	test(111, $Sentence->getSyntaxString(), '[S [WhNP [whword who]][VP [verb am][NP [pronoun i]]]]');
	test(112, $Sentence->getPhraseStructureString(), "[predicate: *identify, participants: [*identity: [referring-expression: *current-speaker, type: object], *patient: [question: *person, type: object]], type: clause, act: question-about-object]");
	test(113, $Sentence->getStructure(), "wh-subject-question");
	test(114, $Sentence->syntaxTree['features']['head']['agreement']['number'], 's');

	// S => aux NP VP ; DBPedia
	$answer = $Echo->answer("Was Lord Byron influenced by the author of Paradise Lost?");
	test(201, $answer, 'Yes.');
	$answer = $Echo->answer("Werd Lord Byron beïnvloed door de auteur van Paradise Lost?");
	test(202, $answer, 'Yes.');
	$Sentence = $Echo->parseFirstLine('Was Lord Byron influenced by the author of Paradise Lost?');
	test(203, $Sentence->syntaxTree['features']['head']['agreement']['number'], 's');

	// S => WhNP VP
	$answer = $Echo->answer("How many children did Lord Byron have?");
	test(211, $answer, '2');
	$answer = $Echo->answer("Hoeveel kinderen had Lord Byron?");
	test(212, $answer, '2');
	$Sentence = $Echo->parseFirstLine('How many children did Lord Byron have?');
	test(213, $Sentence->syntaxTree['features']['head']['agreement']['number'], 's');

	// S => WHNP aux NP VP
	$answer = $Echo->answer("Where was Lord Byron born?");
	test(221, $answer, 'Dover');
	$answer = $Echo->answer("Waar werd Lord Byron geboren?");
	test(222, $answer, 'Dover');

	// S => WHNP aux NP VP
	$answer = $Echo->answer("When was Lord Byron born?");
	test(231, $answer, '1788-01-22');
	$answer = $Echo->answer("Wanneer werd Lord Byron geboren?");
	test(232, $answer, '1788-01-22');
	$answer = $Echo->answer("Where did Lord Byron die?");
	test(233, $answer, 'Aetolia-Acarnania');

	// S => aux NP NP
	$answer = $Echo->answer("Was Ada Lovelace the daughter of Lord Byron?");
	test(241, $answer, 'Yes.');
	$answer = $Echo->answer("Was Ada Lovelace een dochter van Lord Byron?");
	test(242, $answer, 'Yes.');
	$Sentence = $Echo->parseFirstLine('Was Ada Lovelace the daughter of Lord Byron?');
	test(243, $Sentence->syntaxTree['features']['head']['agreement']['number'], 's');

// heeft waarschijnlijk nog nooit gewerkt
//	$Echo->tell(array($objectId, 'name', 'patrick'));
//	$answer = $Echo->ask(array($objectId, 'name', '?variable'));
//	test(121, $answer, 'patrick');
//	$answer = $Echo->answer('I am Patrick. Who am I?');
//	test(122, $answer, 'You are Patrick.');
}