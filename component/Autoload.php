<?php

spl_autoload_register(

	function ($className)
	{
		if (file_exists($file = __DIR__ . '/../../' . strtr($className, '\\', '/') . '.php')) {
			require_once $file;
		}
	}

);
