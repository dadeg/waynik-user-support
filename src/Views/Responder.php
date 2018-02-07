<?php

namespace Waynik\Views;

use Psr\Http\Message\ResponseInterface;
use Aws\S3\S3Client;

/**
 * @property ResponseInterface response
 */
class Responder
{
	private $checkins;
	private $user;
	private $lastKnownLocation;

	public function __construct(array $data)
	{
		$this->checkins = $data['checkins'];
		/* @var \Waynik\Models\User $user */
		$this->user = $data['user'];
		$this->lastKnownLocation = $data['lastKnownLocation'];
	}

	private function timeSince(string $time)
	{
		$time = time() - strtotime($time); // to get the time since that moment
		$time = ($time<1)? 1 : $time;
		$tokens = array (
				31536000 => 'year',
				2592000 => 'month',
				604800 => 'week',
				86400 => 'day',
				3600 => 'hour',
				60 => 'minute',
				1 => 'second'
		);

		foreach ($tokens as $unit => $text) {
			if ($time < $unit) continue;
			$numberOfUnits = floor($time / $unit);
			return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
		}

	}
	
	private function getProfileImageUrl(string $userId)
	{
		$client = S3Client::factory(array(
				'credentials' => [
						'key' => 'AKIAIGYPNQHUPJXDGM7Q',
						'secret' => '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'
				],
				'region' => 'us-west-2',
				'version' => '2006-03-01'
		));
	
		$cmd = $client->getCommand('GetObject', [
				'Bucket' => 'waynik-user-profiles',
				'Key'    => $userId
		]);
	
		$request = $client->createPresignedRequest($cmd, '+2 minutes');
		$presignedUrl = (string) $request->getUri();
		// Get the actual presigned-url
		return $presignedUrl;
	
	}

	public function render()
	{
		$mostRecentLat = "";
		$mostRecentLong = "";
		$mostRecentTimestamp = "";
		if (count($this->checkins) > 0) {
			$mostRecentLat = $this->checkins[0]['latitude'];
			$mostRecentLong = $this->checkins[0]['longitude'];
			$mostRecentTimestamp = $this->checkins[0]['created_at'];
		}

		$checkinPointsHtml = "";
		$checkinHtml = "";
		$comma = "";
		foreach ($this->checkins as $checkin) {
			$checkinPointsHtml .= $comma . "{lat: " . $checkin['latitude'] . ", lng: " . $checkin['longitude'] . "}";
			$comma = ",";

			$checkinHtml .= "<p>Location at " . $checkin['created_at'] . " GMT: " . $checkin['latitude'] . ", " . $checkin['longitude'] . "</p>";
		}


		$userExtraFields = $this->user->getExtraFields();


		$step1Html = "<h1>Profile</h1>";
		$step1Html .= "<img width='200px' src='" . $this->getProfileImageUrl($this->user->getId()) . "' />";

		
		$step2Html = "<h1>Location</h1>";

		$mostRecentLat = $this->checkins[0]['latitude'];
		$mostRecentLong = $this->checkins[0]['longitude'];
		$mostRecentTimestamp = $this->checkins[0]['created_at'];
		
		$timeSinceSeen = $this->timeSince($mostRecentTimestamp);
		$step2Html .= "<div><b><div class='title'>Last Known Location:</div> ". $this->lastKnownLocation . "</b><br />lat/long: " . $mostRecentLat . ", " . $mostRecentLong . "<br>" . $timeSinceSeen . " ago<br><br></div>";

		$step2Html .= "<h1>Nature of Emergency</h1>";
		$step2Html .= "<div>Status: Unknown. Please contact individual to verify nature and status of emergency.</div>";

		$step2Html .= "<div>we need a disclaimer here</div>";
		$step2Html .= "<h1>Recent Location Data</h1>";
		$step2Html .= $checkinHtml;
		
		$contactInfoHtml = "";
		$decriptionHtml = "";
		$generalInfoHtml = "";


		// contact info
		$contactInfoHtml .= "<div><span class='title'>Name</span>: " . $this->user->getName() . "</div>";
		//$contactInfoHtml .= "<div><span class='title'>Email</span>: " . $this->user->getEmail() . "</div>";
		$contactInfoHtml .= "<div><span class='title'>Primary Phone</span>: " . $userExtraFields['primaryPhone'] . "</div>";
		$contactInfoHtml .= "<div><span class='title'>Secondary Phone</span>: " . $userExtraFields['secondaryPhone'] . "</div>";
		$contactInfoHtml .= "<div><span class='title'>Tertiary Phone</span>: " . $userExtraFields['tertiaryPhone'] . "</div>";
		//$contactInfoHtml .= "<div><div class='title'>Current Address:</div> " . $userExtraFields['currentAddressStreet'] . "<br>" . $userExtraFields['currentAddressStreet2'] . "<br>" . $userExtraFields['currentAddressCity'] . ", " . $userExtraFields['currentAddressState'] . " " . $userExtraFields['currentAddressCountry'] . "</div>";
		//$contactInfoHtml .= "<div><div class='title'>Permanent Address:</div> " . $userExtraFields['permanentAddressStreet'] . "<br>" . $userExtraFields['permanentAddressStreet2'] . "<br>" . $userExtraFields['permanentAddressCity'] . ", " . $userExtraFields['permanentAddressState'] . " " . $userExtraFields['permanentAddressCountry'] . "</div>";


		// description
		$decriptionHtml .= "<div><span class='title'>Gender</span>: " . $userExtraFields['gender'] . "</div>";
		$decriptionHtml .= "<div><span class='title'>Eye Color</span>: " . $userExtraFields['eyeColor'] . "</div>";
		$decriptionHtml .= "<div><span class='title'>Hair Color</span>: " . $userExtraFields['hairColor'] . "</div>";
		$decriptionHtml .= "<div><span class='title'>Height</span>: " . $userExtraFields['height'] . "</div>";
		$decriptionHtml .= "<div><span class='title'>Nationality</span>: " . $userExtraFields['nationality'] . "</div>";
		//$decriptionHtml .= "<div><span class='title'>Languages Spoken</span>: " . $userExtraFields['languagesSpoken'] . "</div>";

		//$generalInfoHtml .= "<div><span class='title'>General Info</span>: " . $userExtraFields['generalInfo'] . "</div>";
		//$generalInfoHtml .= "<div><span class='title'>Travel Plans</span>: " . $userExtraFields['travelPlans'] . "</div>";




		$html = "<html><link href='/user-support/css/main.css' rel='stylesheet'><body>";

		$html .= '<div class="infowrapper">';
		$html .= '<div class="infobox">' . $step1Html . '</div>';
		$html .= '<div class="infobox">' . $contactInfoHtml . '</div>';
		$html .= '<div class="infobox">' . $decriptionHtml . '</div>';
		$html .= '<div class="infobox">' . $generalInfoHtml . '</div>';
		$html .= '<div class="infobox">' . $step2Html . '</div>';


		$html .= '</div>';

		$html .= '<div class="infowrapper">';

		$html .= '<h1>Map of Recent Location Check-ins</h1>';
		$html .= '<div id="map" class="infobox"></div>';
		$html .= '</div>';

		//         $html .= "<div class='checkins'>";
		//         $html .= $checkinHtml;

		//         $html .= "</div>";
		$html .= "<script>";

		$html .= "function initMap() {";
		$html .= "  var map = new google.maps.Map(document.getElementById('map'), {";
		$html .= "    zoom: 11,";
		$html .= "    center: {lat: " . $mostRecentLat . ", lng: " . $mostRecentLong . "},";
		$html .= "    mapTypeId: 'terrain'";
		$html .= "  });";

		$html .= "  var knownLocations = [";
		$html .= $checkinPointsHtml;
		$html .= "  ];";
		$html .= "  var locationsPath = new google.maps.Polyline({";
		$html .= "    path: knownLocations,";
		$html .= "    geodesic: true,";
		$html .= "    strokeColor: '#FF0000',";
		$html .= "    strokeOpacity: 1.0,";
		$html .= "    strokeWeight: 2";
		$html .= "  });";

		$html .= "  locationsPath.setMap(map);";
		$html .= "  var marker = new google.maps.Marker({";
		$html .= "  position: {lat: " . $mostRecentLat . ", lng: " . $mostRecentLong . "},";
		$html .= "  title: '#most-recent-location',";
		$html .= "  map: map";
		$html .= "});";
		$html .= "var infowindow = new google.maps.InfoWindow({";
		$html .= "  content: 'Last known location: " . $mostRecentLat . ", " . $mostRecentLong . " at " . $mostRecentTimestamp . " GMT'";
		$html .= "});";
		$html .= "";
		$html .= "marker.addListener('click', function() {";
		$html .= "  infowindow.open(map, marker);";
		$html .= "});";
		$html .= "}";
		$html .= '</script>';
		$html .= '<script async defer ';
		$html .= 'src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB2mi7rSEn4zhhPs21oacNp7WN4FB5AG2Y&callback=initMap">';
		$html .= '</script>';
		$html .= "</body></html>";

		return $html;
	}
}