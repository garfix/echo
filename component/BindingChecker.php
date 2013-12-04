<?php

namespace agentecho\component;


interface BindingChecker
{
	public function check(array &$propertyBindings, array &$variableBindings);
}