<?php
function m_sleep($milliseconds){
	return usleep($milliseconds * 1000);
}

function formatTimePeriod($endtime, $starttime){
  $duration = $endtime - $starttime;

  $hours = (int) ($duration / 60 / 60);
  $minutes = (int) ($duration / 60) - $hours * 60;
  $seconds = (int) $duration - $hours * 60 * 60 - $minutes * 60;

  return ($hours == 0 ? "00":$hours) . ":" . ($minutes == 0 ? "00":($minutes < 10? "0".$minutes:$minutes)) . ":" . ($seconds == 0 ? "00":($seconds < 10? "0".$seconds:$seconds));
}

function file_get_content_curl($url){
    // Throw Error if the curl function doesn't exist.
    if (!function_exists('curl_init'))
    { 
        die('CURL is not installed!');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}


// function to geocode address, it will return false if unable to geocode address
function geocode($address){
 
    // url encode the address
    $address = urlencode($address);
     
    // google map geocode api url
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&key=[GOOGLE_API_KEY]';
 
    // get the json response
    $resp_json = file_get_content_curl($url);
     
    // decode the json
    $resp = json_decode($resp_json, true);
 
    // response status will be 'OK', if able to geocode given address 
    if($resp['status']=='OK'){
 
        // get the important data
        $lati = isset($resp['results'][0]['geometry']['location']['lat']) ? $resp['results'][0]['geometry']['location']['lat'] : "";
        $longi = isset($resp['results'][0]['geometry']['location']['lng']) ? $resp['results'][0]['geometry']['location']['lng'] : "";
        $formatted_address = isset($resp['results'][0]['formatted_address']) ? $resp['results'][0]['formatted_address'] : "";

		foreach($resp['results'][0]['address_components'] as $address_component){
			if($address_component['types'][0] == 'street_number'){
				$street_number = $address_component['short_name'];
			}
			
			if($address_component['types'][0] == 'route'){
				$street_address = $address_component['short_name'];
			}
			
			if($address_component['types'][0] == 'locality'){
				$city = $address_component['short_name'];
			}
			
			if($address_component['types'][0] == 'administrative_area_level_1'){
				$state = $address_component['short_name'];
			}
			
			if($address_component['types'][0] == 'postal_code'){
				$zip = $address_component['short_name'];
			}
		}

        // verify if data is complete
        if($lati && $longi && $formatted_address){
         
            // put the data in the array
            $data_arr = array();            
             
            array_push(
                $data_arr, 
                    $lati, 					//0
                    $longi, 				//1
                    $formatted_address,		//2
                    $street_number,			//3
                    $street_address,		//4
                    $city,					//5
                    $state,					//6
                    $zip					//7
                );
             
            return $data_arr;
             
        }else{
            return false;
        }
         
    }
 
    else{
        print("ERROR: {$resp['status']}");
        return false;
    }
}


$record = 0;
$fp = file("input.csv");
$totalRecords = count($fp);

if(($handle1 = fopen("input.csv", "r")) !== FALSE){
    
	if(($handle2 = fopen("output.csv", "w")) !== FALSE){

		while(($data = fgetcsv($handle1, 7000, ",")) !== FALSE){

			$random_time = mt_rand(20, 25);
			
			// Alter your data
			m_sleep($random_time);

			$geocode = geocode($data[0]);
			
			$new_data[0] = $data[0];
			$new_data[1] = $geocode[0];
			$new_data[2] = $geocode[1];
			$new_data[3] = $geocode[2];
			$new_data[4] = $geocode[3];
			$new_data[5] = $geocode[4];
			$new_data[6] = $geocode[5];
			$new_data[7] = $geocode[6];
			$new_data[8] = $geocode[7];

			// Write back to CSV format
			fputcsv($handle2, $new_data);
			
			print("Record: " . ($record+1) . " of " . $totalRecords . " - " . $new_data[0]. "\r\n");


			$record++;
		}
		fclose($handle2);
	}
	fclose($handle1);
}
?>
