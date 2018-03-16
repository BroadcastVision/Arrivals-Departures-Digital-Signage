<?php
// Connect to database.
include_once("config.php");

// Get airline details.
function airline($mysqli, $ident){
	$result = $mysqli->query("SELECT IATA, Airline FROM airlines WHERE ICAO = '$ident' LIMIT 1");					
	$row = $result->fetch_assoc();
	if(mysqli_num_rows($result)==1)
		return array($row['IATA'], $row['Airline']);
	else
		return array($ident, "Not Available");
}

// Get airport.
if(isset($_POST['airport'])){
	$airport = addslashes($_POST['airport']);
}

// Get mode.
if(isset($_POST['mode'])){
	$mode = addslashes($_POST['mode']);
}

// Set timezone.
if(isset($_POST['continent']) && isset($_POST['city'])){
	$continent = addslashes($_POST['continent']);
	$city = addslashes($_POST['city']);	
	date_default_timezone_set($continent.'/'.$city);
}

$options = array(
	'trace' => true,
	'exceptions' => 0,
	'login' => '',
	'password' => '',
	);
				 
$params = array(
	'airport' => "$airport", 
	'howMany' => 100 ,
	'filter' => '',
	'offset' => 0,
	);
	
$client = new SoapClient('http://flightxml.flightaware.com/soap/FlightXML2/wsdl', $options);
$client->SetMaximumResultSize(array("max_size" => 50));

if($mode == 1){ // Arrivals.
	$result = $client->Enroute($params);
	$result_num = $result->EnrouteResult->next_offset;
}
elseif($mode == 2){ // Departures.
	$result = $client->Scheduled($params);
	$result_num = $result->ScheduledResult->next_offset;
}

$result_final = NULL;

for($i=0; $i<$result_num; $i++){
	if($mode == 1){
		$flight_ICAO = $result->EnrouteResult->enroute[$i]->ident;
		$location = $result->EnrouteResult->enroute[$i]->originCity;
		$time = $result->EnrouteResult->enroute[$i]->estimatedarrivaltime;
	}
	elseif($mode == 2){
		$flight_ICAO = $result->ScheduledResult->scheduled[$i]->ident;
		$location = $result->ScheduledResult->scheduled[$i]->destinationCity;
		$time = $result->ScheduledResult->scheduled[$i]->filed_departuretime;
	}

	//Split it to chars and numbers.
	$ICAO_char = preg_replace("/\d+$/","",$flight_ICAO);
	$ICAO_num = preg_replace('/[^0-9]/', '', $flight_ICAO);
	
	list($IATA, $airline_name) = airline($mysqli, $ICAO_char);
	
	$IATA_flight = $IATA.$ICAO_num;

	$result_final .= "<tr>
	<td>".$IATA_flight."</td>
	<td>".$airline_name."</td>
	<td>".$location."</td>
	<td><span>".date('H:i', $time)."</span></td>
</tr>\n";
}

echo json_encode(array("total" => $result_num, "result" => $result_final)); 
?>
