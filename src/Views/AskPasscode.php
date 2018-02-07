<?php

namespace Waynik\Views;

class AskPasscode
{
	private $userIdHash;
	
	public function __construct(string $userIdHash)
	{
		$this->userIdHash = $userIdHash;
	}
	public function render()
	{
	
		$html = "<html><link href='/user-support/css/main.css' rel='stylesheet'><body>";
		$html .= "<form method='POST' action='/user-support/user/" . $this->userIdHash . "'>";
		$html .= "Enter Passcode: <input type='text' name='passcode' />";
		$html .= "<input type='submit'>";
		$html .= "</form>";
		$html .= "</body></html>";

		return $html;
	}
}