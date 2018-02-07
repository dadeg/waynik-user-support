<?php

namespace Waynik\Models;

use Waynik\Repository\DataConnectionInterface;

abstract class BaseModel
{
	protected $storageHelper;

	protected $table = "define-me";
	protected $object = "define-me";

	protected $fields = [];

	public function __construct(DataConnectionInterface $dataConnection)
	{
		$this->storageHelper = $dataConnection;
	}
	
	public function get(int $id)
	{
		 
		$sql = "SELECT *
                FROM " . $this->table . " t
                WHERE t.id = ? LIMIT 1";
		$params = [$id];
		 
		$results = $this->storageHelper->query($sql, $params);
		$object = array_shift($results);
		 
		if (!$object) {
			throw new \Exception("Object not found");
		}
		 
		$className = "\Waynik\Models\\" . $this->object;
		return new $className($object);
	}
	
	public function getAllIds(): array
	{
		$sql = "SELECT t.id
                FROM " . $this->table . " t ORDER BY t.id";
		 
		$results = $this->storageHelper->query($sql);
		 
		$ids = [];
		foreach ($results as $result) {
			$ids[] = $result['id'];
		}
		 
		return $ids;
	}
}