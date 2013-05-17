<?php

namespace agentecho\component;

use agentecho\exception\SemanticStructureParseException;
use agentecho\datastructure\Predication;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Constant;
use agentecho\datastructure\Variable;
use agentecho\datastructure\Atom;
use agentecho\datastructure\Property;
use agentecho\datastructure\Assignment;
use agentecho\datastructure\TermList;
use agentecho\datastructure\AssignmentList;
use agentecho\datastructure\GoalClause;
use agentecho\datastructure\DataMapping;
use agentecho\datastructure\Map;
use agentecho\datastructure\FunctionApplication;
use agentecho\datastructure\BinaryOperation;
use agentecho\datastructure\LabeledDAG;

/**
 *
 * todo: the parser now allows possibilities that I don't need; these may be removed at some later point
 *
 * @author Patrick van Bergen
 */
class SemanticStructureParser
{
	// pairs
	const T_BRACKET_OPEN = 'bracket open';
	const T_BRACKET_CLOSE = 'bracket close';
	const T_CURLY_BRACKET_OPEN = 'curly bracket open';
	const T_CURLY_BRACKET_CLOSE = 'curly bracket close';
	// single
	const T_COMMA = 'comma';
	const T_DOT = 'dot';
	const T_QUESTION_MARK = 'question mark';
	const T_COLON = 'colon';
	const T_SEMICOLON = 'semicolon';
	const T_EQUALS_SIGN = 'equals sign';
	// two chars
	const T_IMPLICATION = 'implication';
	const T_TRANSFORMATION = 'transformation';
	const T_COMMENT = 'comment';
	// content
	const T_IDENTIFIER = 'identifier';
	const T_STRING = 'string';
	const T_NUMBER = 'number';
	const T_WHITESPACE = 'whitespace';
	// keywords
	const T_AND = 'and';
	// operators
	const T_PLUS = '+';


	private $lastPosParsed = 0;

	/**
	 * @param $semanticStructureString
	 * @throws SemanticStructureParseException
	 */
	public function parse($string)
	{
		if ($string == "") {
			return null;
		}

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
		if ($newPos = $this->parseMap($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseGoalClause($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parsePropertyAssignmentList($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parsePredicationList($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parsePropertyAssignment($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseProperty($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseAtom($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseLabeledDag($tokens, $pos, $Result)) {
			$pos = $newPos;
		} else {
			$pos = false;
		}

		// parse trailing whitespace
		if ($newPos = $this->parseWhitespace($tokens, $pos)) {
			$pos = $newPos;
		}

		return $pos;
	}

	private function parseWhitespace($tokens, $pos)
	{
		if ($newPos = $this->parseSingleToken(self::T_WHITESPACE, $tokens, $pos)) {
			$pos = $newPos;
		} else {
			$pos = false;
		}

		return $pos;
	}

	private function parseLabeledDag(array $tokens, $pos, &$LabeledDag)
	{
		if ($pos = $this->parseLabeledDagTree($tokens, $pos, $tree)) {

			$LabeledDag = new LabeledDAG($tree);

			return $pos;
		}

		return false;
	}

	private function parseLabeledDagTree(array $tokens, $pos, &$tree)
	{
		$tree = array();

		// {
		if ($pos = $this->parseSingleToken(self::T_CURLY_BRACKET_OPEN, $tokens, $pos)) {

			// label
			while ($newPos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $label)) {
				$pos = $newPos;

				// :
				if ($pos = $this->parseSingleToken(self::T_COLON, $tokens, $pos)) {

					// reference variable (optional)
					if ($newPos = $this->parseVariable($tokens, $pos, $Variable)) {
						$pos = $newPos;
						$variable = (string)$Variable;
					} else {
						$variable = null;
					}

					// constant or tree
					if ($newPos = $this->parseConstant($tokens, $pos, $constant)) {
						$pos = $newPos;
						$value = (string)$constant;
					} elseif ($newPos = $this->parseSingleToken(self::T_NUMBER, $tokens, $pos, $number)) {
						$pos = $newPos;
						$value = $number;
					} elseif ($newPos = $this->parseLabeledDagTree($tokens, $pos, $subTree)) {
						$pos = $newPos;
						$value = $subTree;
					} elseif ($variable !== null) {
						// there is just the reference variable
						$value = $variable;
						$variable = null;
					} else {
						return false;
					}

					// add field
					if ($variable !== null) {
						$label .= '{' . $variable . '}';
					}
					$tree[$label] = $value;

				} else {
					return false;
				}

				// ,
				if ($newPos = $this->parseSingleToken(self::T_COMMA, $tokens, $pos)) {
					$pos = $newPos;
				} else {
					break;
				}
			}

			// }
			if ($pos = $this->parseSingleToken(self::T_CURLY_BRACKET_CLOSE, $tokens, $pos)) {
				return $pos;
			}
		}

		return false;
	}

	private function parseMap(array $tokens, $pos, &$Map)
	{
		$mappings = array();

		// first mapping
		if ($newPos = $this->parseDataMapping($tokens, $pos, $Mapping)) {
			$mappings[] = $Mapping;
			$pos = $newPos;

			// zero or more semicolon - mapping combinations
			while ($newPos = $this->parseSingleToken(self::T_SEMICOLON, $tokens, $pos)) {
				$pos = $newPos;

				if ($newPos = $this->parseDataMapping($tokens, $pos, $Mapping)) {
					$mappings[] = $Mapping;
					$pos = $newPos;
				}
			}


			if (!empty($mappings)) {
				$Map = new Map();
				$Map->setMappings($mappings);
				return $pos;
			}

		}

		return false;
	}

	/**
	 * Parses grandfather(?x, ?z) :- father(?x, ?y) and father(?y, ?z)
	 *
	 * @param array $tokens
	 * @param $pos
	 * @param $GoalClause
	 * @return bool
	 */
	private function parseGoalClause(array $tokens, $pos, &$GoalClause)
	{
		if ($pos = $this->parsePredicationList($tokens, $pos, $GoalList)) {

			if ($pos = $this->parseSingleToken(self::T_IMPLICATION, $tokens, $pos)) {

				if ($pos = $this->parsePredicationList($tokens, $pos, $MeansList)) {

					$GoalClause = new GoalClause();
					$GoalClause->setGoal($GoalList);
					$GoalClause->setMeans($MeansList);
					return $pos;
				}
			}
		}

		return false;
	}

	/**
	 * Parses age(?p, ?a) => born(?p, ?d1) and die(?p, ?d2) and diff(?d2, ?d1, ?a)
	 * @param array $tokens
	 * @param $pos
	 * @param $GoalClause
	 */
	private function parseDataMapping(array $tokens, $pos, &$DataMapping)
	{
		if ($pos = $this->parsePredicationList($tokens, $pos, $PredicationList1)) {

			if ($pos = $this->parseSingleToken(self::T_TRANSFORMATION, $tokens, $pos)) {

				if ($pos = $this->parsePredicationList($tokens, $pos, $PredicationList2)) {

					$DataMapping = new DataMapping();
					$DataMapping->setPreList($PredicationList1);
					$DataMapping->setPostList($PredicationList2);
					return $pos;
				}
			}
		}

		return false;
	}

	/**
	 * Parses any term.
	 *
	 * @param array $tokens
	 * @param $pos
	 * @param $Term
	 * @return bool
	 */
	private function parseTerm(array $tokens, $pos, &$Term)
	{
		if ($newPos = $this->parseOperation($tokens, $pos, $Term)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseProperty($tokens, $pos, $Term)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parsePredication($tokens, $pos, $Term)) {
			$pos = $newPos;
		} else {
			$pos = false;
		}
		return $pos;
	}

	private function parsePredicationLet(array $tokens, $pos, &$Predication)
		{
			$predicate = null;
			$arguments = array();

			if ($pos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $predicate)) {

				if ($predicate == 'let') {

					// opening bracket
					if ($pos = $this->parseSingleToken(self::T_BRACKET_OPEN, $tokens, $pos)) {

						// first argument
						if ($pos = $this->parseArgument($tokens, $pos, $argument)) {
							$arguments[] = $argument;

							// comma
							if ($pos = $this->parseSingleToken(self::T_COMMA, $tokens, $pos)) {

								// second argument
								if ($newPos = $this->parseFunctionApplication($tokens, $pos, $argument)) {
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

	private function parsePredication(array $tokens, $pos, &$Predication)
	{
		// special case: let
		if ($newPos = $this->parsePredicationLet($tokens, $pos, $Predication)) {
			$pos = $newPos;
			return $pos;
		}

		$predicate = null;
		$arguments = array();

		if ($newPos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $predicate)) {
			$pos = $newPos;

			// opening bracket
			if ($newPos = $this->parseSingleToken(self::T_BRACKET_OPEN, $tokens, $pos)) {
				$pos = $newPos;

				// arguments

				// optional first argument
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

		return false;
	}

	private function parsePredicationList(array $tokens, $pos, &$PredicationList)
	{
		$count = 0;
		$predications = array($count => null);

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

	private function parseOperation(array $tokens, $pos, &$Operation)
	{
		// first operand
		if ($newPos = $this->parseOperand($tokens, $pos, $Operand1)) {
			$pos = $newPos;

			if ($newPos = $this->parseOperator($tokens, $pos, $operator)) {
				$pos = $newPos;

				if (($newPos = $this->parseOperation($tokens, $pos, $Operand2)) ||
					($newPos = $this->parseOperand($tokens, $pos, $Operand2))) {

					$pos = $newPos;

					$Operation = new BinaryOperation();
					$Operation->setOperator($operator);
					$Operation->setOperands(array($Operand1, $Operand2));
					return $pos;

				}
			}
		}

		return false;
	}

	private function parseOperator(array $tokens, $pos, &$operator)
	{
		if ($pos = $this->parseSingleToken(self::T_PLUS, $tokens, $pos, $operator)) {
			return $pos;
		}

		return false;
	}

	private function parseOperand(array $tokens, $pos, &$Operand)
	{
		if ($newPos = $this->parseConstant($tokens, $pos, $Operand)) {
			return $newPos;
		} elseif ($newPos = $this->parseProperty($tokens, $pos, $Operand)) {
			return $newPos;
		}
	}

	private function parseTermList(array $tokens, $pos, &$TermList)
	{
		$count = 0;
		$terms = array($count => null);

		if ($newPos = $this->parseTerm($tokens, $pos, $terms[$count])) {
			$pos = $newPos;
			$count++;

			while ($newPos) {

				// and
				if ($newPos = $this->parseSingleToken(self::T_AND, $tokens, $pos)) {
					$pos = $newPos;

					// argument
					$terms[$count] = null;
					if ($newPos = $this->parseTerm($tokens, $pos, $terms[$count])) {
						$pos = $newPos;
						$count++;
					} else {
						return false;
					}

				}

			}

			$TermList = new TermList();
			$TermList->setTerms($terms);
			return $pos;
		}

		return false;
	}

	private function parsePropertyAssignmentList(array $tokens, $pos, &$AssignmentList)
	{
		$count = 0;
		$assignments = array($count => null);

		if ($newPos = $this->parsePropertyAssignment($tokens, $pos, $assignments[$count])) {
			$pos = $newPos;
			$count++;

			while ($newPos) {

				// and
				if ($newPos = $this->parseSingleToken(self::T_SEMICOLON, $tokens, $pos)) {
					$pos = $newPos;

					// argument
					$assignments[$count] = null;
					if ($newPos = $this->parsePropertyAssignment($tokens, $pos, $assignments[$count])) {
						$pos = $newPos;
						$count++;
					} else {
						return false;
					}

				}

			}

			$AssignmentList = new AssignmentList();
			$AssignmentList->setAssignments($assignments);
			return $pos;
		}

		return false;
	}

	/**
	 * Parses a list of ?a = f(?b, ?c), ?d = g(?a)
	 */
	private function parseVariableAssignmentList(array $tokens, $pos, &$AssignmentList)
	{
		$count = 0;
		$assignments = array($count => null);

		if ($newPos = $this->parseVariableAssignment($tokens, $pos, $assignments[$count])) {
			$pos = $newPos;
			$count++;

			while ($newPos) {

				// and
				if ($newPos = $this->parseSingleToken(self::T_SEMICOLON, $tokens, $pos)) {
					$pos = $newPos;

					// argument
					$assignments[$count] = null;
					if ($newPos = $this->parseVariableAssignment($tokens, $pos, $assignments[$count])) {
						$pos = $newPos;
						$count++;
					} else {
						return false;
					}

				}

			}

			$AssignmentList = new AssignmentList();
			$AssignmentList->setAssignments($assignments);
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
			if ($newPos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $name)) {
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
		if ($newPos = $this->parseOperation($tokens, $pos, $argument)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseProperty($tokens, $pos, $argument)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseFunctionApplication($tokens, $pos, $argument)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseConstant($tokens, $pos, $argument)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseAtom($tokens, $pos, $argument)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseVariable($tokens, $pos, $argument)) {
			$pos = $newPos;
		} else {
			$pos = false;
		}
		return $pos;
	}

	private function parseFunctionArgument(array $tokens, $pos, &$argument)
	{
		if ($newPos = $this->parseVariable($tokens, $pos, $argument)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseFunctionApplication($tokens, $pos, $argument)) {
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
		if ($newPos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $string)) {
			$pos = $newPos;
			$atom = new Atom($string);
		} else {
			$pos = false;
		}
		return $pos;
	}

	private function parsePropertyAssignment(array $tokens, $pos, &$Assignment)
	{
		if ($newPos = $this->parseProperty($tokens, $pos, $Property1)) {
			$pos = $newPos;

			if ($newPos = $this->parseSingleToken(self::T_EQUALS_SIGN, $tokens, $pos, $string)) {
				$pos = $newPos;

				if ($pos = $this->parseTermList($tokens, $pos, $TermList)) {

					$Assignment = new Assignment();
					$Assignment->setLeft($Property1);
					$Assignment->setRight($TermList);
					return $pos;

				}
			}
		}

		return false;
	}

	private function parseVariableAssignment(array $tokens, $pos, &$Assignment)
	{
		if ($newPos = $this->parseVariable($tokens, $pos, $Variable)) {
			$pos = $newPos;

			if ($newPos = $this->parseSingleToken(self::T_EQUALS_SIGN, $tokens, $pos, $string)) {
				$pos = $newPos;

				if ($pos = $this->parseFunctionApplication($tokens, $pos, $FunctionApplication)) {

					$Assignment = new Assignment();
					$Assignment->setLeft($Variable);
					$Assignment->setRight($FunctionApplication);
					return $pos;

				}
			}
		}

		return false;
	}

	private function parseFunctionApplication(array $tokens, $pos, &$FunctionApplication)
	{
		$name = null;
		$arguments = array();

		if ($pos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $name)) {

			// opening bracket
			if ($pos = $this->parseSingleToken(self::T_BRACKET_OPEN, $tokens, $pos)) {

				// arguments

				// optional first argument
				if ($newPos = $this->parseFunctionArgument($tokens, $pos, $argument)) {
					$pos = $newPos;
					$arguments[] = $argument;

					// optional following arguments
					while ($newPos) {

						// comma
						if ($newPos = $this->parseSingleToken(self::T_COMMA, $tokens, $pos)) {
							$pos = $newPos;

							// argument
							if ($newPos = $this->parseFunctionArgument($tokens, $pos, $argument)) {
								$pos = $newPos;
								$arguments[] = $argument;
							} else {
								return false;
							}
						}
					}
				}

				// closing bracket
				if ($newPos = $this->parseSingleToken(self::T_BRACKET_CLOSE, $tokens, $pos)) {
					$pos = $newPos;

					$FunctionApplication = new FunctionApplication();
					$FunctionApplication->setName($name);
					$FunctionApplication->setArguments($arguments);

					return $pos;
				}
			}
		}

		return false;
	}

	private function parseVariable(array $tokens, $pos, &$variable)
	{
		if ($newPos = $this->parseSingleToken(self::T_QUESTION_MARK, $tokens, $pos)) {
			$pos = $newPos;

			if ($newPos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $string)) {
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
		} elseif (in_array($token['id'], array(self::T_WHITESPACE, self::T_COMMENT))) {
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

		$singleCharTokens = array(
			'(' => self::T_BRACKET_OPEN,
			')' => self::T_BRACKET_CLOSE,
			'{' => self::T_CURLY_BRACKET_OPEN,
			'}' => self::T_CURLY_BRACKET_CLOSE,
			',' => self::T_COMMA,
			'.' => self::T_DOT,
			':' => self::T_COLON,
			';' => self::T_SEMICOLON,
			'?' => self::T_QUESTION_MARK,
			'=' => self::T_EQUALS_SIGN,
			'+' => self::T_PLUS,
		);

		for ($pos = 0; $pos < $stringLength; $pos++) {

			$char = $string[$pos];

			if ($char == ' ' || $char == "\t" || $char == "\r" || $char == "\n") {
				if (preg_match('/(\s+)/', $string, $matches, 0, $pos)) {
					$id = self::T_WHITESPACE;
					$contents = $matches[1];
					$pos += strlen($contents) - 1;
				}
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
			} elseif ($char == '/') {
				if (preg_match('#(//[^\n]*)#', $string, $matches, 0, $pos)) {
					$id = self::T_COMMENT;
					$contents = $matches[1];
					$pos += strlen($contents) - 1;
				}
			} elseif (substr($string, $pos, 2) == ':-') {
				$id = self::T_IMPLICATION;
				$contents = ':-';
				$pos += 2 - 1;
			} elseif (substr($string, $pos, 2) == '=>') {
				$id = self::T_TRANSFORMATION;
				$contents = '=>';
				$pos += 2 - 1;
			} elseif (substr($string, $pos, 3) == 'and') {
				$id = self::T_AND;
				$contents = 'and';
				$pos += 3 - 1;
			} elseif (isset($singleCharTokens[$char])) {
				// single character
				$id = $singleCharTokens[$char];
				$contents = $char;
			} elseif (($char >= 'A' and $char <= 'Z') || ($char >= 'a' and $char <= 'z')) {
				if (preg_match('/(\w+)/', $string, $matches, 0, $pos)) {
					$id = self::T_IDENTIFIER;
					$contents = $matches[1];
					$pos += strlen($contents) - 1;
				}
			} elseif (preg_match('/(\d+)/', $string, $matches, 0, $pos)) {
				// number
				$id = self::T_NUMBER;
				$contents = $matches[1];
				$pos += strlen($contents) - 1;
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
