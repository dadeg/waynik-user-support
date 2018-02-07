<?php

namespace Waynik\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Waynik\Repository\DependencyInjectionInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Waynik\Views\Support as SupportView;
use Waynik\Views\AskPasscode as AskPasscodeView;
use Waynik\Models\SingleUseToken;
use Waynik\Models\User as UserObject;
use Waynik\Models\UserModel;

class CallCenter implements ControllerInterface
{
    protected $dependencyInjectionContainer;

    public function __construct(DependencyInjectionInterface $dependencyInjector)
    {
        $this->dependencyInjectionContainer = $dependencyInjector;
    }

    public function handle(ServerRequestInterface $request)
    {
   
    	/* @var \Waynik\Models\SingleUseTokenModel $singleUseTokenModel */
    	$singleUseTokenModel = $this->dependencyInjectionContainer->make("SingleUseTokenModel");
    	
    	if ($singleUseTokenModel->exists($request->getAttributes()['id'])) {
    		
    		$singleUseToken = $singleUseTokenModel->useToken($request->getAttributes()['id']);
    		
    		return $this->showUserFromTokenAction($singleUseToken);
    	}
    	
        if (!array_key_exists("passcode", $request->getParsedBody())) {
        	return $this->askPasscodeAction($request);
        }
        
        return $this->showUserFromPasscodeAction($request);
    }
        
    private function askPasscodeAction(ServerRequestInterface $request) {
    	$view = new AskPasscodeView($request->getAttributes()['id']);
    	$response = new HtmlResponse($view->render());
    	return $response;
    }
    
    private function showUserFromPasscodeAction(ServerRequestInterface $request) {

    	/** @var \Waynik\Models\UserModel $userModel */
    	$userModel = $this->dependencyInjectionContainer->make('UserModel');
    	$user = $userModel->getByPasscode($request->getAttributes()['id'], (int) $request->getParsedBody()['passcode']);
    	
    	return $this->displayUserInfo($user);
    }

    private function showUserFromTokenAction(SingleUseToken $singleUseToken) 
    {
    	
    	/** @var \Waynik\Models\UserModel $userModel */
    	$userModel = $this->dependencyInjectionContainer->make('UserModel');
    	$user = $userModel->get($singleUseToken->getUserId());
    	 
    	return $this->displayUserInfo($user);
    }
    
    protected function displayUserInfo(UserObject $user)
    {
    	
    	/** @var \Waynik\Models\CheckinModel $checkinModel */
    	$checkinModel = $this->dependencyInjectionContainer->make('CheckinModel');
    	$checkins = $checkinModel->getMostRecentHundredForUser($user->getId());
    	
    	$this->sendAlertToMichael($user);
    	$bestLocation = $this->getLocation($checkins[0]);
    		
    	$data = [
    			"checkins" => $checkins,
    			"user" => $user,
    			"lastKnownLocation" => $bestLocation
    	];
    	
    	$view = new SupportView($data);
    	
    	$response = new HtmlResponse($view->render());
    	return $response;
    }
	
	/**
	 * @param UserObject user
	 */
	private function sendAlertToMichael(UserObject $user) {
		//send email alert to mbell so he knows someone is looking at the page!
    	$mail = $this->dependencyInjectionContainer->make('PHPMailer');
    	$mail->addAddress('dan.degreef@gmail.com');
    	$mail->addAddress('mbell@waynik.com');
    	$mail->addAddress('3126182200@tmomail.net'); // mbell's phone number
    	$mail->addReplyTo('development@waynik.com', 'Waynik');
    	
    	$mail->isHTML(true);                                  
    	
    	$mail->Subject = 'Support profile has been viewed for user ' . $user->getId();
    	
    	$body = 'Link to profile page: https://www.waynik.com/user-support/call-center/' 
    					. UserModel::makeIdHash($user->getId())
    					. ' passcode: ' . UserModel::makePasscode($user->getId());
    	
    	$mail->Body    = $body;
    	$mail->AltBody = $body;
    	
    	$mail->send();
	}
	
	protected function getLocation($checkin)
	{
		$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng="
				. $checkin['latitude']
				. ","
						. $checkin['longitude']
						. "&key=AIzaSyB2mi7rSEn4zhhPs21oacNp7WN4FB5AG2Y";
	
						$ch = curl_init();
						$timeout = 10;
						curl_setopt($ch, CURLOPT_URL, $url);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
						$locationJson = curl_exec($ch);
						curl_close($ch);
	
						$locations = json_decode($locationJson);
						return $locations->results[0]->formatted_address;
							
	}
}