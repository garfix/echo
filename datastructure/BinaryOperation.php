<?php

namespace agentecho\datastructure;

/**
 * A simple binary operation.
 *
 * @author Patrick van Bergen
 */
class BinaryOperation
{
	private $operands;
	private $operator;

	public function setOperands($operands)
	{
		$this->operands = $operands;
	}

	public function getOperands()
	{
		return $this->operands;
	}

	public function setOperator($operator)
	{
		$this->operator = $operator;
	}

	public function getOperator()
	{
		return $this->operator;
	}

	public function __toString()
	{
		return $this->operands[0] . ' ' . $this->operator . ' ' . $this->operands[1];
	}
}
