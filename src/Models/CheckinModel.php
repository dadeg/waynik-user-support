<?php

namespace Waynik\Models;

use Waynik\Repository\DataConnectionInterface;

class CheckinModel
{
    private $storageHelper;

    private $table = "checkins";

    const USER_ID = 'user_id';
    const LATITUDE = 'latitude';
    const LONGITUDE = 'longitude';
    const MESSAGE = 'message';
    const BATTERY = 'battery';
    const SPEED = 'speed';
    const BEARING = 'bearing';
    const ALTITUDE = 'altitude';

    private $fields = [
        self::USER_ID,
        self::LATITUDE,
        self::LONGITUDE,
        self::MESSAGE,
        self::BATTERY,
        self::SPEED,
        self::BEARING,
        self::ALTITUDE
    ];

    public function __construct(DataConnectionInterface $dataConnection)
    {
        $this->storageHelper = $dataConnection;
    }

    public function getMostRecentHundredForUser(int $userId): array
    {
        $sql = "SELECT * FROM `" . $this->table . "` WHERE `user_id` = ? ORDER BY `created_at` DESC LIMIT 100";
        return $this->storageHelper->query($sql, [$userId]);
    }

}