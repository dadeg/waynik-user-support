<?php

namespace Waynik\Models;

class SingleUseToken
{
	private $id;
	private $attempts;
	private $valid;
	private $user_id;

	public function __construct(array $data)
	{
		$this->id = $data["id"];
		$this->attempts = $data["attempts"];
		$this->valid = $data["valid"];
		$this->token = $data["token"];		
		$this->user_id = $data["user_id"];
		
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function isValid(): bool
	{
		return $this->valid == true;
	}

	public function getAttempts():int
	{
		return $this->attempts;
	}

	public function getUserId():int
	{
		return $this->user_id;
	}

	public function getToken():string
	{
		return $this->token;
	}
}