<?php
$mode = 2;
$airport = "LHR";
$airport_title = "Heathrow Airport";
$monitor_listing_num = 19;
$monitor_location_weather = "London";

if($mode==1){ //Arrivals.
	$title = "Arrivals";
	$icon = "fa-plane fa-rotate-90";
	$direction = "From";
}
elseif($mode==2){ //Departures.
	$title = "Departures";
	$icon = "fa-plane";
	$direction = "Destination";
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title> Arrivals/Departures Signage System </title>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet">
<link href='https://fonts.googleapis.com/css?family=PT+Sans' rel='stylesheet' type='text/css'>
<link rel="stylesheet" media="screen" href="css/main.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
</head>
<body>	
	<div id="container">
		
		<div id="header">
			<div id="title"><i class="fa <?php echo $icon; ?>" aria-hidden="true"></i> <?php echo $title;?> [<span id="page"></span>/<span id="total"></span>]</div>
			<div id="location"><?php echo $airport_title; ?></div>
		</div>
		
		<div id="main">
			<table>
				<thead>
					<tr>
						<th width="14%">Flight</th>
						<th width="36%">Carrier</th>
						<th width="40%"><?php echo $direction; ?></th>
						<th width="10%">Time</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>

		<div id="info2">
			<div id="location">
				<strong><?php echo $monitor_location_weather; ?> Weather</strong>
			</div>
			<div id="weather_info">
				<ul>
					<li></li>
					<li><strong>Wind:</strong> <span id="wind"></span> m/s <span id="wind_direction">↑</span></li>
					<li><strong>Humidity:</strong> <span id="humidity"></span>%</li>
					<li><strong>UV Index:</strong> <span id="uv"></span></li>
				</ul>
			</div>
		</div>
		
		<div id="info1">			
			<div id="weather">
				<div><canvas id="icon" height="100" width="100"></canvas></div>
				<div><strong><span id="temp"></span>°C</strong> <span id="summary"></span></div>
			</div>
			<div id="datetime">
				<span id="date"></span>
				<span id="time"></span>
			</div>
		</div>
		
	</div>
	
	<!--<div id="branding"></div>-->
	
<script language="javascript" type="text/javascript" src="https://code.jquery.com/jquery-latest.min.js"></script> 
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/skycons/1396634940/skycons.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.13/moment-timezone-with-data.min.js"></script>
<script language="javascript">	
	// Remove Cursor.
	//document.body.style.cursor = 'none';
	
	// Global Variables.
	var language = 'en';
	var city = 'London';
	var continent = 'Europe';		
	var url = 'https://api.forecast.io/forecast/';
	var apiKey = '';
	var units = 'si';
	var latitude = 0;
	var longitude = 0;
	var total, result, total_batch;
	var tmp_count = 0;
	var batch = new Array();

	// API forecast.io.
	function weather(){
		$(function() {
			var data, uv_class, wind_direction;
			
			$.getJSON(url + apiKey + "/" + latitude + "," + longitude + "?units=" + units + "&lang=" + language + "&callback=?", function(data) {
				
				var uv = data.currently.uvIndex;
								
				if(uv >=0 && uv <=2)
					uv_class = "uv0";
				else if(uv >=3 && uv <=5)
					uv_class = "uv1";
				else if(uv >=6 && uv <=7)
					uv_class = "uv2";
				else if(uv >=8 && uv <=10)
					uv_class = "uv3";
				else
					uv_class = "uv4";
				
				$('#temp').html(Math.round(data.currently.temperature));
				$('#humidity').html(Math.round(data.currently.humidity*100));
				$('#wind').html(Math.round(data.currently.windSpeed));
				$('#uv').attr('class', uv_class);
				$('#uv').html(uv);
				$('#summary').html(data.currently.summary);				
				
				wind_direction = data.currently.windBearing/2;
				$('#wind_direction').css({ 'display':'inline-block', '-webkit-transform': 'rotate('+wind_direction+'deg)', 'transform': 'rotate('+wind_direction+'deg)' });
				
				var skycons = new Skycons({"color": "black"});
				skycons.add("icon", data.currently.icon);
				skycons.play();
			});
		});
	}
	
	// Fetch instantly.
	weather();
	// Get weather every 2 min.
	setInterval( weather, 3600000 ); //2*1000*60
	
	
	// Get time and date from moments();
	$(document).ready(function() {
		var interval = setInterval(function() {
			moment.locale(language);
			var momentNow = moment();
			momentNow.tz(continent+"/"+city).format();
			$('#time').html(momentNow.format('HH:mm:ss'));
			$('#date').html(momentNow.format('DD/MM/YYYY'));
		}, 100);
	});	
	
	// Fetch Flightaware data.
	function flightaware(){
        $.ajax({
            url: 'process.php',
			type: 'post',
			data: { airport: '<?php echo $airport;?>', mode: '<?php echo $mode;?>', 'continent': continent, 'city': city },
            success: function(data) {		
				var arr = JSON.parse(data);
				total = arr["total"];
				result = arr["result"];
				// Call batch generator.
				batch_generator();
				// Display instantly results.
				tmp_count=0; // Intialize counter.
				$('tbody').html(batch[tmp_count]);
				tmp_count=1;
				// Update current page number.
				$('#page').html(tmp_count);
            }
        });
	};
   
	// Fetch instantly.
	flightaware();
	// Get flightaware data every 1 hour.
	setInterval( flightaware, 3600000 ); //60*1000*60
	
	// Build array with results.
	function batch_generator(){
		// Number of batches.
		total_batch = Math.ceil(total/<?php echo $monitor_listing_num; ?>);
		
		// Update total page number.
		$('#total').html(total_batch);
		
		// Split lines by brake.
		var lines = result.split("\n");
		
		var tmp = "";				

		for(var i=0;i<total_batch;i++){
			for(var j=0;j<<?php echo $monitor_listing_num*6; ?>;j++){
				tmp = tmp + lines[j + (i*<?php echo $monitor_listing_num*6; ?>)] + "\n";
			}
			batch[i] = tmp;					
			tmp = "";				
		}	
	}
	
	// Update table every 15 seconds.
	setInterval(function(){
		$('tbody').html(batch[tmp_count]);
		tmp_count++;
		
		// Update current page number.
		$('#page').html(tmp_count);
		
		if(tmp_count == total_batch)
			tmp_count = 0;
	}, 15000);
	
	
	// Branding Display Control.
	$('#branding').hide();
	/*setInterval(function(){
		$('#branding').show().delay(10000).hide(0);
	}, 60000)*/
	
</script>

</body></html>