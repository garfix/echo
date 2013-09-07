<?php

use agentecho\AgentEcho;
use agentecho\component\DataMapper;
use agentecho\grammar\EnglishGrammar;
use agentecho\knowledge\DBPedia;
use agentecho\web\component\Div;
use agentecho\web\component\Form;
use agentecho\web\component\Image;
use agentecho\web\component\SubmitButton;
use agentecho\web\component\LineEditor;

require_once __DIR__ . '/../component/Autoload.php';

/**
 * @author Patrick van Bergen
 */
class Processor
{
	function run()
	{
		if (isset($_REQUEST['action'])) {

			// Ajax calls

			$action = $_REQUEST['action'];
			$this->$action($_REQUEST);

		} else {

			$this->showMainPage($_REQUEST);

		}
	}

	private function showMainPage($parameters)
	{
		$template = file_get_contents(__DIR__ . '/template.html');

		$Container = new Div();
		$Container->addClass('container');

			$Container->add($Text = new Div());
			$Text->addClass('text');
			$Text->addText('Ask me something...');

			$Container->add($Bird = new Image());
			$Bird->addClass('bird');
			$Bird->setSource('img/lovebird_balloon.jpg');

			$Container->add($Interaction = new Div());
			$Interaction->addClass('interaction');

				$Interaction->add($Question = new Div());
				$Question->addClass('question');
				$Question->addText('Question');

				$Interaction->add($Form = $this->getForm());

		if (isset($parameters['q'])) {
			$Interaction->addText($this->getReponseHtml(implode(' ', explode(',', $parameters['q']))));
		}

		$tokens = array(
			'css' => $Form->getStyleElements(),
			'body' => (string)$Container,
			'javascript' => $Form->getJavascriptElements(),
		);

		$html = $this->createHtml($template, $tokens);

		echo $html;
	}

	private function getForm()
	{
		$SubmitButton = new SubmitButton();
		$SubmitButton->setTitle('Ask');

		$LineEditor = new LineEditor();
		$LineEditor->setName('q');

		if (isset($parameters['q'])) {
			$LineEditor->setLinePieces(explode(',', $parameters['q']));
		} else {
			$LineEditor->setLinePieces(array('where', 'was', 'Lord Byron'));
		}

		$Form = new Form();
		$Form->add($LineEditor);
		$Form->add($SubmitButton);

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

	public function suggest($params)
	{
		$language = 'en';

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

		$suggests = array();
		foreach ($allowedSentences as $allowedSentence) {

			if (isset($allowedSentence[$wordIndex])) {

				$allowedWord = $allowedSentence[$wordIndex];

				if ($allowedWord == 'X') {

					$actualWord = $inputWords[$wordIndex];

					$suggests = array_merge($suggests, $this->getNamesLike($actualWord));

				} else {
					$suggests[] = $allowedWord;
				}
			}
		}

		header('Content-type: application/json');

		$suggests = array_values(array_unique($suggests));

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
		return $names;
	}

	private function getReponseHtml($sentence)
	{
		$Agent = new AgentEcho();
		$Agent->addGrammar(new EnglishGrammar());
		$Agent->addKnowledgeSource(new DBPedia(__DIR__ . '/../resources/dbpedia.map'));
		$Agent->addElaborator(new DataMapper(__DIR__ . '/../resources/ruleBase1.map'));
		$Conversation = $Agent->startConversation();

		$response = $Conversation->answer($sentence);

		return $response;
	}
}
