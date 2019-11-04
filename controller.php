<?php
ini_set('max_execution_time', '300'); //300 seconds = 5 minutes

require_once 'vendor/autoload.php';
use JJG\Ping as Ping;

define('USER_COLOR_FORM',0); //enable HTML color picker form
define('USER_SPEED_FORM',0); //enable HTML speed slider form
define('CONTROLLER_IP', "192.168.137.181"); // put your controller IP here
define("CONTROLLER_PORT", 8189); // Don't change that

$mono_animations = [
	'meteor' => 'cd',
	'breathing' => 'ce',
	'wave' => 'd1',
	'catch up' => 'd4',
	'static' => 'd3',
	'stack' => 'cf',
	'flash' => 'd2',
	'flow' => 'd0'
];

$chips_types = [
	'SM16703' => '00',
	'TM1804' => '01',
	'UCS1903' => '02',
	'WS2811' => '03',
	'WS2801' => '04',
	'SK6812' => '05',
	'LPD6803' => '06',
	'LPD8806' => '07',
	'APA102' => '08',
	'APA105' => '09',
	'DMX512' => '0a',
	'TM1914' => '0b',
	'TM1913' => '0c',
	'P9813' => '0d',
	'INK1003' => '0e',
	'P943S' => '0f',
	'P9411' => '10',
	'P9413' => '11',
	'TX1812' => '12',
	'TX1813' => '13',
	'GS8206' => '14',
	'GS8208' => '15',
	'SK9822' => '16',
	'TM1814' => '17',
	'SK6812_RGBW' => '18',
	'P9414' => '19',
	'P9412' => '1a'
];

$colors_orders = [
	'RGB' => '00',
	'RBG' => '01',
	'GRB' => '02',
	'GBR' => '03',
	'BRG' => '04',
	'BGR' => '05'
];

function dec_to_even_hex($decimal, $make_it_2_bytes = false){
	$decimal_in_hex= dechex($decimal);
	// echo $decimal_in_hex;
	if($make_it_2_bytes){
		$i = 4 - strlen($decimal_in_hex);
		for($i; $i > 0; $i--){
			$decimal_in_hex = str_replace($decimal_in_hex, "0".$decimal_in_hex, $decimal_in_hex);
		}
	}else{
		if(strlen($decimal_in_hex) == 1){
			$decimal_in_hex = str_replace($decimal_in_hex, "0".$decimal_in_hex, $decimal_in_hex);
		}
	}
	return $decimal_in_hex;	
}

function transmit_data(
		$data, // the command and it's value(if it has a value)
		$expect_response, // set to true if you expect a response
		$response_length // how long the response is
	 ){
	// open the connection
	$fp = fsockopen(CONTROLLER_IP, CONTROLLER_PORT, $errno, $errstr, 30);
	if (!$fp) {
		   echo "$errstr ($errno)<br />\n";
	} else {
		//remove white spaces
		$cleaned_data = str_replace(" ", '', $data);

		// send the command
		fwrite($fp, hex2bin($cleaned_data));

		// only try to read if you expect a response from the it
		// if you tried to read when there's not response will be 
		// sent back, it will timeout 
	    if($expect_response){
	    	$response = fread($fp, $response_length);
	    }

	    // close the connection
	    fclose($fp);
	    
	    // return the response if you expect a response
	    if($expect_response){
	    	return $response;
	    }
	}
}

function is_device_ready(){
	return transmit_data("38 000000 2f 83", true, 1);
}

function send_data($data = "", $expect_response = false, $response_length = 0){

	$response = transmit_data($data, $expect_response, $response_length);

	// wait for the device to get ready

	// while(!is_device_ready()){

	// }
	return $response;
}

function change_color($color){
	// color in hex format

	if(USER_COLOR_FORM){
		if(isset($_GET['do']) && $_GET['do']=="do"){
			$color =str_replace("#", '', $_GET['color']); //remove the # sign
			send_data("38".$color."22 83");
		}
	}else{
		if(strpos($color, '#') !== false){
			$color = str_replace('#', '', $color);
		}
		send_data("38".$color."22 83");
	}
}

function change_speed($speed){
	// value 0 to 255
	if(USER_SPEED_FORM){
		if(isset($_GET['do']) && $_GET['do']=="do"){
			$usr_speed_val = dec_to_even_hex($_GET['speed']);
		
			$speed_val="38".$usr_speed_val."00000383";
		    send_data($speed_val);
		}
	}else{
		send_data("38 ". dec_to_even_hex($speed) ." 0000 03 83");
	}
}

function change_brightness($brightness = 255){
	// value 0 to 255
    send_data("38 ". dec_to_even_hex($brightness) ." 0000 2a 83");
}

function get_name(){
    $result = send_data("38 000000 77 83", true, 18);
    echo $result."<br>";
}

function get_device_raw_settings(){
    $result = send_data("38 000000 10 83", true, 17);
    return bin2hex($result)."<br>";
}

function get_device_settings(){
	global $mono_animations;
	global $chips_types;
	global $colors_orders;

	$raw_settings = get_device_raw_settings();

	$current_animation = array_search(substr($raw_settings, 4, 2), $mono_animations);
	if(empty($current_animation)){
		$current_animation =hexdec(substr($raw_settings, 4, 2));
	}

	$chip_type = array_search(substr($raw_settings, 26, 2), $chips_types);
	$color_order = array_search( substr($raw_settings, 10, 2), $colors_orders);
	$turned_on = (hexdec(substr($raw_settings, 2, 2)) ? 'On': 'Off');
	$current_color  = "<span style='color: #"; 
	$current_color .= substr($raw_settings, 20, 6); 
	$current_color .= ";'>" . substr($raw_settings, 20, 6) . "</span>"; 
	

	$settings = [
		'turned_on' => $turned_on,
		'current_animation' => $current_animation,
		'animation_speed' => hexdec(substr($raw_settings, 6, 2)),
		'current_brightness' => hexdec(substr($raw_settings, 8, 2)),
		'color_order' => $color_order,
		'leds_per_segment ' => hexdec(substr($raw_settings, 12, 4)),
		'segments' => hexdec(substr($raw_settings, 16, 4)),
		'current_color' => $current_color,
		'chip_type' => $chip_type ,
		'recorded_patterns' => hexdec(substr($raw_settings, 28, 2)),
		'white_channel_brightness' => hexdec(substr($raw_settings, 30, 2))
	];

	$pretty_settings= "";
	foreach ($settings as $key => $value) {
		$pretty_settings .= ucfirst(str_replace("_", ' ' , $key)) . ": " . $value . "<br>";
	}
	return $pretty_settings;
}

function change_mono_color_animation($index){
	/* Mono animations
		0xcd => Meteor
		0xce => Breathing
		0xd1 => Wave
		0xd4 => Catch up
		0xd3 => Static
		0xcf => Stack
		0xd2 => Flash
		0xd0 => Flow
	*/

	send_data("38 ". $index ." 0000 2c 83"); //Meteor
}

function change_mixed_colors_animation($index){
	// 0x00 (first animation 1) -> 0xb3 (last animation 180)
	send_data("38". dec_to_even_hex($index - 1) ."0000 2c 83"); // specific animation
}

function enable_multicolor_animation_auto_mode(){
	send_data("38 000000 06 83"); // auto mode
}

function toggle_off_on(){
	send_data("38 000000 aa 83");
}

function change_name($name = "Ice Cold"){
	// note you can't send new commands to the box 
	// until it completely finish saving the new name
	// so give it sometime until it finish then send any 
	// new commands you wish typically 1 to 3 seconds 

	$result = send_data("38 000000 14 83", true, 1); // the order to change the name
	
	if($result == 1) {
		// the new name 
		// 10 letters max
		send_data(bin2hex($name)); 
	}
}

// Maximum 2048 LEDs 
// leds per segment * segments

function set_number_of_segments($leds = 10){
	send_data("38". dec_to_even_hex($leds, true) ."00 2e 83");
}

function set_number_of_leds_per_segments($segs = 1){
	// maximum 300
	send_data("38". dec_to_even_hex($segs, true) ."00 2d 83");
}

function set_chip_type($type){
	/* Hex code for chip types
		0x00 (00) -> SM16703
		0x01 (01) -> TM1804
		0x02 (02) -> UCS1903
		0x03 (03) -> WS2811
		0x04 (04) -> WS2801
		0x05 (05) -> SK6812
		0x06 (06) -> LPD6803
		0x07 (07) -> LPD8806
		0x08 (08) -> APA102
		0x09 (09) -> APA105
		0x0a (10) -> DMX512
		0x0b (11) -> TM1914
		0x0c (12) -> TM1913
		0x0d (13) -> P9813
		0x0e (14) -> INK1003
		0x0f (15) -> P943S
		0x10 (16) -> P9411
		0x11 (17) -> P9413
		0x12 (18) -> TX1812
		0x13 (19) -> TX1813
		0x14 (20) -> GS8206
		0x15 (21) -> GS8208
		0x16 (22) -> SK9822
		0x17 (23) -> TM1814
		0x18 (24) -> SK6812_RGBW
		0x19 (25) -> P9414
		0x1a (26) -> P9412
	*/
	send_data("38 $type 0000 1c 83");
}

function set_color_order($order){
	/* Hex code for color order 
		0x00 (00) -> RGB
		0x01 (01) -> RBG
		0x02 (02) -> GRB
		0x03 (03) -> GBR
		0x04 (04) -> BRG
		0x05 (05) -> BGR
	*/
	send_data("38 $order 0000 3c 83");
}

function change_white_channel_brightness($brightness="255"){
	// value 0 to 255
	send_data("38 ". dec_to_even_hex($brightness) ." 0000 08 83");
}

function find_device(){
	$local_ip = '192.168.137.180';
	$ping = new Ping($local_ip, 32);
	$ping->setTimeout(1);
	$ping->setPort(8189);

	for($i=1; $i<255; $i++){
		$last_dot_position = strrpos($local_ip, ".");
		$device_address = substr($local_ip, 0, $last_dot_position+1);
		$ip_to_ping = $device_address . $i;
		// echo "1- ".$last_dot_position . "<br>";
		// echo "2- ". $device_address . "<br>";
		echo "3-". $ip_to_ping . "<br>";
	
		$ping->setHost($ip_to_ping);
		$latency = $ping->ping('fsockopen');
		if($latency !== false){
			echo "$ip_to_ping is online <br>";
		}
	}

}

// change_color('ffff00');
// change_mono_color_animation($mono_animations['stack']);
// change_speed(150);
// change_brightness(255);
// set_chip_type($chips_types['WS2811']);
// change_white_channel_brightness(255);
// set_number_of_segments(13);
// set_number_of_leds_per_segments(12);
// change_mixed_colors_animation(75);
// enable_multicolor_animation_auto_mode();
// toggle_off_on();
// change_name();
// get_name();
// echo get_device_raw_settings();
// echo get_device_settings();
// find_device();

?>


<!DOCTYPE html>
	<html>
	<head>
		<title></title>
		<style type="text/css">
			.color_selector, .speed_selector{
				margin-left: 250px
			}	
			input{
				width: 300px;
				height: 150px;
				font-size: 25px;
				vertical-align: top;
			}

			span{font-size: 25px;}
		</style>
	</head>
	<body>
	<?php 
		if(USER_COLOR_FORM){
			echo '<div class="color_selector">
			<form action="" method="get">
				<input type="color" name="color">
				<input type="submit" name="do" value="do">
			</form>
			</div>';
		}
		if(USER_SPEED_FORM){
			echo '
			<div class="speed_selector">
				<form action="" method="get">
					<input type="range" name="speed" min="0" max="255" id="speed"> 
					<span>Speed: <span id="speed_val"></span></span>
					<br>
					<input type="submit" name="do" value="do">
				</form>
			</div>';
		}
	?>

	<script type="text/javascript">
		<?php 
		if(USER_SPEED_FORM){
			echo 'var speed = document.querySelector("#speed");
			speed.addEventListener("click", function(){
			var speed_val = speed.value;
			var speed_span = document.querySelector("#speed_val");
			speed_span.innerText=speed_val;
			});';
		}
		?>

	</script>
	</body>
</html>
