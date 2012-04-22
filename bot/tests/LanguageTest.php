<?php

require_once __DIR__ . '/../ChatbotEcho.php';
require_once __DIR__ . '/../knowledge_source/DBPedia.php';

function testLanguage()
{
	$Echo = new ChatbotEcho();
	$Echo->addKnowledgeSource(new DBPedia());
	// $Echo->addGrammar(new EnglishGrammar());
	// $Echo->addGrammar(new DutchGrammar());

if (true) {
	// S => VP ; parse sentences in two languages in the same line
	$sentences = $Echo->parse('Book that flight. Boek die vlucht');
	test(1, $sentences[0]->language, 'english');
	test(2, $sentences[0]->getSyntaxString(), '[S [VP [verb book][NP [determiner that][noun flight]]]]');
	test(3, $sentences[0]->getStructure(), "imperative");
	test(4, $sentences[0]->phraseStructure['features']['head']['agreement']['person'], 2);
	test(5, $sentences[0]->phraseStructure['features']['head']['agreement']['number'], 's');
	test(6, $sentences[1]->language, 'dutch');
	test(7, $sentences[1]->getSyntaxString(), '[S [VP [verb boek][NP [determiner die][noun vlucht]]]]');
	test(8, $sentences[1]->getStructure(), "imperative");
	test(9, $sentences[1]->phraseStructure['features']['head']['agreement']['person'], 2);
	test(10, $sentences[1]->phraseStructure['features']['head']['agreement']['number'], 's');

	// agreement success
	// S => NP VP
	$Sentence = $Echo->parseFirstLine('I am Patrick');
	test(101, $Sentence->getSyntaxString(), '[S [NP [pronoun i]][VP [verb am][NP [propernoun Patrick]]]]');
	test(102, $Sentence->phraseStructure['features']['head']['agreement']['person'], 1);
	test(103, $Sentence->phraseStructure['features']['head']['agreement']['number'], 's');

	// agreement fail
	$Sentence = $Echo->parseFirstLine('I are Patrick');
	test(105, $Sentence, false);

	// S => WhNP VP ; referring expression "I" ; agreement feature
	$Sentence = $Echo->parseFirstLine('Who am I?');
	test(111, $Sentence->getSyntaxString(), '[S [WhNP [whword who]][VP [verb am]][NP [pronoun i]]]');
	test(112, $Sentence->getPhraseStructureString(), "[head: [agreement: [person: 1, number: s], sem: [predicate: *be, arg2: [question: 1], arg1: [category: *firstPerson]], sentenceType: wh-non-subject-question]]");
	test(113, $Sentence->getStructure(), "wh-non-subject-question");
	test(114, $Sentence->phraseStructure['features']['head']['agreement']['number'], 's');

	// S => aux NP VP ; DBPedia
	$answer = $Echo->answer("Was Lord Byron influenced by the author of Paradise Lost?");
	test(201, $answer, 'Yes. Lord Byron was influenced by the author of Paradise Lost.');
	$answer = $Echo->answer("Werd Lord Byron beïnvloed door de auteur van Paradise Lost?");
	test(202, $answer, 'Yes.');

	$Sentence = $Echo->parseFirstLine('Was Lord Byron influenced by the author of Paradise Lost?');
	test(203, $Sentence->getSyntaxString(), '[S [aux was][NP [propernoun Lord Byron]][VP [verb influenced]][passivisationPreposition by][NP [NP [determiner the][noun author]][PP [preposition of][NP [propernoun Paradise Lost]]]]]');
	test(204, $Sentence->phraseStructure['features']['head']['agreement']['number'], 's');
	test(205, $Sentence->phraseStructure['features']['head']['sem']['predicate'], '*influence');
	test(206, $Sentence->phraseStructure['features']['head']['sem']['arg1']['modifier']['object']['name'], 'Paradise Lost');
	test(207, $Sentence->phraseStructure['features']['head']['sem']['arg1']['category'], '*author');
	test(208, $Sentence->phraseStructure['features']['head']['sem']['arg1']['determiner'], '*the');
	test(209, $Sentence->phraseStructure['features']['head']['sem']['arg2']['name'], 'Lord Byron');
	test(210, $Sentence->phraseStructure['features']['head']['sentenceType'], 'yes-no-question');
}
	// S => WhNP aux NP VP
	$answer = $Echo->answer("How many children did Lord Byron have?");
	test(211, $answer, 'Lord Byron had 2 children.');
//return;
	$answer = $Echo->answer("Hoeveel kinderen had Lord Byron?");
	test(212, $answer, '2');
	$Sentence = $Echo->parseFirstLine('How many children did Lord Byron have?');
	test(213, $Sentence->phraseStructure['features']['head']['agreement']['number'], 's');

	// S => WhNP aux NP VP
	$answer = $Echo->answer("Where was Lord Byron born?");
	test(221, $answer, 'Dover');
	$answer = $Echo->answer("Waar werd Lord Byron geboren?");
	test(222, $answer, 'Dover');

	// S => WhNP aux NP VP
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
	test(243, $Sentence->phraseStructure['features']['head']['agreement']['number'], 's');

	// S => NP VP
	$Sentence = $Echo->parseFirstLine('John sees the book');
	test(251, $Sentence->getSyntaxString(), '[S [NP [propernoun John]][VP [verb sees][NP [determiner the][noun book]]]]');
	test(255, $Sentence->getPhraseStructureString(), "[head: [tense: present, sem: [predicate: *see, arg2: [category: *book, determiner: *the], arg1: [name: John]], sentenceType: declarative, voice: active, agreement: [number: s, person: 1]]]");
}