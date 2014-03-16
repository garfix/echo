<?php

namespace agentecho\component\parser;

use agentecho\datastructure\FormulationRule;
use agentecho\datastructure\RelationTemplate;
use agentecho\exception\SemanticStructureParseException;
use agentecho\datastructure\Relation;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Constant;
use agentecho\datastructure\Variable;
use agentecho\datastructure\Atom;
use agentecho\datastructure\Property;
use agentecho\datastructure\Assignment;
use agentecho\datastructure\TermList;
use agentecho\datastructure\AssignmentList;
use agentecho\datastructure\DataMapping;
use agentecho\datastructure\Map;
use agentecho\datastructure\FunctionApplication;
use agentecho\datastructure\BinaryOperation;
use agentecho\datastructure\ProductionRule;
use agentecho\datastructure\ParseRule;
use agentecho\datastructure\GenerationRule;
use agentecho\datastructure\LexicalEntry;

/**
 * @author Patrick van Bergen
 */
class SemanticStructureParser
{
	// pairs
	const T_BRACKET_OPEN = 'bracket open';
	const T_BRACKET_CLOSE = 'bracket close';
	const T_CURLY_BRACKET_OPEN = 'curly bracket open';
	const T_CURLY_BRACKET_CLOSE = 'curly bracket close';
	const T_SQUARE_BRACKET_OPEN = 'square bracket open';
	const T_SQUARE_BRACKET_CLOSE = 'square bracket close';
	// single
	const T_COMMA = 'comma';
	const T_DOT = 'dot';
	const T_QUESTION_MARK = 'question mark';
	const T_COLON = 'colon';
	const T_SEMICOLON = 'semicolon';
	const T_EQUALS_SIGN = 'equals sign';
	const T_HASH_COMMENT = 'hash comment';

	// two chars
	const T_TRANSFORMATION = 'transformation';
	const T_COMMENT = 'comment';
	const T_TEMPLATE_START = 'template start';
	const T_TEMPLATE_END = 'template end';
	// content
	const T_IDENTIFIER = 'identifier';
	const T_STRING = 'string';
	const T_NUMBER = 'number';
	const T_WHITESPACE = 'whitespace';
	// operators
	const T_PLUS = '+';


	private $lastPosParsed = 0;

	/**
	 * @param string $string
	 * @return mixed The structure that was created during the parse.
	 * @throws SemanticStructureParseException
	 */
	public function parse($string)
	{
		if ($string == "") {
			return null;
		}

		// name(a, "John") name(b, "Mary") love(a, b)
		$tokens = $this->tokenize($string);
		if (!$tokens) {
			throw new SemanticStructureParseException(substr($string, $this->lastPosParsed, 40));
		}

		$pos = 0;
		$result = null;
		$this->lastPosParsed = 0;

		// call the main function
		$pos = $this->parseMain($tokens, $pos, $result);

		// parse trailing whitespace
		if ($newPos = $this->parseWhitespace($tokens, $pos)) {
			$pos = $newPos;
		}

		// have all tokens been used?
		if ($pos == count($tokens)) {

			// yes: parse ok
			return $result;

		} else {

			// build the partial string from tokens
			$tokenPos = $this->lastPosParsed;
			$subString = '';
			for ($i = 0; $i < $tokenPos; $i++) {
				$subString .= $tokens[$i]['contents'];
			}

			$stringPos = strlen($subString);

			throw new SemanticStructureParseException(substr($string, $stringPos, 40));
		}
	}

	protected function parseMain(array $tokens, $pos, &$Result)
	{
		if ($newPos = $this->parseMap($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseAssignmentList($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseRelationList($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parsePropertyAssignment($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseProperty($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseProductionRule($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseAtom($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseParseRule($tokens, $pos, $Result)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseRelationTemplate($tokens, $pos, $Result)) {
			$pos = $newPos;
		} else {
			$pos = false;
		}

		return $pos;
	}

	private function parseWhitespace($tokens, $pos)
	{
		while (true) {

			if ($newPos = $this->parseSingleToken(self::T_WHITESPACE, $tokens, $pos)) {
				$pos = $newPos;
			} elseif ($newPos = $this->parseSingleToken(self::T_COMMENT, $tokens, $pos)) {
				$pos = $newPos;
			} elseif ($newPos = $this->parseSingleToken(self::T_HASH_COMMENT, $tokens, $pos)) {
				$pos = $newPos;
			} else {
				break;
			}

		}

		return $pos;
	}

	private function parseProductionRule($tokens, $pos, &$ProductionRule)
	{
		if ($pos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $antecedent)) {

			if ($pos = $this->parseSingleToken(self::T_TRANSFORMATION, $tokens, $pos)) {

				$consequents = array();

				while ($newPos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $consequent)) {
					$pos = $newPos;
					$consequents[] = $consequent;
				}

				$ProductionRule = new ProductionRule();
				$ProductionRule->setAntecedent($antecedent);
				$ProductionRule->setConsequents($consequents);
				return $pos;
			}
		}

		return false;
	}

	protected function parseParseRule($tokens, $pos, &$ParseRule)
	{
		if ($pos = $this->parseSingleToken(self::T_SQUARE_BRACKET_OPEN, $tokens, $pos)) {

			$ParseRule = new ParseRule();

			// label
			while ($newPos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $label)) {
				$pos = $newPos;

				// :
				if ($pos = $this->parseSingleToken(self::T_COLON, $tokens, $pos)) {
				}

				if ($label == 'rule') {

					if ($pos = $this->parseProductionRule($tokens, $pos, $Production)) {
						$ParseRule->setProduction($Production);
					}


				} elseif ($label == 'semantics') {

					if ($pos = $this->parseAssignmentList($tokens, $pos, $Semantics)) {
						$ParseRule->setSemantics($Semantics);
					}

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

			if ($pos = $this->parseSingleToken(self::T_SQUARE_BRACKET_CLOSE, $tokens, $pos)) {

				return $pos;

			}
		}

		return false;
	}

	protected function parseGenerationRule($tokens, $pos, &$ParseRule)
	{
		if ($pos = $this->parseSingleToken(self::T_SQUARE_BRACKET_OPEN, $tokens, $pos)) {

			$ParseRule = new GenerationRule();

			// label
			while ($newPos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $label)) {
				$pos = $newPos;

				// :
				if ($pos = $this->parseSingleToken(self::T_COLON, $tokens, $pos)) {
				}

				if ($label == 'rule') {

					if ($pos = $this->parseProductionRule($tokens, $pos, $Production)) {
						$ParseRule->setProduction($Production);
					}

				} elseif ($label == 'condition') {

					if ($pos = $this->parseRelationList($tokens, $pos, $List)) {
						$ParseRule->setCondition($List);
					}

				} elseif ($label == 'word') {

					if ($pos = $this->parseRelationList($tokens, $pos, $List)) {
						$ParseRule->setWordSemantics($List);
					}

				} elseif ($label == 'bind') {

					if ($pos = $this->parseAssignmentList($tokens, $pos, $List)) {
						$ParseRule->setAssignments($List);
					}

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

			if ($pos = $this->parseSingleToken(self::T_SQUARE_BRACKET_CLOSE, $tokens, $pos)) {

				return $pos;

			}
		}

		return false;
	}

	protected function  parseFormulationRule($tokens, $pos, &$FormulationRule)
	{
		if ($pos = $this->parseSingleToken(self::T_SQUARE_BRACKET_OPEN, $tokens, $pos)) {

			$FormulationRule = new FormulationRule();

			// label
			while ($newPos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $label)) {
				$pos = $newPos;

				// :
				if ($pos = $this->parseSingleToken(self::T_COLON, $tokens, $pos)) {
				}

				if ($label == 'condition') {

					if ($pos = $this->parseRelationList($tokens, $pos, $List)) {
						$FormulationRule->setCondition($List);
					}

				} elseif ($label == 'type') {

					if ($pos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $type)) {
						if ($FormulationRule->typeExists($type)) {
							$FormulationRule->setType($type);
						}
					}

				} elseif ($label == 'production') {

					if ($pos = $this->parseRelationList($tokens, $pos, $List)) {
						$FormulationRule->setProduction($List);
					}

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

			if ($pos = $this->parseSingleToken(self::T_SQUARE_BRACKET_CLOSE, $tokens, $pos)) {

				return $pos;

			}
		}

		return false;
	}

	public function parseLexicalEntry($tokens, $pos, &$LexicalEntry)
	{
		if ($pos = $this->parseSingleToken(self::T_SQUARE_BRACKET_OPEN, $tokens, $pos)) {

			$LexicalEntry = new LexicalEntry();

			// label
			while ($newPos = $this->parseSingleToken(self::T_IDENTIFIER, $tokens, $pos, $label)) {
				$pos = $newPos;

				// :
				if ($pos = $this->parseSingleToken(self::T_COLON, $tokens, $pos)) {
				}

				if ($label == 'form') {

					if ($pos = $this->parseString($tokens, $pos, $wordForm)) {
						$LexicalEntry->setWordForm($wordForm);
					}

				} elseif ($label == 'partOfSpeech') {

					if ($pos = $this->parseString($tokens, $pos, $partOfSpeech)) {
						$LexicalEntry->setPartOfSpeech($partOfSpeech);
					}

				} elseif ($label == 'semantics') {

					if ($pos = $this->parseRelationList($tokens, $pos, $Semantics)) {
						$LexicalEntry->setSemantics($Semantics);
					}

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

			if ($pos = $this->parseSingleToken(self::T_SQUARE_BRACKET_CLOSE, $tokens, $pos)) {

				return $pos;

			}
		}

		return false;
	}

	protected function parseMap(array $tokens, $pos, &$Map)
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
	 * Parses age(?p, ?a) => born(?p, ?d1) die(?p, ?d2) diff(?d2, ?d1, ?a)
	 * @param array $tokens
	 * @param $pos
	 * @param $DataMapping
	 * @return bool
	 */
	private function parseDataMapping(array $tokens, $pos, &$DataMapping)
	{
		if ($pos = $this->parseRelationList($tokens, $pos, $RelationList1)) {

			if ($pos = $this->parseSingleToken(self::T_TRANSFORMATION, $tokens, $pos)) {

				if ($pos = $this->parseRelationList($tokens, $pos, $RelationList2)) {

					$DataMapping = new DataMapping();
					$DataMapping->setPreList($RelationList1);
					$DataMapping->setPostList($RelationList2);
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
		} elseif ($newPos = $this->parseRelation($tokens, $pos, $Term)) {
			$pos = $newPos;
		} else {
			$pos = false;
		}
		return $pos;
	}

	private function parseRelationLet(array $tokens, $pos, &$Relation)
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

							$Relation = new Relation();
							$Relation->setPredicate($predicate);
							$Relation->setArguments($arguments);

							return $pos;
						}
					}
				}
			}

			return false;
		}

	private function parseRelation(array $tokens, $pos, &$Relation)
	{
		// special case: let
		if ($newPos = $this->parseRelationLet($tokens, $pos, $Relation)) {
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

					$Relation = new Relation();
					$Relation->setPredicate($predicate);
					$Relation->setArguments($arguments);

					return $pos;
				}
			}
		}

		return false;
	}

	private function parseRelationList(array $tokens, $pos, &$RelationList)
	{
		$relations = array();
		$newPos = true;

		while ($newPos) {

			// argument
			if ($newPos = $this->parseRelation($tokens, $pos, $result)) {
				$relations[] = $result;
				$pos = $newPos;
			} elseif ($newPos = $this->parseRelationTemplate($tokens, $pos, $result)) {
				$relations[] = $result;
				$pos = $newPos;
			}
		}

		if (count($relations) > 0) {
			$RelationList = new RelationList();
			$RelationList->setRelations($relations);
			return $pos;
		} else {
			return false;
		}
	}

	private function parseRelationTemplate(array $tokens, $pos, &$RelationTemplate)
	{
		// {{
		if ($newPos = $this->parseSingleToken(self::T_TEMPLATE_START, $tokens, $pos)) {
			$pos = $newPos;

			// function application
			if ($newPos = $this->parseFunctionApplication($tokens, $pos, $FunctionApplication)) {
				$pos = $newPos;

				// }}
				if ($newPos = $this->parseSingleToken(self::T_TEMPLATE_END, $tokens, $pos)) {
					$pos = $newPos;

					$RelationTemplate = new RelationTemplate();
					$RelationTemplate->setArgument(0, $FunctionApplication);

					return $pos;
				}
			}
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
		$terms = array();
		$newPos = true;

		while ($newPos) {

			// term
			if ($newPos = $this->parseTerm($tokens, $pos, $result)) {
				$terms[] = $result;
				$pos = $newPos;
			}
		}

		if (count($terms) > 0) {
			$TermList = new TermList();
			$TermList->setTerms($terms);
			return $pos;
		} else {
			return false;
		}
	}

	private function parseAssignmentList(array $tokens, $pos, &$AssignmentList)
	{
		$count = 0;
		$assignments = array($count => null);

		if ($pos = $this->parseSingleToken(self::T_CURLY_BRACKET_OPEN, $tokens, $pos)) {

			if ($newPos = $this->parsePropertyAssignment($tokens, $pos, $assignments[$count])) {
				$pos = $newPos;
				$count++;

				while ($newPos) {

					// ;
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
			}

			if ($pos = $this->parseSingleToken(self::T_CURLY_BRACKET_CLOSE, $tokens, $pos)) {

				$AssignmentList = new AssignmentList();
				$AssignmentList->setAssignments($assignments);
				return $pos;

			}
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
		} elseif ($newPos = $this->parseAtom($tokens, $pos, $argument)) {
			$pos = $newPos;
		} elseif ($newPos = $this->parseConstant($tokens, $pos, $argument)) {
			$pos = $newPos;
		} else {
			$pos = false;
		}
		return $pos;
	}

	private function parseConstant(array $tokens, $pos, &$constant)
	{
		if ($newPos = $this->parseString($tokens, $pos, $string)) {
			$pos = $newPos;
			$constant = new Constant($string);
		} else {
			$pos = false;
		}
		return $pos;
	}

	private function parseString(array $tokens, $pos, &$string)
	{
		if ($newPos = $this->parseSingleToken(self::T_STRING, $tokens, $pos, $quotedString)) {
			$pos = $newPos;
			$string = substr($quotedString, 1, strlen($quotedString) - 2);
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
		} elseif ($newPos = $this->parseSingleToken(self::T_NUMBER, $tokens, $pos, $string)) {
			$pos = $newPos;
			$atom = new Atom((string)$string);
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

				if ($newPos = $this->parseTermList($tokens, $pos, $TermList)) {

					$Assignment = new Assignment();
					$Assignment->setLeft($Property1);
					$Assignment->setRight($TermList);
					return $newPos;

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
		} elseif (in_array($token['id'], array(self::T_WHITESPACE, self::T_COMMENT, self::T_HASH_COMMENT))) {
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
			'[' => self::T_SQUARE_BRACKET_OPEN,
			']' => self::T_SQUARE_BRACKET_CLOSE,
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
			} elseif ($char == "\"" || $char == "'") {
				if (preg_match("/{$char}([^\n{$char}]+){$char}/", $string, $matches, 0, $pos)) {
					$id = self::T_STRING;
					$contents = $matches[0];
					$pos += strlen($matches[0]) - 1;
				} else {
					$this->lastPosParsed = $pos;
					return false;
				}
			} elseif ($char == '#') {
				if (preg_match('/(#[^\n]*)/', $string, $matches, 0, $pos)) {
					$id = self::T_HASH_COMMENT;
					$contents = $matches[1];
					$pos += strlen($contents) - 1;
				}
			} elseif ($char == '/') {
				if (preg_match('#(//[^\n]*)#', $string, $matches, 0, $pos)) {
					$id = self::T_COMMENT;
					$contents = $matches[1];
					$pos += strlen($contents) - 1;
				}
			} elseif (substr($string, $pos, 2) == '=>') {
				$id = self::T_TRANSFORMATION;
				$contents = '=>';
				$pos += 2 - 1;
			} elseif (substr($string, $pos, 2) == '{{') {
				$id = self::T_TEMPLATE_START;
				$contents = '{{';
				$pos += 2 - 1;
			} elseif (substr($string, $pos, 2) == '}}') {
				$id = self::T_TEMPLATE_END;
				$contents = '}}';
				$pos += 2 - 1;
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
				$contents = (double)$matches[1];
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
