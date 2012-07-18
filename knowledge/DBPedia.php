<?php

namespace agentecho\knowledge;

use \agentecho\Settings;
use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\Determiner;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\Relation;
use \agentecho\phrasestructure\Preposition;

/**
 * An adapter for DBPedia.
 */
class DBPedia extends KnowledgeSource
{
	static $cacheResults = true;
	static $id = 0;

	public function isProperNoun($identifier)
	{
		$triples = array(
			array('?object', 'rdfs:label', "'$identifier'@en")
		);
		$select = 'COUNT(?object)';
		$result = $this->query($triples, $select);
		return $result;
	}

    public function checkQuestion(Sentence $Sentence)
   	{
//r($Sentence);
   		$triples = array();
   		$select = '';
        $sentenceType = $Sentence->getSentenceType();
		$this->interpretPhrase($Sentence, $sentenceType, $triples, $select, null);

   		if (Settings::$debugKnowledge) r($triples);

   		$result = $this->query($triples, $select);

   		if (Settings::$debugKnowledge) { r($result); echo "\n"; }

   		return $result;
   	}

	public function answerQuestion(Sentence $Sentence)
	{
//r($Sentence);exit;
		$triples = array();
		$select = '';
		$sentenceType = $Sentence->getSentenceType();
		$this->interpretPhrase($Sentence, $sentenceType, $triples, $select, null);

		$result = $this->query($triples, $select);

		if ($sentenceType == 'wh-question') {
			if (count($result == 1)) {
				if (is_array($result)) {
					$result = reset($result);
				}
				if (is_array($result)) {
					$result = reset($result);
				}
			}
		}
		if ($sentenceType == 'imperative') {
			$values = array();
			foreach ($result as $resultVal) {
				$values[] = reset($resultVal);
			}
			$result = $values;
		}

		return $result;
	}

	public function answerQuestionAboutObject(Sentence $Sentence)
	{
//r($Sentence);exit;
		$triples = array();
		$select = '';
		$sentenceType = $Sentence->getSentenceType();
		$this->interpretPhrase($Sentence, $sentenceType, $triples, $select, null);

		$result = $this->query($triples, $select);

		if ($sentenceType == 'wh-question') {
			if (count($result == 1)) {
				if (is_array($result)) {
					$result = reset($result);
				}
				if (is_array($result)) {
					$result = reset($result);
				}
			}
		}
		if ($sentenceType == 'imperative') {
			$values = array();
			foreach ($result as $resultVal) {
				$values[] = reset($resultVal);
			}
			$result = $values;
		}

		return $result;
	}

	private function interpretPhrase(PhraseStructure $Phrase, $sentenceType, &$triples, &$select, $parentId)
	{
		$subjectId = $Phrase->getHashCode();

		// yes-no-question
		if ($sentenceType == 'yes-no-question') {
			$select = 'COUNT(*)';
		}

		// imperative 'name'
		if ($Phrase instanceof Sentence) {

			/** @var Sentence $Sentence */
			$Sentence = $Phrase;

			if ($sentenceType == 'imperative') {

				/** @var Relation $Relation  */
				$Relation = $Sentence->getRelation();

                if ($Relation->getPredicate() == 'name') {

                    $select = '?name';
                    $arg2id = $Relation->getArgument2()->getHashCode();
                    $triples[] = array('?' . $arg2id, 'rdfs:label', '?name');
                    $triples[] = array('FILTER(lang(?name) = "en")');
                }
			}
		}

		// how many?
		if ($Phrase instanceof Determiner) {

            /** @var Determiner $Determiner */
       		$Determiner = $Phrase;

			if ($Determiner->isQuestion()) {
				if ($Determiner->getCategory() == 'many') {
					$select = 'COUNT(?' . $parentId . ')';
				}
			}
		}

		if ($Phrase instanceof Preposition) {

			/** @var Preposition $Preposition */
			$Preposition = $Phrase;

			$category = $Preposition->getCategory();
			$Object = $Preposition->getObject();

			if ($category == 'location') {
				if ($Object->isQuestion()) {
					$triples[] = array('?' . $Object->getHashCode(), 'rdfs:label', '?location');
					$triples[] = array('FILTER(lang(?location) = "en")');
					$select = '?location';
				}
			}
			if ($category == 'time') {
				if ($Object->isQuestion()) {
					$select = '?' . $Object->getHashCode();
				}
			}

		}

		// rdfs:label
        if ($Phrase instanceof Entity) {

            /** @var Entity $Entity */
       		$Entity = $Phrase;

            if ($name = $Entity->getName()) {
                $triples[] = array('?' . $subjectId, 'rdfs:label', "'" . ucwords($name) . "'@en");
            }

    		// http://dbpedia.org/property/children
            if ($Entity->getCategory() == 'child') {
                if ($Determiner = $Entity->getDeterminer()) {
                    if ($Object = $Determiner->getObject()) {
                        $objectId = $Object->getHashCode();
                        $triples[] = array('?' . $objectId, '<http://dbpedia.org/property/children>', '?' . $subjectId);
                    }
                }
            }

	        // http://dbpedia.org/ontology/author
	        if ($Entity->getCategory() == 'author') {

		        /** @var Preposition $Preposition */
		        $Preposition = $Entity->getPreposition();

		        $objectId = $Preposition->getObject()->getHashCode();

		        $triples[] = array('?' . $objectId, '<http://dbpedia.org/ontology/author>', '?' . $subjectId);
	        }
        }



		// http://dbpedia.org/ontology/influencedBy
		if ($Phrase instanceof Relation) {

            /** @var Relation $Relation */
            $Relation = $Phrase;

			$predicate = $Relation->getPredicate();

			// http://dbpedia.org/ontology/influencedBy
			if ($predicate == 'influence') {
				$arg1 = $Relation->getArgument1()->getHashCode();
				$arg2 = $Relation->getArgument2()->getHashCode();

				$triples[] = array('?' . $arg2, '<http://dbpedia.org/ontology/influencedBy>', '?' . $arg1);
			}

			// http://dbpedia.org/ontology/child (1)
			if ($predicate == 'have') {
				$arg1 = $Relation->getArgument1()->getHashCode();
				$arg2 = $Relation->getArgument2()->getHashCode();

				if ($Relation->getArgument2()->getCategory() == 'child') {
					$triples[] = array('?' . $arg1, '<http://dbpedia.org/ontology/child>', '?' . $arg2);
				}
			}

			// http://dbpedia.org/ontology/birthPlace
			if ($predicate == 'bear') {
				$Preposition = $Relation->getPreposition();
				if ($Preposition->getCategory() == 'location') {
					$locationId = $Preposition->getObject()->getHashCode();
					$arg2id = $Relation->getArgument2()->getHashCode();
					$triples[] = array('?' . $arg2id, '<http://dbpedia.org/ontology/birthPlace>', '?' . $locationId);
				}

				if ($Preposition->getCategory() == 'time') {
					$timeId = $Preposition->getObject()->getHashCode();
					$arg2id = $Relation->getArgument2()->getHashCode();
					$triples[] = array('?' . $arg2id, '<http://dbpedia.org/ontology/birthDate>', '?' . $timeId);
				}

			}

			// http://dbpedia.org/ontology/deathPlace
			if ($predicate == 'die') {
				$Preposition = $Relation->getPreposition();
				if ($Preposition->getCategory() == 'location') {
					$locationId = $Preposition->getObject()->getHashCode();
					$arg1id = $Relation->getArgument1()->getHashCode();
					$triples[] = array('?' . $arg1id, '<http://dbpedia.org/ontology/deathPlace>', '?' . $locationId);
				}
			}

			// http://dbpedia.org/ontology/child (2)
			if ($predicate == 'be') {
				$childId = $Relation->getArgument1()->getHashCode();
				$Arg2 = $Relation->getArgument2();
				$Preposition = $Arg2->getPreposition();
				$Object = $Preposition->getObject();
				$parentId = $Object->getHashCode();

				if ($Relation->getArgument2()->getCategory() == 'daughter') {
					$triples[] = array('?' . $parentId, '<http://dbpedia.org/ontology/child>', '?' . $childId);
				}

			}
		}

		// interpret child elements
        foreach ($Phrase->getChildPhrases() as $ChildPhrase) {
            $this->interpretPhrase($ChildPhrase, $sentenceType, $triples, $select, $subjectId);
        }
	}

	/**
	 * @param mixed $query Either a query string or an array of clauses.
	 */
	private function query($where, $select = '*')
	{
		if (is_array($where)) {
			$clauses = $where;
			$triples = array();
			foreach ($clauses as $clause) {
				$triples[] = implode(' ', $clause);
			}
$triples = array_unique($triples);
			$query = "SELECT " . $select . " WHERE {\n\t" . implode(" .\n\t", $triples) . "\n}";
		}

		if (Settings::$debugKnowledge) r($query);

		$result = self::$cacheResults ? $this->getResultFromCache($query) : false;

		if ($result === false) {

			$url = 'http://dbpedia.org/sparql';
			$params = array(
				'default-graph-uri' => 'http://dbpedia.org',
				'query' => $query,
				'format' => 'application/json',
			);

			$json = @file_get_contents($url . '?' . http_build_query($params));

			if ($json !== false) {
				$result = json_decode($json, true);

				if (self::$cacheResults) {
					$this->cacheResult($query, $result);
				}
			}
		}
//r($query);
//r($result);
		if (isset($result['results']['bindings'][0]['callret-0'])) {
			$value = $result['results']['bindings'][0]['callret-0']['value'];
		} elseif (isset($result['results']['bindings'])) {
			$value = array();
			foreach ($result['results']['bindings'] as $binding) {
				$row = array();
				foreach ($binding as $key => $data) {
					$row[$key] = $data['value'];
				}
				$value[] = $row;
			}

		} else {
			$value = null;
		}

		return $value;
	}

	private function querySingleRow($query)
	{
		$result = $this->query($query);
		return $result ? $result[0] : null;
	}

	private function querySingleCell($query)
	{
		$result = $this->querySingleRow($query);
		if ($result) {
			$var = reset($result);
			return $var['value'];
		} else {
			return null;
		}
	}

	private static function getVariableId()
	{
		return 'v' . ++self::$id;
	}

	private function getResultFromCache($query)
	{
		$dir = __DIR__ . '/../../cache/';
		$path = $dir . sha1($query);
		if (file_exists($path)) {
			$json = file_get_contents($path);
			return json_decode($json, true);
		} else {
			return false;
		}
	}

	private function cacheResult($query, $cache)
	{
		$dir = __DIR__ . '/../../cache/';
		$path = $dir . sha1($query);
		if (!file_exists($dir)) {
			mkdir($dir);
		}
		$sha1 = sha1($query);
		$json = json_encode($cache);
		file_put_contents($path, $json);
	}
}