<?php

namespace Waynik\Models;

use Waynik\Repository\DataConnectionInterface;

class UserModel extends BaseModel
{
    
    protected $table = "users";
	protected $object = "User";
	
    protected $fields = [
        'id',
        'email',
        'password',
        'token'
    ];
    
    public function get(int $id)
    {
    	$user = parent::get($id);
    	$extraFields = $this->getUserExtraFields($user);
    	
    	$user->setExtraFields($extraFields);
    	return $user;
    }
    
    private function getUserExtraFields(User $user): array
    {
    	$sql = "SELECT *
                FROM user_custom_fields t
                WHERE t.user_id = ?";
    	$params = [$user->getId()];
    		
    	$results = $this->storageHelper->query($sql, $params);
    	
    	$extraFields = [];
    	foreach ($results as $result) {
    		$extraFields[$result['attribute']] = $result['value'];
    	}
    	return $extraFields;
    }

    public function authenticate(array $headers)
    {
        if (!array_key_exists('email', $headers) || !array_key_exists('token', $headers)) {
            throw new \Exception('email and token are required headers', 401);
        }

        $token = $headers['token'];
        if (!$token) {
            $token = $headers['token'][0];
        }

        $email = $headers['email'];
        if (!$email) {
            $email = $headers['email'][0];
        }

        $sql = "SELECT u.id, u.email 
                FROM users u 
                JOIN user_custom_fields c ON c.user_id = u.id AND c.attribute = 'apiToken' AND c.value = ? 
                WHERE u.email = ? LIMIT 1";
        $params = [$token, $email];

        $results = $this->storageHelper->query($sql, $params);
        if (!$results) {
            throw new \Exception("Invalid user credentials", 401);
        }

        $user = array_shift($results);
        return $user;
    }
    
    public function getByPasscode(string $idHash, int $passcode): User 
    {
    	$actualId = self::validatePasscodeAndGetActualId($idHash, $passcode);
    	return $this->get($actualId);
    }
    
    private static function validatePasscodeAndGetActualId(string $idHash, int $passcode):int
    {
    	$idFromHash = self::getIdFromIdHash($idHash);
    	$idFromPasscode = self::getIdFromPasscode($passcode);
    	
    	if ($idFromHash !== $idFromPasscode) {
    		throw new \Exception("ID and Passcode do not match");
    	}
    	
    	return $idFromHash;
    }
    
    private static function getIdFromIdHash(string $id):int
    {
    	return (int) base64_decode($id) / 3;
    }
    
    public static function makeIdHash(int $id):string
    {
    	return base64_encode($id * 3);
    }
    
    private static function getIdFromPasscode(int $passcode):int
    {
    	return ($passcode - 11) / 73;
    }
    
    public static function makePasscode(int $id):int
    {
    	return ($id * 73) + 11;
    }

}