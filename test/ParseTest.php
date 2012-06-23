<?php

namespace agentecho\test;

use \agentecho\AgentEcho;
use \agentecho\test\TestBase;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;
use \agentecho\exception\ParseException;

class ParseTest extends TestBase
{
	function execute()
	{
		$Echo = new AgentEcho();
		$Echo->addGrammar(new EnglishGrammar());
		$Echo->addGrammar(new DutchGrammar());

		$Conversation = $Echo->startConversation();

		// S => VP ; parse sentences in two languages in the same line
		$sentences = $Conversation->parse('Book that flight. Boek die vlucht');
		$this->test(101, $sentences[0]->language, 'english');
		$this->test(102, $sentences[0]->getSyntaxString(), '[S [VP [verb book][NP [DP [determiner that]][NBar [noun flight]]]]]');
		$this->test(103, $sentences[0]->getStructure(), "imperative");
		$this->test(104, $sentences[0]->phraseSpecification['features']['head']['agreement']['person'], 2);
		$this->test(105, $sentences[0]->phraseSpecification['features']['head']['agreement']['number'], 's');
		$this->test(106, $sentences[1]->language, 'dutch');
		$this->test(107, $sentences[1]->getSyntaxString(), '[S [VP [verb boek][NP [DP [determiner die]][NBar [noun vlucht]]]]]');
		$this->test(108, $sentences[1]->getStructure(), "imperative");
		$this->test(109, $sentences[1]->phraseSpecification['features']['head']['agreement']['person'], 2);
		$this->test(110, $sentences[1]->phraseSpecification['features']['head']['agreement']['number'], 's');

		// S => WhNP VP ; referring expression "I" ; agreement feature
		$Sentence = $Conversation->parseFirstLine('Who am I?');
		$this->test(111, $Sentence->getSyntaxString(), '[S [WhNP [whword who]][VP [verb am]][NP [pronoun i]]]');
		$this->test(112, $Sentence->getPhraseSpecificationString(), "[head: [agreement: [person: 1, number: s], sem: [predicate: *be, type: relation, arg2: [type: entity, question: 1], arg1: [category: *firstPerson, type: entity]], sentenceType: wh-question, voice: active]]");
		$this->test(113, $Sentence->getStructure(), "wh-question");
		$this->test(114, $Sentence->phraseSpecification['features']['head']['agreement']['number'], 's');

		$Sentence = $Conversation->parseFirstLine('Was Lord Byron influenced by the author of Paradise Lost?');
		$this->test(203, $Sentence->getSyntaxString(), '[S [aux was][NP [propernoun Lord Byron]][VP [verb influenced]][passivisationPreposition by][NP [DP [determiner the]][NBar [NBar [noun author]][PP [preposition of][NP [propernoun Paradise Lost]]]]]]');
		$this->test(204, $Sentence->phraseSpecification['features']['head']['agreement']['number'], 's');
		$this->test(205, $Sentence->phraseSpecification['features']['head']['sem']['predicate'], '*influence');
		$this->test(206, $Sentence->phraseSpecification['features']['head']['sem']['arg1']['modifier']['object']['name'], 'Paradise Lost');
		$this->test(207, $Sentence->phraseSpecification['features']['head']['sem']['arg1']['category'], '*author');
//r($Sentence->phraseSpecification);
		$this->test(208, $Sentence->phraseSpecification['features']['head']['sem']['arg1']['determiner']['category'], '*the');
		$this->test(209, $Sentence->phraseSpecification['features']['head']['sem']['arg2']['name'], 'Lord Byron');
		$this->test(210, $Sentence->phraseSpecification['features']['head']['sentenceType'], 'yes-no-question');

		$Sentence = $Conversation->parseFirstLine('How many children did Lord Byron have?');
		$this->test(213, $Sentence->phraseSpecification['features']['head']['agreement']['number'], 's');

		// S => NP VP
		$Sentence = $Conversation->parseFirstLine('John sees the book');
		$this->test(251, $Sentence->getSyntaxString(), '[S [NP [propernoun John]][VP [verb sees][NP [DP [determiner the]][NBar [noun book]]]]]');
		$this->test(255, $Sentence->getPhraseSpecificationString(), "[head: [tense: present, sem: [predicate: *see, type: relation, arg2: [category: *book, type: entity, determiner: [category: *the, type: determiner]], arg1: [name: John, type: entity]], sentenceType: declarative, voice: active, agreement: [number: s, person: 1]]]");

		// agreement success
		// S => NP VP
		$Sentence = $Conversation->parseFirstLine('I am Patrick');
		$this->test(261, $Sentence->getSyntaxString(), '[S [NP [pronoun i]][VP [verb am][NP [propernoun Patrick]]]]');
		$this->test(262, $Sentence->phraseSpecification['features']['head']['agreement']['person'], 1);
		$this->test(263, $Sentence->phraseSpecification['features']['head']['agreement']['number'], 's');

		// agreement fail
		$caught = false;
		try {
			$Conversation->parseFirstLine('I are Patrick');
		} catch (ParseException $E) {
			$caught = true;
		}
		$this->test(264, $caught, true);

		// proper error feedback
		$answer = $Conversation->answer('rwyrwur');
		$this->test(270, $answer, "Word not found: rwyrwur");

		$answer = $Conversation->answer('We rwyrwur born');
		$this->test(271, $answer, "Word not found: rwyrwur");

		// S => NP VP NP NP
		$S = $Conversation->parseFirstLine("John gives Mary flowers.");
		$this->test(280, $S->getPhraseSpecificationString(), '[head: [tense: present, sem: [predicate: *give, type: relation, arg2: [category: *flower, type: entity], arg3: [name: Mary, type: entity], arg1: [name: John, type: entity]], sentenceType: declarative, voice: active, agreement: [number: s, person: 1]]]');
	}
}