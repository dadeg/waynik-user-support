<?php

namespace Waynik\Views;

use Psr\Http\Message\ResponseInterface;

/**
 * @property ResponseInterface response
 */
class Support
{
    private $checkins;
    private $user;
    private $lastKnownLocation;
    private $showEmailForward;

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
        
        
        $step1Html = "<h1>Customer Profile</h1>";
        if ($this->user->getName()) {
        	$step1Html .= "<div><span class='title'>Name</span>: " . $this->user->getName() . "</div>";
        }
        if ($userExtraFields['primaryPhone']) {
        	$step1Html .= "<div><span class='title'>Primary Phone</span>: " . $userExtraFields['primaryPhone'] . "</div>";
        }
        
        $mostRecentLat = $this->checkins[0]['latitude'];
        $mostRecentLong = $this->checkins[0]['longitude'];
        $mostRecentTimestamp = $this->checkins[0]['created_at'];
        
        $timeSinceSeen = $this->timeSince($mostRecentTimestamp);
        
        if ($mostRecentLat) {
        	$step1Html .= "<div><b><div class='title'>Last Known Location:</div> ". $this->lastKnownLocation . "</b><br />lat/long: " . $mostRecentLat . ", " . $mostRecentLong . "<br>" . $timeSinceSeen . " ago<br><br></div>";
        }
        
        $step2Html = "";
        if ($userExtraFields['primaryEmergencyContactPhone']) {
        	$step2Html .= "<h1>First Emergency Contact</h1>";
        	$step2Html .= "<div>Relation: " . $userExtraFields['primaryEmergencyContactRelation'] . "<br>Name: " . $userExtraFields['primaryEmergencyContactName'] . "<br>Phone: " . $userExtraFields['primaryEmergencyContactPhone'] . "<br>Email: " . $userExtraFields['primaryEmergencyContactEmail'] . "</div>";
        	
        }
        if ($userExtraFields['secondaryEmergencyContactPhone']) {
        	$step2Html .= "<h1>Second Emergency Contact</h1>";
        	$step2Html .= "<div>Relation: " . $userExtraFields['secondaryEmergencyContactRelation'] . "<br>Name: " . $userExtraFields['secondaryEmergencyContactName'] . "<br>Phone: " . $userExtraFields['secondaryEmergencyContactPhone'] . "<br>Email: " . $userExtraFields['secondaryEmergencyContactEmail'] . "</div>";
        	
        }
        
        $showEmailForwardHtml = "<h1>Send Notification Email</h1>";
        $showEmailForwardHtml .= "<form method='POST' action='/user-support/forward'>";
        $showEmailForwardHtml .= "Email provided by emergency contact: <input type='text' name='email' />";
        $showEmailForwardHtml .= "<input type='hidden' name='userId' value='" . $this->user->getId() . "' />";
        $showEmailForwardHtml .= "<input type='submit'>";
        $showEmailForwardHtml .= "</form>";
       
        $html = "<html><link href='/user-support/css/main.css' rel='stylesheet'><body>";

        $html .= '<div class="infowrapper">';

        $html .= '<div class="infobox">' . $step1Html . '</div>';

        $html .= '<div class="infobox">' . $step2Html . '</div>';
        
        
        
        $html .= '</div>';
        
        $html .= '<div class="infowrapper">';
        $html .= '<div class="infobox">' . $showEmailForwardHtml . '</div>';
        $html .= '</div>';
        
//         $html .= "<div class='checkins'>";
//         $html .= $checkinHtml;

//         $html .= "</div>";
        
        $html .= "</body></html>";

        return $html;
    }
}