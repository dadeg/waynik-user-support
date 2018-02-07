<?php

namespace Waynik\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Waynik\Repository\DependencyInjectionInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Waynik\Models\SingleUseToken;
use Waynik\Models\User as UserObject;
use Waynik\Models\UserModel;

class EmailForward implements ControllerInterface
{
	private $dependencyInjectionContainer;

	public function __construct(DependencyInjectionInterface $dependencyInjector)
	{
		$this->dependencyInjectionContainer = $dependencyInjector;
	}

	public function handle(ServerRequestInterface $request)
	{
		$email = $request->getParsedBody()['email'];
		$userId = $request->getParsedBody()['userId'];
		
		if (!$email || !$userId) {
			throw new \Exception("error: no email entered or no user found. Press the back button and try again.");
		}
		
		/** @var \Waynik\Models\UserModel $userModel */
		$userModel = $this->dependencyInjectionContainer->make('UserModel');
		$user = $userModel->get($userId);

		$singleUseTokenModel = $this->dependencyInjectionContainer->make('SingleUseTokenModel');
		$singleUseToken = $singleUseTokenModel->getByUser($user);
		
		$this->sendEmail($email, $singleUseToken, $user);
		
		return new HtmlResponse("Email sent. Press the back button in your browser to return to the support page.");

	}


	/**
	 * @param UserObject user
	 */
	private function sendEmail(string $email, SingleUseToken $token, UserObject $user) {
		//send email alert to mbell so he knows someone is looking at the page!
		$mail = $this->dependencyInjectionContainer->make('PHPMailer');
		$mail->addAddress($email);
		$mail->addBcc('mbell@waynik.com');
		$mail->addBcc('development@waynik.com');
		$mail->addBcc('dan.degreef@gmail.com');
		
		$mail->isHTML(true);
		 
		$mail->Subject = "EMERGENCY - Urgent Response Required for " . $user->getName();
		 
		$url = "https://www.waynik.com/user-support/responder/" . $token->getToken();
		$body = "<p>URGENT: Waynik received an emergency alert from our user " . $user->getName() . ". Additional details about the status and nature of the emergency are not known at this time. 
</p><p>
Click on the following link for additional details about the location and last known whereabouts of " . $user->getName() . ". 
</p><p>
Click here for details: <a href='" . $url . "' target='_blank'>" . $url . "</a>	
</p><p>
Please respond directly to " . $user->getName() . " with the contact information listed in the above profile. 
</p><p>
Waynik is an emergency response service that provides real-time location data of members to their designated emergency response contacts. 
</p><p>
Do not reply to this email. Reference number: " . $token->getToken() . "</p>";
				 
				$mail->Body    = $body;
				$mail->AltBody = $body;
				 
				$mail->send();
	}
}