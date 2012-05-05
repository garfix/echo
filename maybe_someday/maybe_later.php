<?php

namespace agentecho\later;

/**
 * Low-level: enters $statment into declarative memory.
 *
 * @param array $statement three parameters: subject, predicate, object
 */
public function tell(array $statement)
{
	$this->declarativeMemory[] = $statement;
}

/**
 * Low-level: queries declarative memory for $pattern
 *
 * @param $pattern
 * @return mixed|null
 */
public function ask(array $triple)
{
	$answer = null;

	foreach ($triple as $index => $word) {
		if ($triple[$index][0] == '?') {
			break;
		}
	}

	foreach ($this->declarativeMemory as $statement) {
		foreach ($statement as $i => $part) {
			if ($triple[$i][0] == '?') {
				$answer = $part;
			} else {
				if ($triple[$i] != $part) {
					continue;
				}
			}
		}
		return $answer;
	}

	return null;
}

/**
 * Replaces a variable in a triple with a value
 *
 * @param array $unboundTriple
 * @param string $answer
 */
private function bind($unboundTriple, $answer)
{
	$boundTriple = array();

	foreach ($unboundTriple as $index => $word) {
		if ($word[0] == '?') {
			$boundTriple[] = $answer;
		} else {
			$boundTriple[] = $word;
		}
	}

	return $boundTriple;
}

public function addToContext($subject, $predicate, $object)
{
	$this->context[$subject][$predicate] = $object;
}

