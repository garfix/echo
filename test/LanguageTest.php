<?php

namespace agentecho\test;

use \agentecho\AgentEcho;
use \agentecho\test\Test;
use \agentecho\knowledge\KnowledgeSource;
use \agentecho\knowledge\DBPedia;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;

class LanguageTest extends Test
{
	function execute()
	{
		// Lord Byron facts
		//
		// http://dbpedia.org/page/Lord_Byron

		$Echo = new AgentEcho();
		$Echo->addKnowledgeSource(new DBPedia());
		$Echo->addGrammar(new EnglishGrammar());
		$Echo->addGrammar(new DutchGrammar());

		$Conversation = $Echo->startConversation();

		/**
		 * Parsing
		 */

	$single = 0;

	if (!$single) {
		// S => VP ; parse sentences in two languages in the same line
		$sentences = $Conversation->parse('Book that flight. Boek die vlucht');
		$this->test(1, $sentences[0]->language, 'english');
		$this->test(2, $sentences[0]->getSyntaxString(), '[S [VP [verb book][NP [determiner that][noun flight]]]]');
		$this->test(3, $sentences[0]->getStructure(), "imperative");
		$this->test(4, $sentences[0]->phraseSpecification['features']['head']['agreement']['person'], 2);
		$this->test(5, $sentences[0]->phraseSpecification['features']['head']['agreement']['number'], 's');
		$this->test(6, $sentences[1]->language, 'dutch');
		$this->test(7, $sentences[1]->getSyntaxString(), '[S [VP [verb boek][NP [determiner die][noun vlucht]]]]');
		$this->test(8, $sentences[1]->getStructure(), "imperative");
		$this->test(9, $sentences[1]->phraseSpecification['features']['head']['agreement']['person'], 2);
		$this->test(10, $sentences[1]->phraseSpecification['features']['head']['agreement']['number'], 's');

		// agreement success
		// S => NP VP
		$Sentence = $Conversation->parseFirstLine('I am Patrick');
		$this->test(101, $Sentence->getSyntaxString(), '[S [NP [pronoun i]][VP [verb am][NP [propernoun Patrick]]]]');
		$this->test(102, $Sentence->phraseSpecification['features']['head']['agreement']['person'], 1);
		$this->test(103, $Sentence->phraseSpecification['features']['head']['agreement']['number'], 's');

		// agreement fail
		$caught = false;
		try {
			$Conversation->parseFirstLine('I are Patrick');
		} catch (\Exception $E) {
			$caught = true;
		}
		$this->test(105, $caught, true);

		// S => WhNP VP ; referring expression "I" ; agreement feature
		$Sentence = $Conversation->parseFirstLine('Who am I?');
		$this->test(111, $Sentence->getSyntaxString(), '[S [WhNP [whword who]][VP [verb am]][NP [pronoun i]]]');
		$this->test(112, $Sentence->getPhraseSpecificationString(), "[head: [agreement: [person: 1, number: s], sem: [predicate: *be, arg2: [question: 1], arg1: [category: *firstPerson]], sentenceType: wh-non-subject-question, voice: active]]");
		$this->test(113, $Sentence->getStructure(), "wh-non-subject-question");
		$this->test(114, $Sentence->phraseSpecification['features']['head']['agreement']['number'], 's');

		$Sentence = $Conversation->parseFirstLine('Was Lord Byron influenced by the author of Paradise Lost?');
		$this->test(203, $Sentence->getSyntaxString(), '[S [aux was][NP [propernoun Lord Byron]][VP [verb influenced]][passivisationPreposition by][NP [NP [determiner the][noun author]][PP [preposition of][NP [propernoun Paradise Lost]]]]]');
		$this->test(204, $Sentence->phraseSpecification['features']['head']['agreement']['number'], 's');
		$this->test(205, $Sentence->phraseSpecification['features']['head']['sem']['predicate'], '*influence');
		$this->test(206, $Sentence->phraseSpecification['features']['head']['sem']['arg1']['modifier']['object']['name'], 'Paradise Lost');
		$this->test(207, $Sentence->phraseSpecification['features']['head']['sem']['arg1']['category'], '*author');
		$this->test(208, $Sentence->phraseSpecification['features']['head']['sem']['arg1']['determiner'], '*the');
		$this->test(209, $Sentence->phraseSpecification['features']['head']['sem']['arg2']['name'], 'Lord Byron');
		$this->test(210, $Sentence->phraseSpecification['features']['head']['sentenceType'], 'yes-no-question');

		// S => NP VP
		$Sentence = $Conversation->parseFirstLine('John sees the book');
		$this->test(251, $Sentence->getSyntaxString(), '[S [NP [propernoun John]][VP [verb sees][NP [determiner the][noun book]]]]');
		$this->test(255, $Sentence->getPhraseSpecificationString(), "[head: [tense: present, sem: [predicate: *see, arg2: [category: *book, determiner: *the], arg1: [name: John]], sentenceType: declarative, voice: active, agreement: [number: s, person: 1]]]");

		/**
		 * Question answering
		 */


		// S => aux NP VP ; DBPedia
		$answer = $Conversation->answer("Was Lord Byron influenced by the author of Paradise Lost?");
		$this->test(201, $answer, 'Yes. Lord Byron was influenced by the author of Paradise Lost.');

		$answer = $Conversation->answer("Werd Lord Byron beïnvloed door de auteur van Paradise Lost?");
		$this->test(202, $answer, 'Yes.');

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("How many children did Lord Byron have?");
		$this->test(211, $answer, 'Lord Byron had 2 children.');
		$answer = $Conversation->answer("Hoeveel kinderen had Lord Byron?");
		$this->test(212, $answer, 'Lord Byron had 2 kinderen.');

		$Sentence = $Conversation->parseFirstLine('How many children did Lord Byron have?');
		$this->test(213, $Sentence->phraseSpecification['features']['head']['agreement']['number'], 's');

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("Where was Lord Byron born?");
		$this->test(221, $answer, 'Dover');
		$answer = $Conversation->answer("Waar werd Lord Byron geboren?");
		$this->test(222, $answer, 'Dover');

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("When was Lord Byron born?");
		$this->test(231, $answer, '1788-01-22');
		$answer = $Conversation->answer("Wanneer werd Lord Byron geboren?");
		$this->test(232, $answer, '1788-01-22');
		$answer = $Conversation->answer("Where did Lord Byron die?");
		$this->test(233, $answer, 'Aetolia-Acarnania');

		// S => aux NP NP
		$answer = $Conversation->answer("Was Ada Lovelace the daughter of Lord Byron?");
		$this->test(241, $answer, 'Yes.');
		$answer = $Conversation->answer("Was Ada Lovelace een dochter van Lord Byron?");
		$this->test(242, $answer, 'Yes.');
		$Sentence = $Conversation->parseFirstLine('Was Ada Lovelace the daughter of Lord Byron?');
		$this->test(243, $Sentence->phraseSpecification['features']['head']['agreement']['number'], 's');
	}

		// S => VP
		// http://www.grammarbook.com/punctuation/apostro.asp
		$answer = $Conversation->answer("Name Lord Byron's children");
		$this->test(251, $answer, "Ada Lovelace and Allegra Byron");

	//	$answer = $Conversation->answer("Noem Lord Byron kinderen");
	//	$this->test(252, $answer, "Ada Lovelace en Allegra Byron");
	}
}