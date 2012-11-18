<?php

namespace agentecho\component;

use agentecho\datastructure\Predication;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Constant;
use agentecho\datastructure\Variable;
use agentecho\datastructure\Atom;
use agentecho\datastructure\Property;
use agentecho\exception\SemanticStructureParseException;

/**
 * @author Patrick van Bergen
 */
class SemanticStructureParser
{
	const T_LC_IDENTIFIER = 1;
	const T_UC_IDENTIFIER = 2;
	const T_BRACKET_OPEN = 3;
	const T_BRACKET_CLOSE = 4;
	const T_STRING = 5;
	const T_COMMA = 6;
	const T_AND = 7;
	const T_WHITESPACE = 8;
	const T_DOT = 9;
	const T_QUESTION_MARK = 10;

	private $lastPosParsed = 0;

	/**
	 * @param $semanticStructureString
	 * @return Predication
	 * @throws SemanticStructureParseException
	 */
	public function parse($string)
	{
		// name(a, "John") and name(b, "Mary") and love(a, b)
		$tokens = $this->tokenize($string);
		if (!$tokens) {
			throw new SemanticStructureParseException($this->lastPosParsed, $string);
		}

		$pos = 0;
		$result = null;
		$this->lastPosParsed = 0;

		$newPos = $this->parseMain($tokens, $pos, $result);

		if ($newPos == count($tokens)) {
			return $result;
		} else {

			// build the partial string from tokens
			$tokenPos = $this->lastPosParsed;
			$subString = '';
			for ($i = 0; $i < $tokenPos; $i++) {
				$subString .= $tokens[$i]['contents'];
			}

			$stringPos = strlen($subString);

			throw new SemanticStructureParseException($stringPos, $string);
		}
	}

	private function parseMain(array $tokens, $pos, &$Result)
	{
		if ($newPos = $this->parsePredicationList($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parsePredication($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseProperty($tokens, $pos, $Result)) {
			$pos = $newPos;
		} else {
			$pos = false;
		}
		return $pos;
	}

	private function parsePredication(array $tokens, $pos, &$Predication)
	{
		$predicate = null;
		$arguments = array();

		if ($newPos = $this->parseSingleToken(self::T_LC_IDENTIFIER, $tokens, $pos, $predicate)) {
			$pos = $newPos;

			// opening bracket
			if ($newPos = $this->parseSingleToken(self::T_BRACKET_OPEN, $tokens, $pos)) {
				$pos = $newPos;

				// arguments

				// required first argument
				if ($newPos = $this->parseArgument($tokens, $pos, $argument)) {
					$pos = $newPos;
					$arguments[] = $argument;

					// optional following arguments
					while ($newPos) {

						// comma
						if ($newPos = $this->parseSingleToken(self::T_COMMA, $tokens, $pos)) {
							$pos = $newPos;

							// argument
							if ($newPos = $this->parseArgument($tokens, $pos, $argument)) {
								$pos = $newPos;
								$arguments[] = $argument;
							} else {
								return false;
							}
						}
					}

					// closing bracket
					if ($newPos = $this->parseSingleToken(self::T_BRACKET_CLOSE, $tokens, $pos)) {
						$pos = $newPos;

						$Predication = new Predication();
						$Predication->setPredicate($predicate);
						$Predication->setArguments($arguments);

						return $pos;
					}
				}
			}
		}

		return false;
	}

	private function parsePredicationList(array $tokens, $pos, &$PredicationList)
	{
		$count = 0;
		$predications = array($count => null);
		$Predication = null;

		if ($newPos = $this->parsePredication($tokens, $pos, $predications[$count])) {
			$pos = $newPos;
			$count++;

			while ($newPos) {

				// and
				if ($newPos = $this->parseSingleToken(self::T_AND, $tokens, $pos)) {
					$pos = $newPos;

					// argument
					$predications[$count] = null;
					if ($newPos = $this->parsePredication($tokens, $pos, $predications[$count])) {
						$pos = $newPos;
						$count++;
					} else {
						return false;
					}

				}

			}

			$PredicationList = new PredicationList();
			$PredicationList->setPredications($predications);
			return $pos;
		}

		return false;
	}

	private function parseProperty(array $tokens, $pos, &$Property)
	{
		// parse an atom
		if ($newPos = $this->parseAtom($tokens, $pos, $Atom)) {
			$pos = $newPos;

			// parse .name(.name(.name(...)))
			if ($newPos = $this->parsePropertyTail($tokens, $pos, $Atom, $Property)) {
				$pos = $newPos;
				return $pos;
			}
		}

		return false;
	}

	/**
	 * Parses the .name(.name(...)) part of a property
	 *
	 * @param $Object Will be placed in the 'object' field
	 */
	private function parsePropertyTail(array $tokens, $pos, $Object, &$Property)
	{
		// parse a dot
		if ($newPos = $this->parseSingleToken(self::T_DOT, $tokens, $pos)) {
			$pos = $newPos;

			// parse a propertyname
			if ($newPos = $this->parseSingleToken(self::T_LC_IDENTIFIER, $tokens, $pos, $name)) {
				$pos = $newPos;

				// create a new property
				$Property = new Property();
				$Property->setObject($Object);
				$Property->setName($name);

				// parse the rest (optional)
				// This call will attempt to place the $Property we just made into the 'object' field of a new property
				// It will return another Property object ($Property3) as the last argument
				if ($newPos = $this->parsePropertyTail($tokens, $pos, $Property, $ParentProperty)) {
					$pos = $newPos;

					// the property we created above was pushed down and
					// we will return the object created by our call to parsePropertyTail
					$Property = $ParentProperty;
				}
				return $pos;
			}
		}

		return false;
	}

	private function parseArgument(array $tokens, $pos, &$argument)
	{
		if ($newPos = $this->parseConstant($tokens, $pos, $argument)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseAtom($tokens, $pos, $argument)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parsePredicationList($tokens, $pos, $argument)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseVariable($tokens, $pos, $argument)) {
			$pos = $newPos;
		} else {
			$pos = false;
		}
		return $pos;
	}

	private function parseConstant(array $tokens, $pos, &$constant)
	{
		if ($newPos = $this->parseSingleToken(self::T_STRING, $tokens, $pos, $string)) {
			$pos = $newPos;
			$constant = new Constant($string);
		} else {
			$pos = false;
		}
		return $pos;
	}

	private function parseAtom(array $tokens, $pos, &$atom)
	{
		if ($newPos = $this->parseSingleToken(self::T_UC_IDENTIFIER, $tokens, $pos, $string)) {
			$pos = $newPos;
			$atom = new Atom($string);
		} else {
			$pos = false;
		}
		return $pos;
	}

	private function parseVariable(array $tokens, $pos, &$variable)
	{
		if ($newPos = $this->parseSingleToken(self::T_QUESTION_MARK, $tokens, $pos)) {
			$pos = $newPos;

			if ($newPos = $this->parseSingleToken(self::T_LC_IDENTIFIER, $tokens, $pos, $string)) {
				$pos = $newPos;

				$variable = new Variable($string);

			} else {
				$pos = false;
			}

		} else {
			$pos = false;
		}
		return $pos;
	}

	private function parseSingleToken($tokenId, array $tokens, $pos, &$result = null)
	{
		// for debugging
		$this->lastPosParsed = max($this->lastPosParsed, $pos);

		if ($pos > count($tokens) - 1) {
			return false;
		}
		$token = $tokens[$pos];
		if ($token['id'] == $tokenId) {
			$result = $token['contents'];
			$pos++;
			return $pos;
		} elseif ($token['id'] == self::T_WHITESPACE) {
			$pos++;
			return $this->parseSingleToken($tokenId, $tokens, $pos, $result);
		} else {
			return false;
		}
	}

	private function tokenize($string)
	{
		$stringLength = strlen($string);
		$tokens = array();

		for ($pos = 0; $pos < $stringLength; $pos++) {

			$char = $string[$pos];

			if ($char == ' ' || $char == "\t" || $char == "\r" || $char == "\n") {
				if (preg_match('/(\s+)/', $string, $matches, 0, $pos)) {
					$id = self::T_WHITESPACE;
					$contents = $matches[1];
					$pos += strlen(($contents)) - 1;
				}
			} elseif ($char == '(') {
				$id = self::T_BRACKET_OPEN;
				$contents = '(';
			} elseif ($char == ')') {
				$id = self::T_BRACKET_CLOSE;
				$contents = ')';
			} elseif ($char == ',') {
				$id = self::T_COMMA;
				$contents = ',';
			} elseif ($char == '.') {
				$id = self::T_DOT;
				$contents = ',';
			} elseif ($char == '?') {
				$id = self::T_QUESTION_MARK;
				$contents = '?';
			} elseif ($char == "\"" || $string[$pos] == "'") {
				$endPos = strpos($string, $char, $pos + 1);
				if ($endPos !== false) {
					$length = $endPos - $pos + 1;
					$id = self::T_STRING;
					$contents = substr($string, $pos + 1, $length - 2);
					$pos += $length - 1;
				} else {
					$this->lastPosParsed = $pos;
					return false;
				}
			} elseif (substr($string, $pos, 3) == 'and') {
				$id = self::T_AND;
				$contents = 'and';
				$pos += 3 - 1;
			} elseif ($char >= 'A' and $char <= 'Z') {
				if (preg_match('/(\w+)/', $string, $matches, 0, $pos)) {
					$id = self::T_UC_IDENTIFIER;
					$contents = $matches[1];
					$pos += strlen(($contents)) - 1;
				}
			} elseif ($char >= 'a' and $char <= 'z') {
				if (preg_match('/(\w+)/', $string, $matches, 0, $pos)) {
					$id = self::T_LC_IDENTIFIER;
					$contents = $matches[1];
					$pos += strlen($contents) - 1;
				}
			} else {
				$this->lastPosParsed = $pos;
				return false;
			}

			$tokens[] = array('id' => $id, 'contents' => $contents);
		}

		return $tokens;
	}

	public function serialize($SemanticStructure)
	{
		return (string)$SemanticStructure;
	}
}
