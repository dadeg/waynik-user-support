<?php

namespace Waynik\Models;

class User
{
	private $id;
	private $email;
	private $name;
	private $extraFields;
	
	public function __construct(array $data)
	{
		$this->id = $data["id"];
		$this->email = $data["email"];
		$this->name = $data["name"];
		if (array_key_exists("extraFields", $data)) {
			$this->extraFields = $data["extraFields"];
		}
	}
	
	public function getId():int
	{
		return $this->id;
	}
	
	public function getEmail(): string
	{
		return $this->email;
	}
	
	public function getName(): string
	{
		return $this->name;
	}
	
	public function setExtraFields(array $fields)
	{
		$this->extraFields = $fields;
	}
	
	public function getExtraFields(): array
	{
		return $this->extraFields;
	}
}