<?php

namespace agentecho\web;

use agentecho\AgentEcho;
use agentecho\component\DataMapper;
use agentecho\component\LogEvent;
use agentecho\grammar\DutchGrammar;
use agentecho\grammar\EnglishGrammar;
use agentecho\knowledge\DBPedia;
use agentecho\web\component\Div;
use agentecho\web\component\Form;
use agentecho\web\component\Image;
use agentecho\web\component\Input;
use agentecho\web\component\Raw;
use agentecho\web\component\ResetButton;
use agentecho\web\component\SideTabs;
use agentecho\web\component\SubmitButton;
use agentecho\web\component\LineEditor;
use agentecho\web\view\BindingsView;
use agentecho\web\view\PhraseStructureView;
use agentecho\web\view\PredicationListView;
use agentecho\web\view\SparqlQueryView;
use agentecho\web\view\SyntaxView;

require_once __DIR__ . '/../component/Autoload.php';

/**
 * @author Patrick van Bergen
 */
class Processor
{
	private $language = 'en';

	function run(array $parameters)
	{
		// language
		if (isset($parameters['language'])) {
			$this->language = $parameters['language'];
		}

		if (isset($parameters['action'])) {

			// Ajax calls

			$action = $parameters['action'];
			$this->$action($parameters);

		} else {

			$this->showMainPage($parameters);

		}
	}

	private function showMainPage($parameters)
	{
		$template = file_get_contents(__DIR__ . '/template.html');

		$Container = new Div();
		$Container->addClass('container');

			$Container->add($Bird = new Image());
			$Bird->addClass('bird');
			// http://www.clipartsalbum.com/?l=en-us&m=start&c=birds&s=lovebirds&p=1&t=20&q=&e=1&i=180727&r=6
			$Bird->setSource('img/lovebird_balloon.jpg');

			$Container->add($BirdEyes = new Image());
			$BirdEyes->setId('birdEyes');
			$BirdEyes->addClass('bird');
			$BirdEyes->setSource('img/lovebird_eyes.png');
			$BirdEyes->addStyle('display', 'none');

			$Container->add($Text = new Div());
			$Text->addClass('text');
			$Text->addText($this->translate('Ask me something...'));

			$Container->add($Interaction = new Div());
			$Interaction->addClass('interaction');

				$Interaction->add($Question = new Div());
				$Question->addClass('question');
				$Question->addText($this->translate('Question'));

				$Interaction->add($Form = $this->getForm($parameters));

		if (isset($parameters['q'])) {

			$response = $this->getResponse(implode(' ', explode(',', $parameters['q'])));

			$Interaction->add($Answer = new Div());
			$Answer->addClass('answer');
			$Answer->addText($this->translate('Answer'));

			$Interaction->addText($response['answer']);

			$Container->add($this->getSideTabs($response));
		}

		$tokens = array(
			'css' => $Container->getStyleElements(),
			'body' => (string)$Container,
			'javascript' => $Container->getJavascriptElements(),
		);

		$html = $this->createHtml($template, $tokens);

		echo $html;
	}

	private function getSideTabs(array $response)
	{
		$SideTabs = new SideTabs();

		foreach ($response as $key => $value) {
			$SideTabs->addTab($key, $this->markUp($key, $value));
		}

		return $SideTabs;
	}

	private function markUp($key, $value)
	{
		if ($key == 'syntax') {
			$View = new SyntaxView();
			$html = $View->getHtml($value);
		} elseif ($key == 'phraseSpecification') {
			$View = new PhraseStructureView();
			$html = $View->getHtml($value);
		} elseif ($key == 'semantics') {
			$View = new PredicationListView();
			$html = $View->getHtml($value);
		} elseif ($key == 'interpretation') {
			$View = new PredicationListView();
			$html = $View->getHtml($value);
		} elseif ($key == 'relations') {
			$View = new PredicationListView();
			$html = $View->getHtml($value);
		} elseif ($key == 'query') {
			$View = new SparqlQueryView();
			$html = $View->getHtml($value);
		} elseif ($key == 'response') {
			$View = new PhraseStructureView();
			$html = $View->getHtml($value);
		} elseif ($key == 'bindings') {
			$View = new BindingsView();
			$html = $View->getHtml($value);
		} else {
			$html = htmlspecialchars($value);
		}

		return new Raw($html);
	}

	private function getForm(array $parameters)
	{
		$Form = new Form();
		$Form->setMethodGet();

			$Form->add($ResetButton = new ResetButton());
			$ResetButton->setTitle($this->translate('Clear'));

			$Form->add($Panel = new Div());
			$Panel->addClass('editPanel');

				$Panel->add($LineEditor = new LineEditor());
				$LineEditor->setName('q');
				$LineEditor->setId('lineEditor');
				$LineEditor->setLanguage($this->language);

				if (isset($parameters['q'])) {
					$LineEditor->setLinePieces(explode(',', $parameters['q']));
				} else {
					$LineEditor->setLinePieces(array('where', 'was', 'Lord Byron', 'born'));
				}

			$Form->add($SubmitButton = new SubmitButton());
			$SubmitButton->setTitle($this->translate('Ask'));

			$Form->add($Language = new Input());
			$Language->setType('hidden');
			$Language->setName('language');
			$Language->setValue($this->language);

			$Form->onReset('$("lineEditor").ed.reset()');

		return $Form;
	}

	private function createHtml($template, $tokens)
	{
		$html = $template;

		foreach ($tokens as $token => $contents) {
			$html = str_replace('##' . $token . '##', $contents, $html);
		}

		return $html;
	}

	public function translate($text)
	{
		$texts = array(
			'nl' => array(
				'Ask' => 'Vraag',
				'Question' => 'Vraag',
				'Answer' => 'Antwoord',
				'Response' => 'Antwoord',
				'Semantics' => 'Semantiek',
				'Ask me something...' => 'Vraag maar wat...',
			)
		);

		$language = $this->language;

		if ($language == 'en') {
			return $text;
		} elseif (isset($texts[$language][$text])) {
			return $texts[$language][$text];
		} else {
			trigger_error('Text not translated:' . $text, E_USER_WARNING);
			return $text;
		}
	}

	public function suggest($params)
	{
		$language = $this->language;

		$sentences = array(
			'nl' => array(
				'waar werd X geboren',
				'wanneer werd X geboren',
			),
			'en' => array(
				'where was X born',
				'when was X born',
				'where did X die',
				'when did X die',
			)
		);

		if ($params['value'] == '') {
			$inputWords = array();
		} else {
			$inputWords = explode(',', $params['value']);
		}

		$wordIndex = count($inputWords) > 0 ? count($inputWords) - 1 : 0;
		$sentenceArrays = $this->getSentenceWords($sentences[$language]);
		$allowedSentences = $this->getAllowedSentences($sentenceArrays, $inputWords);
		$actualWord = $inputWords[$wordIndex];

		$suggests = array();
		foreach ($allowedSentences as $allowedSentence) {

			if (isset($allowedSentence[$wordIndex])) {

				$allowedWord = $allowedSentence[$wordIndex];

				if ($allowedWord == 'X') {

					$names = $this->getNamesLike($actualWord);
					if (empty($names)) {
						$names = array('' => '<i>{Name famous person}</i>');
					}

					$suggests += $names;

				} else {
					$html = preg_replace('/\b(' . $actualWord .  ')/iu', '<b>\1</b>', $allowedWord);
					$suggests[$allowedWord] = $html;
				}
			}
		}

		header('Content-type: application/json');

		$response = array(
			'suggests' => $suggests
		);

		echo json_encode($response);
	}

	private function getAllowedSentences($sentenceArrays, $inputWords)
	{
		$allowedSentences = array();

		foreach ($sentenceArrays as $sentenceArray) {

			$success = true;

			for ($i = 0; $i < count($inputWords); $i++) {
				$inputWord = $inputWords[$i];

				if (isset($sentenceArray[$i])) {

					$word = $sentenceArray[$i];

					if ($word == 'X') {

					} elseif ($inputWord == '') {

					} elseif (strpos($word, $inputWord) !== 0) {

						$success = false;
						break;
					}
				}
			}

			if ($success) {
				$allowedSentences[] = $sentenceArray;
			}

		}

		return $allowedSentences;
	}

	private function getSentenceWords($sentences)
	{
		$words = array();

		foreach ($sentences as $sentence) {
			$words[] = explode(' ' , $sentence);
		}

		return $words;
	}

	function getNamesLike($word)
	{
		$trimmed = trim($word);
		if (empty($trimmed)) {
			return array();
		}

		$names = file_get_contents('/home/patrick/Desktop/names.csv');
		preg_match_all('/^([^\n]*\b' . $word . '[^\n]*)$/miu', $names, $results);
		$names = $results[1];
		$names = array_splice($names, 0, 20);

		$i = 0;
		while ($i < count($names) && $i < 20) {

			$name = $names[$i];

			$html = preg_replace('/\b(' . $word .  ')/iu', '<b>\1</b>', $name);

			$nameHtml[$name] = $html;
			$i++;
		}

		return $nameHtml;
	}

	private function getResponse($sentence)
	{
		$Agent = new AgentEcho();

		$logs = array();

		$Agent->addListener(function($Event) use (&$logs) {
			if ($Event instanceof LogEvent) {
				$logs = array_merge($logs, $Event->getParams());
			}
		});

		switch ($this->language) {
			case 'en':
				$Agent->addGrammar(new EnglishGrammar());
				break;
			case 'nl':
				$Agent->addGrammar(new DutchGrammar());
				break;
		}

		$Agent->addKnowledgeSource(new DBPedia(__DIR__ . '/../resources/dbpedia.map'));
		$Agent->addElaborator(new DataMapper(__DIR__ . '/../resources/ruleBase1.map'));

		$answer = $Agent->answer($sentence);

		$logs['answer'] = $answer;

		return $logs;
	}
}
