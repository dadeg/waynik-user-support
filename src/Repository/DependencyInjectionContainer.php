<?php

namespace Waynik\Repository;

use Waynik\Models\CheckinModel;
use Waynik\Models\UserModel;
use Waynik\Models\SingleUseTokenModel;

class DependencyInjectionContainer implements DependencyInjectionInterface
{
    private $mysqlConnectionInstance;
    private $phpMailerInstance;

    private function getCheckinModel()
    {
        $dataConnection = $this->make('MysqlConnection');
        return new CheckinModel($dataConnection);
    }

    private function getUserModel()
    {
        $dataConnection = $this->make('MysqlConnection');
        return new UserModel($dataConnection);
    }

    private function getSingleUseTokenModel()
    {
    	$dataConnection = $this->make('MysqlConnection');
    	return new SingleUseTokenModel($dataConnection);
    }
    
    private function getMysqlConnection()
    {
        if (!$this->mysqlConnectionInstance) {
            $this->mysqlConnectionInstance = new MysqlConnection();
        }
        return $this->mysqlConnectionInstance;
    }
    
    private function getPHPMailer()
    {
		$mail = new \PHPMailer();
		//$mail->SMTPDebug = 3;
		
		$mail->isSMTP();                                      
		$mail->Host = 'smtp.gmail.com';  
		$mail->SMTPAuth = true;                               
		$mail->Username = 'development@waynik.com';                 
		$mail->Password = 'gnzuywwemrqwtfdt'; 
		$mail->SMTPSecure = 'ssl';                           
		$mail->Port = 465;                                   
		            
		$mail->setFrom('do-not-reply@waynik.com', 'Waynik');

        return $mail;
    }

    public function make($className)
    {
        switch ($className) {
            case 'CheckinModel':
                return $this->getCheckinModel();
                break;
            case 'UserModel':
                return $this->getUserModel();
                break;
            case 'SingleUseTokenModel':
                return $this->getSingleUseTokenModel();
                break;
            case 'MysqlConnection':
                return $this->getMysqlConnection();
                break;
            case 'PHPMailer':
                return $this->getPHPMailer();
                break;
            default:
                throw new \Exception('There is no class strategy for class: ' . $className);
                break;
        }

    }
}
