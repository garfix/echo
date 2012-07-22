<?php

#todo


		// proper error feedback
		$answer = $Conversation->answer('rwyrwur');
		$this->test(270, $answer, "Word not found: rwyrwur");

		$answer = $Conversation->answer('We rwyrwur born');
		$this->test(271, $answer, "Word not found: rwyrwur");
