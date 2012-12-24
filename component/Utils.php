<?php

namespace agentecho\component;

/**
 * @author Patrick van Bergen
 */
class Utils
{
	public static function calculcateRecursionLevel()
	{
		return count(debug_backtrace());
	}
}
