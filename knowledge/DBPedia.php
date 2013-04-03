<?php

namespace agentecho\knowledge;

use agentecho\Settings;
use agentecho\datastructure\Constant;
use agentecho\phrasestructure\PhraseStructure;
use agentecho\phrasestructure\Sentence;
use agentecho\phrasestructure\Determiner;
use agentecho\phrasestructure\Entity;
use agentecho\phrasestructure\Clause;
use agentecho\phrasestructure\Preposition;
use agentecho\datastructure\PredicationList;
use agentecho\component\DataMapper;
use agentecho\datastructure\SparqlQuery;
use agentecho\datastructure\Predication;
use agentecho\exception\DataMappingFailedException;

/**
 * An adapter for DBPedia.
 *
 * Docs:
 * - SPARQL 1.1 spec:       http://www.w3.org/TR/sparql11-query/
 * - SPARQL cheat sheet:    http://www.slideshare.net/LeeFeigenbaum/sparql-cheat-sheet
 *
 * Online:
 * - http://dbpedia.org/sparql
 */
class DBPedia extends KnowledgeSource
{
	static $cacheResults = true;
	static $id = 0;

	private $dataMapFile = null;
	private $Mapper = null;

	public function __construct($dataMapFile)
	{
		$this->dataMapFile = $dataMapFile;
	}

	public function isProperNoun($identifier)
	{
		$resources = $this->getResourcesByName($identifier);
		return !empty($resources);
	}

	/**
	 * Returns the URI's of all resources in DBPedia that can be identified by $name
	 * as either a label or a birth name.
	 *
	 * @param $name
	 * @return array
	 */
	private function getResourcesByName($name)
	{
		static $resources = array();

		if (!isset($resources[$name])) {

			$triple = array(" { { ?object rdfs:label '$name'@en } UNION { ?object dbpprop:birthName '$name'@en } }");
			$resources[$name] = $this->querySingleColumn(array($triple), '?object');

		}

		return $resources[$name];
	}


    public function checkQuestion(Sentence $Sentence)
   	{
   		$clauses = array();
   		$select = '';
        $sentenceType = $Sentence->getSentenceType();
		$this->interpretPhrase($Sentence, $sentenceType, $clauses, $select, null);

   		if (Settings::$debugKnowledge) r($clauses);

   		$result = $this->query($clauses, $select);

   		if (Settings::$debugKnowledge) { r($result); echo "\n"; }

   		return $result;
   	}

	public function answerQuestion(Sentence $Sentence)
	{
		$clauses = array();
		$select = '';
		$sentenceType = $Sentence->getSentenceType();
		$this->interpretPhrase($Sentence, $sentenceType, $clauses, $select, null);

		$result = $this->query($clauses, $select);

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

	private function interpretPhrase(PhraseStructure $Phrase, $sentenceType, &$clauses, &$select, $parentId)
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

				/** @var Clause $Clause */
				$Clause = $Sentence->getClause();

                if ($Clause->getPredicate() == 'name') {

                    $select = '?name';
                    $deepDeepDirectObject = $Clause->getDeepDirectObject()->getHashCode();
                    $clauses[] = "?{$deepDeepDirectObject} rdfs:label ?name";
                    $clauses[] = 'FILTER(lang(?name) = "en")';
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

			if ($category == 'where') {
				if ($Object->isQuestion()) {
					$clauses[] = "?{$Object->getHashCode()} rdfs:label ?location";
					$clauses[] = 'FILTER(lang(?location) = "en")';
					$select = '?location';
				}
			}
			if ($category == 'when') {
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
	            $ucName = ucwords($name);
	            $clauses[] = "{ { ?{$subjectId} rdfs:label '$ucName'@en } UNION { ?{$subjectId} dbpprop:birthName '$ucName'@en } }";
            }

    		// http://dbpedia.org/property/children
            if ($Entity->getCategory() == 'child') {
                if ($Determiner = $Entity->getDeterminer()) {
                    if ($Object = $Determiner->getObject()) {
                        $objectId = $Object->getHashCode();
                        $clauses[] = "?{$objectId} <http://dbpedia.org/property/children> ?{$subjectId}";
                    }
                }
            }

	        // http://dbpedia.org/ontology/author
	        if ($Entity->getCategory() == 'author') {

		        /** @var Preposition $Preposition */
		        $Preposition = $Entity->getPreposition();
		        $objectId = $Preposition->getObject()->getHashCode();

		        $clauses[] = "?{$objectId} <http://dbpedia.org/ontology/author> ?{$subjectId}";
	        }
        }

		// http://dbpedia.org/ontology/influencedBy
		if ($Phrase instanceof Clause) {

            /** @var Clause $Clause */
            $Clause = $Phrase;

			$predicate = $Clause->getPredicate();

			// http://dbpedia.org/ontology/influencedBy
			if ($predicate == 'influence') {
				$subject = $Clause->getDeepSubject()->getHashCode();
				$deepDeepDirectObject = $Clause->getDeepDirectObject()->getHashCode();

				$clauses[] = "?{$deepDeepDirectObject} <http://dbpedia.org/ontology/influencedBy> ?{$subject}";
			}

			// http://dbpedia.org/ontology/child (1)
			if ($predicate == 'have') {
				$subject = $Clause->getDeepSubject()->getHashCode();
				$deepDeepDirectObject = $Clause->getDeepDirectObject()->getHashCode();

				if ($Clause->getDeepDirectObject()->getCategory() == 'child') {
					$clauses[] = "?{$subject} <http://dbpedia.org/ontology/child> ?{$deepDeepDirectObject}";
				}
			}

			// http://dbpedia.org/ontology/birthPlace
			if ($predicate == 'bear') {
				$Preposition = $Clause->getPreposition();
				if ($Preposition->getCategory() == 'where') {
					$locationId = $Preposition->getObject()->getHashCode();
					$deepDeepDirectObject = $Clause->getDeepDirectObject()->getHashCode();
					$clauses[] = "?{$deepDeepDirectObject} <http://dbpedia.org/ontology/birthPlace> ?{$locationId}";
					$clauses[] = "_:place dbpedia-owl:city ?{$locationId}";
				}

				if ($Preposition->getCategory() == 'when') {
					$timeId = $Preposition->getObject()->getHashCode();
					$deepDeepDirectObject = $Clause->getDeepDirectObject()->getHashCode();
					$clauses[] = "?{$deepDeepDirectObject} <http://dbpedia.org/ontology/birthDate> ?{$timeId}";
				}

			}

			// http://dbpedia.org/ontology/deathPlace
			if ($predicate == 'die') {
				$Preposition = $Clause->getPreposition();
				if ($Preposition && $Preposition->getCategory() == 'where') {
					$locationId = $Preposition->getObject()->getHashCode();
					$subject = $Clause->getDeepSubject()->getHashCode();
					$clauses[] = "?{$subject} <http://dbpedia.org/ontology/deathPlace> ?{$locationId}";
					$clauses[] = "_:place dbpedia-owl:city ?{$locationId}";
				}
			}

			// http://dbpedia.org/ontology/child (2)
			if ($predicate == 'be') {
				$childId = $Clause->getDeepSubject()->getHashCode();
				$DeepDirectObject = $Clause->getDeepDirectObject();
				$Preposition = $DeepDirectObject->getPreposition();
				if ($Preposition) {
					$Object = $Preposition->getObject();
					$parentId = $Object->getHashCode();

					if ($Clause->getDeepDirectObject()->getCategory() == 'daughter') {
						$clauses[] = "?{$parentId} <http://dbpedia.org/ontology/child> ?{$childId}";
					}
				}
			}

			// http://dbpedia/ontology/spouse
			if ($predicate == 'marry') {
				$person1 = $Clause->getDeepDirectObject()->getHashCode();
				$Preposition = $Clause->getPreposition();
				$person2 = $Preposition->getObject()->getHashCode();

				// spouse: a symmetric relation
				$clauses[] = "{ { ?{$person1} dbpprop:spouse ?{$person2} } UNION { ?{$person2} dbpprop:spouse ?{$person1} } }";
			}
		}

		// interpret child elements
        foreach ($Phrase->getChildPhrases() as $ChildPhrase) {
            $this->interpretPhrase($ChildPhrase, $sentenceType, $clauses, $select, $subjectId);
        }
	}

	/**
	 * @param mixed $query Either a query string or an array of clauses.
	 */
	private function query(array $where, $select = '*')
	{
		$query = "SELECT " . $select . " WHERE {\n\t" . implode(" .\n\t", $where) . "\n}";

//r($query);

		$value = $this->queryDBPedia($query);

		return $value;
	}

	private function  queryDBPedia($query)
	{
		$result = self::$cacheResults ? $this->getResultFromCache($query) : false;
# $result = false;

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

			// single value
			$value = $result['results']['bindings'][0]['callret-0']['value'];

		} elseif (isset($result['results']['bindings'])) {

			// a result set
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

	private function querySingleRow($query, $select = '*')
	{
		$result = $this->query($query, $select);
		return $result ? $result[0] : null;
	}

	private function querySingleCell($query, $select = '*')
	{
		$result = $this->querySingleRow($query, $select);
		if ($result) {
			$var = reset($result);
			return is_array($var) ? $var['value'] : $var;
		} else {
			return null;
		}
	}

	private function querySingleColumn($query, $select = '*')
	{
		$return = array();
		$result = $this->query($query, $select);
		if ($result) {
			foreach ($result as $row) {
				$return[] = reset($row);
			}
		}
		return $return;
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
		$json = json_encode($cache);
		file_put_contents($path, $json);
	}

	public function bind($predicate, array $arguments)
	{
		$resultSets = array();

		if ($predicate == 'BIRTHDATE') {
			// presume that name is not null
			list($name, $date) = $arguments;

			$clauses = array();
			// name
			$clauses[] = "{ { ?person rdfs:label '$name'@en } UNION { ?person dbpprop:birthName '$name'@en } }";
			// birth date
			$dateClause = $date ? $date : '?date';
			$clauses[] = "?person <http://dbpedia.org/ontology/birthDate> {$dateClause}";

			foreach ($this->querySingleColumn($clauses, '?date') as $date) {;
				$resultSets[] = array($name, $date);
			}
		}

		if ($predicate == 'DEATHDATE') {
			// presume that name is not null
			list($name, $date) = $arguments;

			$clauses = array();
			// name
			$clauses[] = "{ { ?person rdfs:label '$name'@en } UNION { ?person dbpprop:birthName '$name'@en } }";
			// death date
			$dateClause = $date ? $date : '?date';
			$clauses[] = "?person <http://dbpedia.org/ontology/deathDate> {$dateClause}";

			foreach ($this->querySingleColumn($clauses, '?date') as $date) {
				$resultSets[] = array($name, $date);
			}
		}

		return $resultSets;
	}

	public function answer(PredicationList $Question)
	{
$b = (string)$Question;
		// turn the expanded question into a set of database relations
		$Relations = $this->getDataMapper()->mapPredications($Question);


//		if ($Relations !== false) {

			// convert the database relations into a query
			$Query = $this->createDatabaseQuery($Relations);

$sparql = (string)$Query;

			$resultSets = $this->processQuery($Query);

//		} else {
//
//			throw new DataMappingFailedException();
//
//		}

		return $resultSets;
	}

	private function getDataMapper()
	{
		if ($this->Mapper === null) {
			$this->Mapper = new DataMapper($this->dataMapFile);
		}
		return $this->Mapper;
	}

	private function createDatabaseQuery(PredicationList $Relations)
	{
		$Query = new SparqlQuery();

		foreach ($Relations->getPredications() as $Relation) {

			$this->convertRelationIntoClauses($Relation, $Query);
		}

		return $Query;
	}

	private function convertRelationIntoClauses(Predication $Relation, SparqlQuery $Query)
	{
		$predicate = $Relation->getPredicate();

		switch ($predicate) {
			case 'true';
				break;
			case 'born_at':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("?{$subject} <http://dbpedia.org/ontology/birthDate> ?{$object}");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
				break;
			case 'born_in':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				// link to the place id
				$Query->where("?{$subject} <http://dbpedia.org/ontology/birthPlace> _:placeId");
				// link place id to place name
				$Query->where("_:placeId rdfs:label ?{$object}");
				// place name should be in english
				$Query->where("FILTER(lang(?{$object}) = 'en')");
				// place should be city
				$Query->where("_:place dbpedia-owl:city _:placeId");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
				break;
			case 'die_in':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				// link to the place id
				$Query->where("?{$subject} <http://dbpedia.org/ontology/deathPlace> _:placeId");
				// link place id to place name
				$Query->where("_:placeId rdfs:label ?{$object}");
				// place name should be in english
				$Query->where("FILTER(lang(?{$object}) = 'en')");
				// place should be city
				$Query->where("_:place dbpedia-owl:city _:placeId");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
				break;
			case 'die_at':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("?{$subject} <http://dbpedia.org/ontology/deathDate> ?{$object}");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
				break;
			case 'name':
				$subject = (string)$Relation->getArgument(0)->getName();
				if ($Relation->getArgument(1) instanceof Constant) {
					$object = (string)$Relation->getArgument(1)->getName();
					$ucName = ucwords($object);
					$Query->where("{ { ?{$subject} rdfs:label '$ucName'@en } UNION { ?{$subject} dbpprop:birthName '$ucName'@en } }");
				} else {
					$object = (string)$Relation->getArgument(1)->getName();
					$Query->where("{ { ?{$subject} rdfs:label ?{$object} } UNION { ?{$subject} dbpprop:birthName ?{$object} } }");
				}
				$Query->select("?{$subject}");
				break;
			case 'time_property':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("{ { ?{$subject} <http://dbpedia.org/ontology/birthDate> ?{$object} } UNION { ?{$subject} <http://dbpedia.org/ontology/deathDate> ?{$object} } }");
				break;
			case 'author':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("?{$object} <http://dbpedia.org/ontology/author> ?{$subject}");
				break;
			case 'influence':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("?{$object} <http://dbpedia.org/ontology/influencedBy> ?{$subject}");
				break;
			case 'child':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("?{$subject} <http://dbpedia.org/ontology/child> ?{$object}");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
				break;
			default:
				$i = 0;
				break;
		}
	}

	private function processQuery(SparqlQuery $Query)
	{
		$result = $this->queryDBPedia((string)$Query);
		return $result;
	}
}