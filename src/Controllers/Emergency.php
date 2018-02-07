<?php

namespace Waynik\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Waynik\Repository\DependencyInjectionInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Waynik\Views\Json as JsonView;
use Waynik\Models\SingleUseToken;
use Waynik\Models\UserModel;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;

class Emergency
{
	private $dependencyInjectionContainer;
	const SECRET_KEY_TO_STOP_STRANGERS = "secret";

	public function __construct(DependencyInjectionInterface $dependencyInjector)
	{
		$this->dependencyInjectionContainer = $dependencyInjector;
	}

	/**
	 * You must hit this endpoint from AWS SNS because it will not work otherwise.
	 * Create a new single use url and send email alerting call center about the new emergency.
	 * @param ServerRequestInterface $request
	 * @throws \Exception
	 * @return \Zend\Diactoros\Response\HtmlResponse
	 */
	public function handle(ServerRequestInterface $request)
	{
		$postData = $request->getParsedBody() ?? [];
		$queryData = $request->getQueryParams() ?? [];
		$hackToGetJsonBody = json_decode(file_get_contents('php://input'), true) ?? [];
		$requestData = array_merge($postData, $queryData, $hackToGetJsonBody);

		$snsMessage = new Message($requestData);

		// Validate the message
		$validator = new MessageValidator();
		if (!$validator->isValid($snsMessage)) {
			throw new \Exception("Invalid message.", 401);
		}

		$snsMessageData = $snsMessage->toArray();
		/**
		 * This is for AWS SNS topics
		 */
		if (array_key_exists('SubscribeURL', $snsMessageData)) {
			error_log('subscribing to new topic!');
			$this->confirmSnsSubscription($snsMessageData['SubscribeURL']);
			$response = new JsonResponse("successful subscription");
			$view = new JsonView($response);
			$view->render();
			return;
		}

		$payload = $snsMessageData['Message'];
		$payload = json_decode($payload, true);

		if (!array_key_exists('apiKey', $payload) || $payload['apiKey'] !== self::SECRET_KEY_TO_STOP_STRANGERS) {
			throw new \Exception("please provide an access key.", 401);
		}

		if (!array_key_exists('userId', $payload)) {
			throw new \Exception("userId is a required parameter.", 400);
		}

		$userId = $payload['userId'];

		/** @var \Waynik\Models\UserModel $userModel */
		$userModel = $this->dependencyInjectionContainer->make('UserModel');
		$user = $userModel->get($userId);

		$singleUseTokenModel = $this->dependencyInjectionContainer->make('SingleUseTokenModel');
		$singleUseToken = $singleUseTokenModel->create($user);

		$this->sendEmergencyEmail($singleUseToken);

		return new HtmlResponse("Email sent");

	}

	private function sendEmergencyEmail(SingleUseToken $singleUseToken)
	{
		// This is the beginning of an emergency! alert Call center and Michael!!!
		$mail = $this->dependencyInjectionContainer->make('PHPMailer');
		$mail->addAddress('1210001509@arcoreengine-dot-conversionsupportlive-hrd.appspotmail.com');
		$mail->addBcc('dan.degreef@gmail.com');
		$mail->addBcc('mbell@waynik.com');
		$mail->addReplyTo('info@waynik.com', 'Waynik');

		$mail->isHTML(true);

		$mail->Subject = "EMERGENCY - Reference Code: " . $singleUseToken->getToken() . " for Waynik Acct: 1210001509";

		$url = "https://www.waynik.com/user-support/call-center/" . $singleUseToken->getToken();
		$textBody = "Click on link for customer profile details: <a href='" . $url . "' target='_blank'>" . $url . "</a>";

		$mail->Body    = $textBody;
		$mail->AltBody = $textBody;

		$mail->send();
	}

    private function confirmSnsSubscription(string $subscriptionUrl)
    {
    	$ch = curl_init($subscriptionUrl);
    	curl_exec($ch);
    }
}
