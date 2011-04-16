<?php

/**
 * This class creates and plans a dialog structure for the conversation with the user.
 * A process called "Macroplanning" by Levelt.
 *
 * The choice of what content should be expressed, depends on (at least): (BNLGS, p. 50)
 * - Communicative goals (what are you trying to achieve)
 * - Characteristics of the reader (for example: novice, expert)
 * - Constraints (size, for example)
 * - The underlying information source (name only most significant information)
 * Depending on the goal, the planner should apply a different plan.
 *
 * I did not make the dialog manager part of the LanguageProcessor, because it's just one of the possible uses of that module.
 * Adding all uses to the module would make it unnecessarily complicated.
 */
class DiscoursePlanner
{
	private $userId;
	private $LanguageProcessor;
	private $workingMemory;

private $dialogPlan;

	/**
	 * @todo Add a communicative goal
	 *
	 * @param string $userId
	 * @param LanguageProcessor $LanguageProcessor
	 * @param array $workingMemory
	 */
	public function __construct($userId, $LanguageProcessor, $workingMemory)
	{
		$this->userId = $userId;
		$this->LanguageProcessor = $LanguageProcessor;
		$this->workingMemory = $workingMemory;
	}

	/**
	 * Within current conversation, interact with the user by responding his $input.
	 *
	 * @param string $input Human readable input
	 * @return string Human readbale output
	 */
	public function interact($input)
	{
		$sentences = $this->LanguageProcessor->parse($input, $this->workingMemory);
	}
}