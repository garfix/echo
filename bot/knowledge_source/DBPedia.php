<?php
class DBPedia
{
	static $cacheResults = true;
	static $id = 0;

	public function check($phraseStructure)
	{
if (ChatbotSettings::$debugKnowledge) r($phraseStructure);

//		$objectId = self::getVariableId();

		$triples = array();
		$select = '';
		$this->interpret($phraseStructure, $triples, $select, null);

		if (ChatbotSettings::$debugKnowledge) r($triples);

		$result = $this->query($triples, $select);

		if (ChatbotSettings::$debugKnowledge) { r($result); echo "\n"; }

		return $result;
	}

	public function answerQuestionAboutObject($phraseStructure)
	{
		if (ChatbotSettings::$debugKnowledge) r($phraseStructure);

		$triples = array();
		$select = '';
		$this->interpret($phraseStructure, $triples, $select, null);

		if (ChatbotSettings::$debugKnowledge) r($triples);

		$result = $this->query($triples, $select);

		if (ChatbotSettings::$debugKnowledge) { r($result); echo "\n"; }

		return $result;
	}

	private function interpret($phraseStructure, &$triples, &$select, $parentId)
	{
		$s = $phraseStructure;
		if (isset($s['id'])) {
			$subjectId = $s['id'];
		} else {
			$subjectId = $parentId;
		}

		// yes-no-question
		if (isset($s['act'])) {
			if ($s['act'] == 'yes-no-question') {
				$select = '1';
			}
		}

		if (isset($s['question']) && isset($s['determiner'])) {
			if (($s['question'] == '*nature-of') && ($s['determiner'] == '*many')) {
				$select = 'COUNT(?' . $s['id'] . ')';
			}
		}

		if (isset($s['question']) && ($s['question'] == '*location')) {
			$triples[] = array('?' . $s['id'], 'rdfs:label', '?location');
			$select = '?location';
		}
#todo may we combine these, or is this similarity just an exception?
		if (isset($s['question']) && ($s['question'] == '*time')) {
			$select = '?' . $s['id'];
		}

		// http://dbpedia.org/ontology/birthPlace
		if (
			isset($s['predicate']) && ($s['predicate'] == '*give-birth') &&
			isset($s['participants']['*location'])
		) {
			$actorId = $s['participants']['*actor']['id'];
			$locationId = $s['participants']['*location']['id'];
			$triples[] = array('?' . $actorId, '<http://dbpedia.org/ontology/birthPlace>', '?' . $locationId);
		}

		// http://dbpedia.org/ontology/deathPlace
		if (
			isset($s['predicate']) && ($s['predicate'] == '*die') &&
			isset($s['participants']['*location'])
		) {
			$actorId = $s['participants']['*actor']['id'];
			$locationId = $s['participants']['*location']['id'];
			$triples[] = array('?' . $actorId, '<http://dbpedia.org/ontology/deathPlace>', '?' . $locationId);
		}

		// http://dbpedia.org/ontology/birthDate
		if (
			isset($s['predicate']) && ($s['predicate'] == '*give-birth') &&
			isset($s['participants']['*time'])
		) {
			$actorId = $s['participants']['*actor']['id'];
			$timeId = $s['participants']['*time']['id'];
			$triples[] = array('?' . $actorId, '<http://dbpedia.org/ontology/birthDate>', '?' . $timeId);
		}

		// rdfs:label
		if (isset($s['name'])) {
			$triples[] = array('?' . $subjectId, 'rdfs:label', "'" . ucwords($s['name']) . "'@en");
		}

		// http://dbpedia.org/ontology/author
		if (
			isset($s['isa']) && ($s['isa'] == '*author') &&
			isset($s['modifiers']['*belong-to'])
		) {
			$objectId = $s['modifiers']['*belong-to']['id'];
			$triples[] = array('?' . $objectId, '<http://dbpedia.org/ontology/author>', '?' . $subjectId);
		}

		// http://dbpedia.org/ontology/influencedBy
		if (
			isset($s['predicate']) && ($s['predicate'] == '*influence') &&
			isset($s['participants']['*actor']) &&
			isset($s['participants']['*patient'])
		) {
			$actorId = $s['participants']['*actor']['id'];
			$patientId = $s['participants']['*patient']['id'];
			$triples[] = array('?' . $patientId, '<http://dbpedia.org/ontology/influencedBy>', '?' . $actorId);
		}

		// http://dbpedia.org/ontology/child (1)
		if (
			isset($s['predicate']) && ($s['predicate'] == '*have') &&
			isset($s['participants']['*actor']) &&
			isset($s['participants']['*patient']) &&
			($s['participants']['*patient']['isa'] == '*child')
		) {
			$actorId = $s['participants']['*actor']['id'];
			$patientId = $s['participants']['*patient']['id'];
			$triples[] = array('?' . $actorId, '<http://dbpedia.org/ontology/child>', '?' . $patientId);
		}

		// http://dbpedia.org/ontology/child (2)
		if (
			isset($s['predicate']) && ($s['predicate'] == '*be') &&
			isset($s['participants']['*theme']) &&
			isset($s['participants']['*patient']['modifiers']['*belong-to']) &&
			($s['participants']['*patient']['isa'] == '*daughter')
		) {
			$childId = $s['participants']['*theme']['id'];
			$parentId = $s['participants']['*patient']['modifiers']['*belong-to']['id'];
			$triples[] = array('?' . $parentId, '<http://dbpedia.org/ontology/child>', '?' . $childId);
		}


		// interpret child elements
		foreach ($s as $key => $value) {
			if (is_array($value)) {
				$this->interpret($s[$key], $triples, $select, $subjectId);
			}
		}
	}

	/**
	 * @param mixed $query Either a query string or an array of clauses.
	 */
	public function query($where, $select = '*')
	{
		if (is_array($where)) {
			$clauses = $where;
			$triples = array();
			foreach ($clauses as $clause) {
				$triples[] = implode(' ', $clause);
			}
			$query = "SELECT " . $select . " WHERE {\n\t" . implode(" .\n\t", $triples) . "\n}";
		}

		$result = self::$cacheResults ? $this->getResultFromCache($query) : false;
		if ($result === false) {

if (ChatbotSettings::$debugKnowledge) r($query);
			$url = 'http://dbpedia.org/sparql';
			$params = array(
				'default-graph-uri' => 'http://dbpedia.org',
				'query' => $query,
				'format' => 'application/json',
			);

			$json = file_get_contents($url . '?' . http_build_query($params));

			$result = json_decode($json, true);

			if (self::$cacheResults) {
				$this->cacheResult($query, $result);
			}
		}

if (ChatbotSettings::$debugKnowledge) r($result);

		if (isset($result['results']['bindings'][0]['callret-0'])) {
			$value = $result['results']['bindings'][0]['callret-0']['value'];
		} else {
			$value = array();
			foreach ($result['results']['bindings'] as $binding) {
				foreach ($binding as $key => $data) {
					$value[$key] = $data['value'];
				}
				// only use the first answer
				break;
			}

			if (count($value) == 1) {
				$value = reset($value);
			}
		}

		return $value;
	}

	public function querySingleRow($query)
	{
		$result = $this->query($query);
		return $result ? $result[0] : null;
	}

	public function querySingleCell($query)
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
		$dir = __DIR__ . '/../../../cache/';
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
		$dir = __DIR__ . '/../../../cache/';
		$path = $dir . sha1($query);
		if (!file_exists($dir)) {
			mkdir($dir);
		}
		$sha1 = sha1($query);
		$json = json_encode($cache);
		file_put_contents($path, $json);
	}
}