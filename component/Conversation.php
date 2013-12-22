<?php

namespace agentecho\component;

use agentecho\grammar\Grammar;
use agentecho\exception\ConfigurationException;

/**
 * This class implements a discourse between a user and Echo.
 *
 * It contains functions that allow the user to interact with the agent at the topmost level: surface text in, surface text out.
 */
class Conversation
{
	/** @var Grammar $CurrentGrammar The grammar that will be tried first,
	 * and the grammar that was last used to successfully parse a sentence */
	private $CurrentGrammar = null;

	/** @var Grammar[] */
	private $grammars;

	/**
	 * @throws ConfigurationException
	 */
	public function __construct(array $grammars)
	{
		$this->grammars = $grammars;

		if (empty($this->grammars)) {
			throw new ConfigurationException();
		}

		$this->CurrentGrammar = reset($grammars);
	}

	/**
	 * @param Grammar $Grammar
	 */
	public function setCurrentGrammar(Grammar $Grammar)
	{
		$this->CurrentGrammar = $Grammar;
	}

	/**
	 * @return Grammar
	 */
	public function getCurrentGrammar()
	{
		return $this->CurrentGrammar;
	}

	/**
	 * @return Grammar[]
	 */
	public function getGrammars()
	{
		return $this->grammars;
	}
}