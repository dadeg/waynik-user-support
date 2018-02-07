<?php

namespace Waynik\Controllers;

use Zend\Diactoros\Response\HtmlResponse;
use Waynik\Views\Responder as ResponderView;
use Waynik\Models\User as UserObject;
use Waynik\Models\UserModel;

class Responder extends CallCenter implements ControllerInterface
{

	protected function displayUserInfo(UserObject $user, bool $showEmailForward = false)
	{

		/** @var \Waynik\Models\CheckinModel $checkinModel */
		$checkinModel = $this->dependencyInjectionContainer->make('CheckinModel');
		$checkins = $checkinModel->getMostRecentHundredForUser($user->getId());

		$this->sendAlertToMichael($user);

		//get most recent physical address
		$bestLocation = $this->getLocation($checkins[0]);

		$data = [
				"checkins" => $checkins,
				"user" => $user,
				"lastKnownLocation" => $bestLocation
		];

		$view = new ResponderView($data);

		$response = new HtmlResponse($view->render());
		return $response;
	}

	/**
	 * @param UserObject user
	 */
	private function sendAlertToMichael(UserObject $user) {
		//send email alert to mbell so he knows someone is looking at the page!
		$mail = $this->dependencyInjectionContainer->make('PHPMailer');
		$mail->addAddress('dan@gmail.com');
		$mail->addAddress('mbell@waynik.com');
		$mail->addAddress(''); // mbell's phone number
		$mail->addReplyTo('development@waynik.com', 'Waynik');

		$mail->isHTML(true);

		$mail->Subject = 'Responder profile has been viewed for user ' . $user->getId();

		$body = 'Link to profile page: https://www.waynik.com/user-support/responder/'
				. UserModel::makeIdHash($user->getId())
				. ' passcode: ' . UserModel::makePasscode($user->getId());

				$mail->Body    = $body;
				$mail->AltBody = $body;

				$mail->send();
	}
}
