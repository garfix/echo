<?php

namespace agentecho\test;

use \agentecho\AgentEcho;
use \agentecho\test\TestBase;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;
use \agentecho\exception\ParseException;

/**
 * This is suite of tests that tests the intermediate representation of parsed sentences.
 * It tests the SentenceContext.
 */
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
		$this->test(105, $sentences[0]->phraseSpecification['features']['head']['agreement']['number'], 'singular');
		$this->test(106, $sentences[1]->language, 'dutch');
		$this->test(107, $sentences[1]->getSyntaxString(), '[S [VP [verb boek][NP [DP [determiner die]][NBar [noun vlucht]]]]]');
		$this->test(108, $sentences[1]->getStructure(), "imperative");
		$this->test(109, $sentences[1]->phraseSpecification['features']['head']['agreement']['person'], 2);
		$this->test(110, $sentences[1]->phraseSpecification['features']['head']['agreement']['number'], 'singular');

		// S => WhNP VP ; referring expression "I" ; agreement feature
		$Sentence = $Conversation->parseFirstLine('Who am I?');
		$this->test(111, $Sentence->getSyntaxString(), '[S [WhNP [whword who]][VP [verb am]][NP [pronoun i]]]');
		$this->test(112, $Sentence->getObjectString(), 'Sentence {sentenceType: wh-question, Relation: Relation {predicate: be, Arg1: Entity {category: firstPerson, number: singular}, Arg2: Entity {number: singular, question: 1}, tense: present}, voice: active}');
		$this->test(113, $Sentence->getStructure(), "wh-question");
		$this->test(114, $Sentence->phraseSpecification['features']['head']['agreement']['number'], 'singular');

		$Sentence = $Conversation->parseFirstLine('Was Lord Byron influenced by the author of Paradise Lost?');
		$this->test(203, $Sentence->getSyntaxString(), '[S [aux was][NP [propernoun Lord Byron]][VP [verb influenced]][passivisationPreposition by][NP [DP [determiner the]][NBar [NBar [noun author]][PP [preposition of][NP [propernoun Paradise Lost]]]]]]');
		$this->test(204, $Sentence->getObjectString(), 'Sentence {sentenceType: yes-no-question, Relation: Relation {predicate: influence, Arg1: Entity {category: author, Determiner: Determiner {category: the, question: }, Preposition: Preposition {category: of, Object: Entity {name: Paradise Lost, number: singular}}, number: singular}, Arg2: Entity {name: Lord Byron, number: singular}, tense: past}, voice: passive}');

		$Sentence = $Conversation->parseFirstLine('How many children did Lord Byron have?');
		$this->test(213, $Sentence->getObjectString(), 'Sentence {sentenceType: wh-question, Relation: Relation {predicate: have, Arg1: Entity {name: Lord Byron, number: singular}, Arg2: Entity {category: child, Determiner: Determiner {category: many, question: 1}, number: singular}, tense: past}, voice: active}');

		// S => NP VP
		$Sentence = $Conversation->parseFirstLine('John sees the book');
		$this->test(251, $Sentence->getSyntaxString(), '[S [NP [propernoun John]][VP [verb sees][NP [DP [determiner the]][NBar [noun book]]]]]');
		$this->test(252, $Sentence->getObjectString(), 'Sentence {sentenceType: declarative, Relation: Relation {predicate: see, Arg1: Entity {name: John, number: singular}, Arg2: Entity {category: book, Determiner: Determiner {category: the, question: }, number: singular}, tense: present}, voice: active}');

		// agreement success
		// S => NP VP
		$Sentence = $Conversation->parseFirstLine('I am Patrick');
		$this->test(261, $Sentence->getSyntaxString(), '[S [NP [pronoun i]][VP [verb am][NP [propernoun Patrick]]]]');
		$this->test(262, $Sentence->getObjectString(), 'Sentence {sentenceType: declarative, Relation: Relation {predicate: be, Arg1: Entity {category: firstPerson, number: singular}, Arg2: Entity {name: Patrick, number: singular}, tense: present}, voice: active}');

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
		$Sentence = $Conversation->parseFirstLine("John gives Mary flowers.");
		$this->test(281, $Sentence->getObjectString(), 'Sentence {sentenceType: declarative, Relation: Relation {predicate: give, Arg1: Entity {name: John, number: singular}, Arg2: Entity {category: flower, number: singular}, Arg3: Entity {name: Mary, number: singular}, tense: present}, voice: active}');
	}
}