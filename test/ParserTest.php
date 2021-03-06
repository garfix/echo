<?php

namespace agentecho\test;

require_once __DIR__ . '/../Autoload.php';

use agentecho\component\Conversation;
use agentecho\component\GrammarFactory;
use agentecho\component\KnowledgeManager;
use agentecho\component\Parser;
use agentecho\component\SentenceProcessor;

/**
 * This is suite of tests that tests the intermediate representation of parsed sentences.
 * It tests the SentenceInformation.
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
	function testTwoSentencesInTwoDifferentLanguages()
	{
		$Dutch = GrammarFactory::getGrammar('nl');
		$English = GrammarFactory::getGrammar('en');

		$Processor = new SentenceProcessor();

		$Conversation = new Conversation([$Dutch, $English]);
		$Conversation->setCurrentGrammar($English);

		// S => VP ; parse sentences in two languages in the same line
		$sentences = $Processor->parse('Book that flight. Boek die vlucht', $Conversation);
		$this->assertSame('english', $sentences[0]->getLanguage());
		$this->assertSame('[S [Clause [VP [verb book][NP [DP [determiner that]][NBar [noun flight]]]]]]', $sentences[0]->getSyntaxString());
		$this->assertSame('dutch', $sentences[1]->getLanguage());
		$this->assertSame('[S [Clause [VP [verb boek][NP [DP [determiner die]][NBar [noun vlucht]]]]]]', $sentences[1]->getSyntaxString());
	}

	function testSyntaxStrings()
	{
		$English = GrammarFactory::getGrammar('en');

		$Processor = new SentenceProcessor();

		// S => WhNP VP ; referring expression "I" ; agreement feature
		$Sentence = $Processor->parseSentence('Who am I?', $English);
		$this->assertSame('[S [Clause [WhADVP [whAdverb who]][VP [verb am]][NP [pronoun i]]]]', $Sentence->getSyntaxString());

		$Sentence = $Processor->parseSentence('Was Lord Byron influenced by the author of Paradise Lost?', $English);
		$this->assertSame('[S [Clause [aux was][NP [PN [propernoun Lord][propernoun Byron]]][VP [verb influenced]][passivisationPreposition by][NP [DP [determiner the]][NBar [NBar [noun author]][PP [preposition of][NP [PN [propernoun Paradise][propernoun Lost]]]]]]]]', $Sentence->getSyntaxString());

		// S => NP VP
		$Sentence = $Processor->parseSentence('John sees the book', $English);
		$this->assertSame('[S [Clause [NP [PN [propernoun John]]][VP [verb sees][NP [DP [determiner the]][NBar [noun book]]]]]]', $Sentence->getSyntaxString());

		// S => NP VP
		$Sentence = $Processor->parseSentence('I am Patrick', $English);
		$this->assertSame('[S [Clause [NP [pronoun i]][VP [verb am][NP [PN [propernoun Patrick]]]]]]', $Sentence->getSyntaxString());

		// NBar -> AdjP NBar
		$Sentence = $Processor->parseSentence('John reads the red book', $English);
		$this->assertSame('[S [Clause [NP [PN [propernoun John]]][VP [verb reads][NP [DP [determiner the]][NBar [AdjP [adjective red]][NBar [noun book]]]]]]]', $Sentence->getSyntaxString());

		// NBar -> AdjP NBar -> (AdjP NBar) PP
		$Sentence = $Processor->parseSentence('John reads the red book in bed', $English);
		$this->assertSame('[S [Clause [NP [PN [propernoun John]]][VP [verb reads][NP [DP [determiner the]][NBar [NBar [AdjP [adjective red]][NBar [noun book]]][PP [preposition in][NP [NBar [noun bed]]]]]]]]]', $Sentence->getSyntaxString());

		// S => NP VP NP NP
		$Sentence = $Processor->parseSentence("John gives Mary flowers.", $English);
		$this->assertSame('[S [Clause [NP [PN [propernoun John]]][VP [verb gives][NP [PN [propernoun Mary]]][NP [NBar [noun flowers]]]]]]', $Sentence->getSyntaxString());
	}

	public function testParseRelations()
	{
		$English = GrammarFactory::getGrammar('en');

		$Processor = new SentenceProcessor();

		$Sentence = $Processor->parseSentence('John reads the red book', $English);
		$this->assertSame('name(S.subject, "John") isa(S.event, Read) tense(S.event, Present) determiner(S.object, The) isa(S_Clause_VP_NP_NBar_AdjP.entity, Red) isa(S.object, Book) modifier(S.object, S_Clause_VP_NP_NBar_AdjP.entity) subject(S.event, S.subject) object(S.event, S.object) mood(S.event, Declarative) sentence(S.event)', $Sentence->getSemanticsString());
	}

	public function testParseDegreeAdverb()
	{
		$Processor = new SentenceProcessor();

		$English = GrammarFactory::getGrammar('en');

		// AdjP -> degreeAdverb adjective
		$Sentence = $Processor->parseSentence('John reads the bright red book', $English);
		$this->assertSame('[S [Clause [NP [PN [propernoun John]]][VP [verb reads][NP [DP [determiner the]][NBar [AdjP [degreeAdverb bright][adjective red]][NBar [noun book]]]]]]]', $Sentence->getSyntaxString());
	}

	public function testParseAdverbialPhrase()
	{
		$Processor = new SentenceProcessor();

		$English = GrammarFactory::getGrammar('en');

		// examples from "The structure of modern english" (p. 173)

		// AdvP -> adverb
		$Sentence = $Processor->parseSentence('John calms the fiercely barking dog', $English);
		$this->assertSame('[S [Clause [NP [PN [propernoun John]]][VP [verb calms][NP [DP [determiner the]][NBar [AdjP [AdvP [adverb fiercely]][adjective barking]][NBar [noun dog]]]]]]]', $Sentence->getSyntaxString());

		// AdvP -> degreeAdverb adverb
		$Sentence = $Processor->parseSentence('John calms the very fiercely barking dog', $English);
		$this->assertSame('[S [Clause [NP [PN [propernoun John]]][VP [verb calms][NP [DP [determiner the]][NBar [AdjP [AdvP [degreeAdverb very][adverb fiercely]][adjective barking]][NBar [noun dog]]]]]]]', $Sentence->getSyntaxString());
	}

	public function testCalculatedAnswerSemantics()
	{
		$Processor = new SentenceProcessor();

		$English = GrammarFactory::getGrammar('en');

		$Sentence = $Processor->parseSentence("How old was Mary Shelley when she died?", $English);
		$this->assertSame('[S [Clause [Clause [WhADVP [whwordNP how][AdjP [adjective old]]][auxBe was][NP [PN [propernoun Mary][propernoun Shelley]]]][SBar [whAdverb when][Clause [NP [pronoun she]][VP [verb died]]]]]]', $Sentence->getSyntaxString());
		$this->assertSame('manner(S.complement, S.request) isa(S.complement, Old) tense(S.event, Past) name(S.subject, "Mary Shelley") subject(S.event, S.subject) modifier(S.event, S.complement) request(S.request) mood(S.event, Interrogative) at_time(S.event, S_Clause_SBar.subEvent) isa(S_Clause_SBar_Clause.subject, Female) reference(S_Clause_SBar_Clause.subject) isa(S_Clause_SBar.subEvent, Die) subject(S_Clause_SBar.subEvent, S_Clause_SBar_Clause.subject) object(S_Clause_SBar.subEvent, S_Clause_SBar_Clause.object) mood(S_Clause_SBar.subEvent, Declarative) sentence(S.event)', $Sentence->getSemanticsString());
	}

	public function testParseNumeral()
	{
		$Processor = new SentenceProcessor();

		$Dutch = GrammarFactory::getGrammar('nl');
		$English = GrammarFactory::getGrammar('en');

		$Sentence = $Processor->parseSentence("Ik ben 43 jaar oud.", $Dutch);
		$this->assertSame('[S [Clause [NP [pronoun ik]][VP [copula ben][AdjP [NP [DP [numeral 43]][NBar [noun jaar]]][adjective oud]]]]]', $Sentence->getSyntaxString());
		$this->assertSame('firstPerson(S.subject) determiner(S.object, "43") isa(S.object, Year) isa(S.object, Old) subject(S.event, S.subject) object(S.event, S.object) mood(S.event, Declarative) sentence(S.event)', $Sentence->getSemanticsString());

		$Sentence = $Processor->parseSentence("I am 43 years old.", $English);
		$this->assertSame('[S [Clause [NP [pronoun i]][VP [copula am][AdjP [NP [DP [numeral 43]][NBar [noun years]]][adjective old]]]]]', $Sentence->getSyntaxString());
		$this->assertSame('firstPerson(S.subject) determiner(S.object, "43") isa(S.object, Year) isa(S.object, Old) subject(S.event, S.subject) object(S.event, S.object) mood(S.event, Declarative) sentence(S.event)', $Sentence->getSemanticsString());
	}

	public function testParseQuotes()
	{
		$Processor = new SentenceProcessor();

		$English = GrammarFactory::getGrammar('en');

		$Sentence = $Processor->parseSentence('I am "Patrick (Garfix) van Bergen"', $English);
		$this->assertSame('[S [Clause [NP [pronoun i]][VP [verb am][NP [PN [propernoun Patrick (Garfix) van Bergen]]]]]]', $Sentence->getSyntaxString());
	}
}