<?php

use Waynik\Models\UserModel;

require_once __DIR__.'/../vendor/autoload.php';

for($id = 1; $id<=50; $id++) {
	echo $id . ", https://app.waynik.com:21004/user/" . UserModel::makeIdHash($id) . ", " . UserModel::makePasscode($id) . "\n";
}