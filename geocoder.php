<?php

// Delay fucnction for geocode requests
function m_sleep($milliseconds){
	return usleep($milliseconds * 1000);
}

// Make requests to geocoder
function file_get_content_curl($url){
    // Throw Error if the curl function doesn't exist.
    if(!function_exists('curl_init')){
        die('CURL is not installed!');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
    // Send request to geocoder and store response
    $output = curl_exec($ch);
    curl_close($ch);
	
    // Return geocoder response
    return $output;
}


// Geocode address input
function geocode($address){
 
    // url encode the address
    $address = urlencode($address);
     
    // Google Maps Geocode API URL
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&key=[GOOGLE_API_KEY]';
 
    // Get geocoder response
    $resp_json = file_get_content_curl($url);
     
    // Decode response to JSON
    $resp = json_decode($resp_json, true);
 
    // Check for successful response
    if($resp['status']=='OK'){
 
        // Parse geocoder response data
        $lati = isset($resp['results'][0]['geometry']['location']['lat']) ? $resp['results'][0]['geometry']['location']['lat'] : "";
        $longi = isset($resp['results'][0]['geometry']['location']['lng']) ? $resp['results'][0]['geometry']['location']['lng'] : "";
        $formatted_address = isset($resp['results'][0]['formatted_address']) ? $resp['results'][0]['formatted_address'] : "";

		foreach($resp['results'][0]['address_components'] as $address_component){
			
			// Get Street Number
			if($address_component['types'][0] == 'street_number'){
				$street_number = $address_component['short_name'];
			}
			
			// Get Street Address
			if($address_component['types'][0] == 'route'){
				$street_address = $address_component['short_name'];
			}
			
			// Get City
			if($address_component['types'][0] == 'locality'){
				$city = $address_component['short_name'];
			}
			
			// Get State
			if($address_component['types'][0] == 'administrative_area_level_1'){
				$state = $address_component['short_name'];
			}
			
			// Get Zip Code
			if($address_component['types'][0] == 'postal_code'){
				$zip = $address_component['short_name'];
			}
		}

        // Verify if data is complete
        if($lati && $longi && $formatted_address){
         
		// Store data in output array
		$data_arr = array();            

		array_push(
			$data_arr, 
				$lati, 			//0
				$longi, 		//1
				$formatted_address,	//2
				$street_number,		//3
				$street_address,	//4
				$city,			//5
				$state,			//6
				$zip			//7
		);

		// Return output array
		return $data_arr;
             
        }else{
		// Return false on incomplete geocoder results
		return false;
        }
	    
    // Return false on geocoder service failure
    }else{
        print("ERROR: {$resp['status']}");
        return false;
    }
}

// Begin geocoding proceedure
$record = 0;

// Load in CSV file
$fp = file("input.csv");

// Count total rows
$totalRecords = count($fp);

// Open input CSV File
if(($handle1 = fopen("input.csv", "r")) !== FALSE){
    	
	// Create output CSV file
	if(($handle2 = fopen("output.csv", "w")) !== FALSE){
		
		// Loop though input CSV file rows
		while(($data = fgetcsv($handle1, 7000, ",")) !== FALSE){
			
			// Delay geocoder exection by a random number of milliseconds to avoid abuse flagging
			$random_time = mt_rand(20, 25);
			m_sleep($random_time);
			
			// Geocode input CSV row data
			$geocode = geocode($data[0]);
			
			// Store geocoder output in array
			$new_data[0] = $data[0];
			$new_data[1] = $geocode[0];
			$new_data[2] = $geocode[1];
			$new_data[3] = $geocode[2];
			$new_data[4] = $geocode[3];
			$new_data[5] = $geocode[4];
			$new_data[6] = $geocode[5];
			$new_data[7] = $geocode[6];
			$new_data[8] = $geocode[7];

			// Write output array to output CSV file
			fputcsv($handle2, $new_data);
			
			// Output status message
			print("Record: " . ($record+1) . " of " . $totalRecords . " - " . $new_data[0]. "\r\n");


			$record++;
		}
		
		// Close output CSV
		fclose($handle2);
	}
	
	// Close input CSV
	fclose($handle1);
}
?>
