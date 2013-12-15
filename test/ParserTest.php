<?php

namespace agentecho\test;

require_once __DIR__ . '/../Autoload.php';

use agentecho\component\GrammarFactory;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;
use \agentecho\exception\ParseException;
use \agentecho\component\Parser;

/**
 * This is suite of tests that tests the intermediate representation of parsed sentences.
 * It tests the SentenceContext.
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
	function test()
	{
		$Dutch = GrammarFactory::getGrammar('nl');
		$English = GrammarFactory::getGrammar('en');

		$Parser = new Parser();
		$Parser->setGrammars(array($Dutch, $English));
		$Parser->setCurrentGrammar($English);

		// S => VP ; parse sentences in two languages in the same line
		$sentences = $Parser->parse('Book that flight. Boek die vlucht');
		$this->assertSame('english', $sentences[0]->getLanguage());
		$this->assertSame('[S [VP [verb book][NP [DP [determiner that]][NBar [noun flight]]]]]', $sentences[0]->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: imperative, Clause: Clause {predicate: book, DeepDirectObject: Entity {category: flight, Determiner: Determiner {category: that, question: }, number: singular}, tense: present}, voice: active}', $sentences[0]->getObjectString());
		$this->assertSame('dutch', $sentences[1]->getLanguage());
		$this->assertSame('[S [VP [verb boek][NP [DP [determiner die]][NBar [noun vlucht]]]]]', $sentences[1]->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: imperative, Clause: Clause {predicate: book, DeepDirectObject: Entity {category: flight, Determiner: Determiner {category: that, question: }, number: singular}, tense: present}, voice: active}', $sentences[1]->getObjectString());

		// S => WhNP VP ; referring expression "I" ; agreement feature
		$Sentence = $Parser->parseFirstLine('Who am I?');
		$this->assertSame('[S [WhADVP [whAdverb who]][VP [verb am]][NP [pronoun i]]]', $Sentence->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: wh-question, Clause: Clause {predicate: be, DeepSubject: Entity {category: firstPerson, number: singular}, tense: present}, voice: active}', $Sentence->getObjectString());

		$Sentence = $Parser->parseFirstLine('Was Lord Byron influenced by the author of Paradise Lost?');
		$this->assertSame('[S [aux was][NP [PN [propernoun Lord][propernoun Byron]]][VP [verb influenced]][passivisationPreposition by][NP [DP [determiner the]][NBar [NBar [noun author]][PP [preposition of][NP [PN [propernoun Paradise][propernoun Lost]]]]]]]', $Sentence->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: yes-no-question, Clause: Clause {predicate: influence, DeepSubject: Entity {category: author, Determiner: Determiner {category: the, question: }, number: singular, Preposition: Preposition {category: of, Object: Entity {name: Paradise, lastname: Lost, number: singular}}}, DeepDirectObject: Entity {name: Lord, lastname: Byron, number: singular}, tense: past}, voice: passive}', $Sentence->getObjectString());

		$Sentence = $Parser->parseFirstLine('How many children did Lord Byron have?');
		$this->assertSame('Sentence {sentenceType: wh-question, Clause: Clause {predicate: have, DeepSubject: Entity {name: Lord, lastname: Byron, number: singular}, DeepDirectObject: Entity {category: child, Determiner: Determiner {category: many, question: 1}, number: singular}, tense: past}, voice: active}', $Sentence->getObjectString());

		// S => NP VP
		$Sentence = $Parser->parseFirstLine('John sees the book');
		$this->assertSame('[S [NP [PN [propernoun John]]][VP [verb sees][NP [DP [determiner the]][NBar [noun book]]]]]', $Sentence->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: declarative, Clause: Clause {predicate: see, DeepSubject: Entity {name: John, number: singular}, DeepDirectObject: Entity {category: book, Determiner: Determiner {category: the, question: }, number: singular}, tense: present}, voice: active}', $Sentence->getObjectString());

		// agreement success
		// S => NP VP
		$Sentence = $Parser->parseFirstLine('I am Patrick');
		$this->assertSame('[S [NP [pronoun i]][VP [verb am][NP [PN [propernoun Patrick]]]]]', $Sentence->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: declarative, Clause: Clause {predicate: be, DeepSubject: Entity {category: firstPerson, number: singular}, DeepDirectObject: Entity {name: Patrick, number: singular}, tense: present}, voice: active}', $Sentence->getObjectString());

		// agreement fail
		$caught = false;
		try {
			$Parser->parseFirstLine('I are Patrick');
		} catch (ParseException $E) {
			$caught = true;
		}
		$this->assertSame(true, $caught);

		// NBar -> AP NBar
		$Sentence = $Parser->parseFirstLine('John reads the red book');
		$this->assertSame('[S [NP [PN [propernoun John]]][VP [verb reads][NP [DP [determiner the]][NBar [AP [adjective red]][NBar [noun book]]]]]]', $Sentence->getSyntaxString());

		// NBar -> AP NBar -> (AP NBar) PP
		$Sentence = $Parser->parseFirstLine('John reads the red book in bed');
		$this->assertSame('[S [NP [PN [propernoun John]]][VP [verb reads][NP [DP [determiner the]][NBar [NBar [AP [adjective red]][NBar [noun book]]][PP [preposition in][NP [NBar [noun bed]]]]]]]]', $Sentence->getSyntaxString());

		// S => NP VP NP NP
		$Sentence = $Parser->parseFirstLine("John gives Mary flowers.");
		$this->assertSame('[S [NP [PN [propernoun John]]][VP [verb gives][NP [PN [propernoun Mary]]][NP [NBar [noun flowers]]]]]', $Sentence->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: declarative, Clause: Clause {predicate: give, DeepSubject: Entity {name: John, number: singular}, DeepDirectObject: Entity {category: flower, number: singular}, DeepIndirectObject: Entity {name: Mary, number: singular}, tense: present}, voice: active}', $Sentence->getObjectString());
	}

	public function testParseDegreeAdverb()
	{
		$Parser = new Parser();
		$Parser->setCurrentGrammar(GrammarFactory::getGrammar('en'));

		// AP -> degreeAdverb adjective
		$Sentence = $Parser->parseFirstLine('John reads the bright red book');
		$this->assertSame('[S [NP [PN [propernoun John]]][VP [verb reads][NP [DP [determiner the]][NBar [AP [degreeAdverb bright][adjective red]][NBar [noun book]]]]]]', $Sentence->getSyntaxString());
	}

	public function testParseAdverbialPhrase()
	{
		$Parser = new Parser();
		$Parser->setCurrentGrammar(GrammarFactory::getGrammar('en'));

		// examples from "The structure of modern english" (p. 173)

		// AdvP -> adverb
		$Sentence = $Parser->parseFirstLine('John calms the fiercely barking dog');
		$this->assertSame('[S [NP [PN [propernoun John]]][VP [verb calms][NP [DP [determiner the]][NBar [AP [AdvP [adverb fiercely]][adjective barking]][NBar [noun dog]]]]]]', $Sentence->getSyntaxString());

		// AdvP -> degreeAdverb adverb
		$Sentence = $Parser->parseFirstLine('John calms the very fiercely barking dog');
		$this->assertSame('[S [NP [PN [propernoun John]]][VP [verb calms][NP [DP [determiner the]][NBar [AP [AdvP [degreeAdverb very][adverb fiercely]][adjective barking]][NBar [noun dog]]]]]]', $Sentence->getSyntaxString());
	}

	public function testCalculatedAnswerSemantics()
	{
		$Parser = new Parser();
		$Parser->setGrammars(array(GrammarFactory::getGrammar('en')));

		// secondary sentence
		//$Sentence = $Parser->parseFirstLine("How old was Mary Shelley when Lady Lovelace was born?");

		$Sentence = $Parser->parseFirstLine("How old was Mary Shelley when she died?");
		$this->assertSame('[S [S [WhADVP [whwordNP how][AP [adjective old]]][auxBe was][NP [PN [propernoun Mary][propernoun Shelley]]]][SBar [whAdverb when][S [NP [pronoun she]][VP [verb died]]]]]', $Sentence->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: wh-question, Clause: Clause {predicate: be, DeepSubject: Entity {name: Mary, lastname: Shelley, number: singular}, DeepDirectObject: Entity {category: old, Determiner: Determiner {question: 1}, number: singular}, tense: past}, voice: active, RelativeClause: RelativeClause {complementizer: when, Clause: Clause {predicate: die, DeepSubject: Entity {category: subject, number: singular}, tense: past}}}', $Sentence->getObjectString());
		$this->assertSame('manner(S1.event, S1.request) and isa(S1.event, Old) and tense(S1.event, Past) and name(S1.subject, "Mary Shelley") and subject(S1.event, S1.subject) and request(S1.request) and at_time(S1.event, S1_SBar.subEvent) and isa(S1_SBar_S.subject, Female) and reference(S1_SBar_S.subject) and isa(S1_SBar.subEvent, Die) and subject(S1_SBar.subEvent, S1_SBar_S.subject) and object(S1_SBar.subEvent, S1_SBar_S.object)', $Sentence->getSemanticsString());
	}

	public function testParseNumeral()
	{
		$Parser = new Parser();
		$Parser->setGrammars(array(GrammarFactory::getGrammar('nl')));

		$Sentence = $Parser->parseFirstLine("Ik ben 43 jaar oud.");
		$this->assertSame('[S [NP [pronoun ik]][VP [copula ben][AP [NP [DP [numeral 43]][NBar [noun jaar]]][adjective oud]]]]', $Sentence->getSyntaxString());
		$this->assertSame('firstPerson(S.subject) and determiner(S.object, "43") and isa(S.object, Year) and isa(S.object, Old) and subject(S.event, S.subject) and object(S.event, S.object)', $Sentence->getSemanticsString());

		$Parser->setGrammars(array(GrammarFactory::getGrammar('en')));

		$Sentence = $Parser->parseFirstLine("I am 43 years old.");
		$this->assertSame('[S [NP [pronoun i]][VP [copula am][AP [NP [DP [numeral 43]][NBar [noun years]]][adjective old]]]]', $Sentence->getSyntaxString());
		$this->assertSame('firstPerson(S.subject) and determiner(S.object, "43") and isa(S.object, Year) and isa(S.object, Old) and subject(S.event, S.subject) and object(S.event, S.object)', $Sentence->getSemanticsString());
	}

	public function testParseQuotes()
	{
		$Parser = new Parser();
		$Parser->setGrammars(array(GrammarFactory::getGrammar('en')));

		$Sentence = $Parser->parseFirstLine('I am "Patrick (Garfix) van Bergen"');
		$this->assertSame('[S [NP [pronoun i]][VP [verb am][NP [PN [propernoun Patrick (Garfix) van Bergen]]]]]', $Sentence->getSyntaxString());
	}
}