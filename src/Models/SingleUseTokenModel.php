<?php

namespace Waynik\Models;

class SingleUseTokenModel extends BaseModel
{
	protected $table = "single_use_tokens";
	protected $object = "SingleUseToken";
	
	const USER_ID = 'user_id';
	const TOKEN = 'token';
	const VALID = 'valid';
	const ATTEMPTS = 'attempts';

	protected $fields = [
			self::USER_ID,
			self::TOKEN,
			self::VALID,
			self::ATTEMPTS
	];
	
	public function exists(string $token): bool
	{
		try {
			$this->getByToken($token);
			return true;
		} catch (\Exception $error) {
			return false;
		}
	}
	
	public function useToken(string $token): SingleUseToken
	{
		$singleUseToken = $this->getByToken($token);
		$this->trackAttempt($singleUseToken);
		$this->validateToken($singleUseToken);
		$this->markUsed($singleUseToken);
		return $singleUseToken;
	}

	private function getByToken(string $singleUseToken): SingleUseToken
	{
		
		$sql = "SELECT t.* FROM single_use_tokens t WHERE token = ?";
		$result = $this->storageHelper->query($sql, [$singleUseToken]);
		$tokenData = array_shift($result);
		if ($tokenData) {
			return new SingleUseToken($tokenData);
		} else {
			throw new \Exception("Token not found");
		}
	}
	
	private function validateToken(SingleUseToken $singleUseToken)
	{
		if (!$singleUseToken->isValid()) {
			throw new \Exception("Token already used");
		}
	}
	
	private function trackAttempt(SingleUseToken $singleUseToken)
	{
		$sql = "UPDATE single_use_tokens t SET t.attempts = ? WHERE t.id = ?";
		$attempts = $singleUseToken->getAttempts() + 1;
		$this->storageHelper->query($sql, [$attempts, $singleUseToken->getId()]);
	}
	
	private function markUsed(SingleUseToken $singleUseToken) 
	{
		$sql = "UPDATE single_use_tokens t SET t.valid = 0 WHERE t.id = ?";
		// Commented out until we figure out a way to easily update the spreadsheet
		//$this->storageHelper->query($sql, [$singleUseToken->getId()]);
	}
	
	/**
	 * Return the first valid single use token for the user
	 * @param User $user
	 * @return SingleUseToken
	 */
	public function getByUser(User $user) : SingleUseToken {
		$sql = "SELECT * FROM single_use_tokens WHERE user_id = ? AND valid = 1 LIMIT 1";
		$tokenData = $this->storageHelper->query($sql, [$user->getId()]);
		$tokenData = array_shift($tokenData);
		if ($tokenData) {
			return new SingleUseToken($tokenData);
		} else {
			throw new \Exception("Token not found");
		}
	}
	
	public function create(User $user) : SingleUseToken
	{
		$sql = "INSERT INTO `single_use_tokens` (`token`, `user_id`) VALUES (UUID(), ?);";
		$tokenId = $this->storageHelper->create($sql, [$user->getId()]);
		if ($tokenId) {
			return $this->get($tokenId);
		} else {
			throw new \Exception("Token not created");
		}
	}

}