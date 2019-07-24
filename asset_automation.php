<?php

require("./sendgrid-php/vendor/autoload.php");
require("./sendgrid-php/sendgrid-php.php");

// let's establish our SendGrid credentials for later use
$sg_username = "sg_username";
$sg_password = "sg_password";
$sg_key = "sg_key";

// let's define our JSS credentials for later use
$jssAPI = 'https://domain.jamfcloud.com/JSSResource/';
$jssUser = 'jssUser';
$jssPass = 'jssPass';
$jamf_xml = new DOMDocument();
$jamf_xml->encoding = 'utf-8';
$jamf_xml->xmlVersion = '1.0';
$jamf_xml->formatOutput = true;

$computer = $jamf_xml->createElement('computer');

// let's define our itassets credentials for later use
$accessToken = 'accessToken';

// and now the uri we'll be using to search for all Apple laptops in our itassets inventory
$uri_search = 'https://itassets.domain.net/api/v1/hardware?limit=1&status_id=5&manufacturer_id=1&category_id=1&sort=created_at&order=desc';

// a note on the above uri
    # limit=X defines the number of assets the API returns, only used in testing to limit the number of assets queried
    # status_id=5 limits the query to machines that are listed as Active and will not query Ready To Deploy or Lost/Stolen or et cetera
    # manufacturer_id=1 limits the query to only Apple assets
    # category_id = 1 limits the query to devices categorized as "Corporate Laptops" only and will not query DC Workstations or et cetera

// let's set up the curl request
$ch = curl_init($uri_search);

// and also our authorization headers
curl_setopt_array($ch, array(
    CURLOPT_HTTPHEADER  => array('Authorization: Bearer ' . $accessToken),

// this will return the output as a string instead of raw data
    CURLOPT_RETURNTRANSFER  => true,

// and this will either enable or disable verbose mode, it can be useful to enable when troubleshooting
    CURLOPT_VERBOSE     => 0
));

// let's execute the curl and define the output as a variable so we can do shit with it
$output = curl_exec($ch);

// and finally let's close that curl request
curl_close($ch);

// let's decode that output so we can do something with the JSON
$json = json_decode($output, true);
$assets = $json['rows'];

// now we can echo the response output from itassets (if we want)
#echo "\n" . 'There are '.count($assets).' assets' . "\n";
#print_r($assets);

// and now let's do a nice little loop for every laptop that appeared in our results
for ($x = 0; $x < count($assets); $x++) {

    $itassets_assignment = $assets[$x]['assigned_to']['name'];
    $itassets_location = $assets[$x]['location']['name'];
    $itassets_name = $assets[$x]['name'];
    $itassets_id = $assets[$x]['id'];
    $itassets_serial = $assets[$x]['serial'];
    $itassets_mac_primary = $assets[$x]['custom_fields']['MAC Address (Primary)']['value'];
    $itassets_mac_alt = $assets[$x]['custom_fields']['MAC Address (Alternate)']['value'];
    $itassets_asset_tag = $assets[$x]['asset_tag'];
    $itassets_manufacturer = $assets[$x]['manufacturer']['name'];
    $itassets_model = $assets[$x]['model']['name'];

    if (isset($assets[$x]['custom_fields']['CPU Type']['value'])) {
        $itassets_cpu_type = $assets[$x]['custom_fields']['CPU Type']['value'];
    } else {
        $itassets_cpu_type = null;
    }

    if (isset($assets[$x]['custom_fields']['CPU Speed']['value'])) {
        $itassets_cpu_speed_ghz = $assets[$x]['custom_fields']['CPU Speed']['value'];
    } else {
        $itassets_cpu_speed_ghz = null;
    }

    if (isset($assets[$x]['custom_fields']['CPU Cores']['value'])) {
        $itassets_cpu_cores = $assets[$x]['custom_fields']['CPU Cores']['value'];
    } else {
        $itassets_cpu_cores = null;
    }

    if (isset($assets[$x]['custom_fields']['RAM']['value'])) {
        $itassets_ram_gb = $assets[$x]['custom_fields']['RAM']['value'];
    } else {
        $itassets_ram_gb = null;
    }

    if (isset($assets[$x]['custom_fields']['Storage']['value'])) {
        $itassets_ssd_size = $assets[$x]['custom_fields']['Storage']['value'];
    } else {
        $itassets_ssd_size = null;
    }

    if (isset($assets[$x]['custom_fields']['OS Version']['value'])) {
        $itassets_os_version = $assets[$x]['custom_fields']['OS Version']['value'];
    } else {
        $itassets_os_version = null;
    }

    echo "\n" . "INFORMATION PULLED FROM ITASSETS" . "\n";
    echo "Assigned To: " . $itassets_assignment . "\n";
    echo "Device Location: " . $itassets_location . "\n";
    echo "Computer Name: " . $itassets_name . "\n";
    echo "Unique ITASSETS ID: " . $itassets_id . "\n";
    echo "Serial Number: " . $itassets_serial . "\n";
    echo "MAC Address (Primary): " . $itassets_mac_primary . "\n";
    echo "MAC Address (Alternate): " . $itassets_mac_alt . "\n";
    echo "Asset Tag: " . $itassets_asset_tag . "\n";
    echo "Manufacturer: " . $itassets_manufacturer . "\n";
    echo "Model: " . $itassets_model . "\n";
    echo "CPU Type: " . $itassets_cpu_type . "\n";
    echo "CPU Speed: " . $itassets_cpu_speed_ghz . "\n";
    echo "CPU Cores: " . $itassets_cpu_cores . "\n";
    echo "Total RAM (GB): " . $itassets_ram_gb . "\n";
    echo "Total SSD Storage (GB): " . $itassets_ssd_size . "\n";
    echo "OS Version: " . $itassets_os_version . "\n";

// now we can take the serial number and grab the machine's info from Jamf, let's define the uri to perform the serach-by-serial
    $jamf_uri = $jssAPI . 'computers/serialnumber/' . $itassets_serial;

// let's set up the curl request
    $ch = curl_init($jamf_uri);

// and also our authorization headers and credentials
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Accept: application/json'
));
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$jssUser:$jssPass");

// this will return the output as a string instead of raw data
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// let's execute the curl and define the output as a variable so we can do shit with it
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    $info = curl_getinfo($ch);
    #print_r($info);

// and finally let's close that curl request
    curl_close($ch);	

// 	now we can echo the response output from Jamf, first let's decode that output so we can do shit with the JSON
    $jss_array = json_decode($result, true);
    #print_r($jss_array);

// first let's confirm that the machine is in Jamf, if not we'll send an error

if (empty($jss_array)) {
    echo "\n" . "ERROR: Serial number $itassets_serial is not in Jamf." . "\n";
    $no_jamf[] = $itassets_serial;

// send email to itsupport

    $sg = new \SendGrid($sg_key);
    $email = new \SendGrid\Mail\Mail ();
        $email -> setFrom ('from@domain.com');
        $email -> setSubject ('MACHINE NOT ENROLLED IN JAMF');
        $email -> addTo ('to@domain.com');
        $email -> addContent("text/plain", "ALERT: $jamf_name is not enrolled in Jamf!" . "\n" . "Serial Number: $jamf_serial");

    try {
        $response = $sg->send($email);
        #print $response->statusCode() . "\n";
        #print_r($response->headers());
        #print $response->body() . "\n";
    } catch (Exception $e) {
        #echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    } else {

    // let's parse that JSON and create a few variables so they'll be easier to use later on
        $jamf_assignment = $jss_array['computer']['location']['realname'];
        $jamf_location = $jss_array['computer']['location']['building'];
        $jamf_name = $jss_array['computer']['general']['name'];
        $jamf_id = $jss_array['computer']['general']['id'];
        $jamf_serial = $jss_array['computer']['general']['serial_number'];
        $jamf_mac_primary = $jss_array['computer']['general']['mac_address'];
        $jamf_mac_primary = $jss_array['computer']['general']['mac_address'];
        $jamf_mac_alt = $jss_array['computer']['general']['alt_mac_address'];
        $jamf_asset_tag = $jss_array['computer']['general']['asset_tag'];
        $jamf_manufacturer = $jss_array['computer']['hardware']['make'];
        $jamf_model = $jss_array['computer']['hardware']['model'];
        $jamf_cpu_type = $jss_array['computer']['hardware']['processor_type'];
        $jamf_cpu_speed_mhz = $jss_array['computer']['hardware']['processor_speed_mhz'];
        $jamf_cpu_cores = $jss_array['computer']['hardware']['number_cores'];
        $jamf_ram_mb = $jss_array['computer']['hardware']['total_ram_mb'];
        $jamf_ssd_size = $jss_array['computer']['hardware']['storage']['0']['size'];
        $jamf_os_version = $jss_array['computer']['hardware']['os_version'];
        $jamf_fv2_status = $jss_array['computer']['hardware']['storage']['0']['partition']['filevault2_percent'];

    // now let's quickly convert how Jamf reports CPU speed (MHz) to a more standardized type (GHz)
        $jamf_cpu_speed_ghz = ($jamf_cpu_speed_mhz / 1000 . 'GHz');

    // and let's quickly convert how Jamf reports total memory (MB) to a more standardized size (GB)
        $jamf_ram_gb = ($jamf_ram_mb / 1024 . 'GB');
    
    // and also let's convert how Jamf reports total SSD size (MB) to a more standardized size (GB)
        $jamf_ssd_size = ($jamf_ssd_size / 1024 . 'GB');
        $jamf_ssd_size = (round($jamf_ssd_size, 3));

    // for now, let's echo all of the variables we're gonna use just so we can look at them in the shell
        echo "\n" . "INFORMATION PULLED FROM JAMF" . "\n";
        echo "Assigned To: " . $jamf_assignment . "\n";
        echo "Device Location: " . $jamf_location . "\n";
        echo "Computer Name: " . $jamf_name . "\n";
        echo "Unique JAMF ID: " . $jamf_id . "\n";
        echo "Serial Number: " . $jamf_serial . "\n";
        echo "MAC Address (Primary): " . $jamf_mac_primary . "\n";
        echo "MAC Address (Alternate): " . $jamf_mac_alt . "\n";
        echo "Asset Tag: " . $jamf_asset_tag . "\n";
        echo "Manufacturer: " . $jamf_manufacturer . "\n";
        echo "Model: " . $jamf_model . "\n";
        echo "CPU Type: " . $jamf_cpu_type . "\n";
        echo "CPU Speed: " . $jamf_cpu_speed_ghz . "\n";
        echo "CPU Cores: " . $jamf_cpu_cores . "\n";
        echo "Total RAM (GB): " . $jamf_ram_gb . "\n";
        echo "Total SSD Storage (GB): " . $jamf_ssd_size . "\n";
        echo "OS Version: " . $jamf_os_version . "\n";
        echo "FileVault 2 Status: " . $jamf_fv2_status . "\n";

    // first let's confirm that the machine is encrypted, if not we'll send an alert
        if ($jamf_fv2_status !== 100) {
            echo "\n" . "ALERT: $jamf_name is not encrypted!" . "\n";
            $no_fv2[] = $jamf_serial;

            $sg = new \SendGrid($sg_key);
            $email = new \SendGrid\Mail\Mail ();
                $email -> setFrom ('from@domain.com');
                $email -> setSubject ('UNENCRYPTED MACHINE');
                $email -> addTo ('to@domain.com');
                $email -> addContent("text/plain", "ALERT: $jamf_name is not encrypted!" . "\n" . "Serial Number: $jamf_serial");

            try {
                $response = $sg->send($email);
                #print $response->statusCode() . "\n";
                #print_r($response->headers());
                #print $response->body() . "\n";
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
    
    // let's compare the DEVICE ASSIGNMENT across our two systems, knowing that itassets is our SOT
        if ($jamf_assignment !== $itassets_assignment) {
            echo "\n" . "ALERT: The device assignment is not in sync across our systems!" . "\n";

            if (!isset($location)) {
                $location = $jamf_xml->createElement('location');
            }
            $location->appendChild($jamf_xml->createElement('real_name',$itassets_assignment));
            $process_xml = 1;
        } else {
            #echo "\n" . "DEVICE ASSIGNMENT CHECKS OUT! ALL GOOD HERE!" . "\n";
        }

    // let's compare the DEVICE LOCATION across our two systems, knowing that itassets is our SOT
        if ($jamf_location !== $itassets_location) {
            echo "\n" . "ALERT: The device location is not in sync across our systems!" . "\n";

            if (!isset($location)) {
                $location = $jamf_xml->createElement('location');
            }
            $location->appendChild($jamf_xml->createElement('building',$itassets_location));
            $process_xml = 1;
        } else {
            #echo "\n" . "DEVICE LOCATION CHECKS OUT! ALL GOOD HERE!" . "\n";
        }

    // let's compare the ASSET TAG across our two systems, knowing that itassets is our SOT
        if ($jamf_asset_tag != $itassets_asset_tag) {
            echo "\n" . "ALERT: The asset tag record is not in sync across our systems!" . "\n";
            $general = $jamf_xml->createElement('general');
            $general->appendChild($jamf_xml->createElement('asset_tag',$itassets_asset_tag));
            $process_xml = 1;
        } else {
            #echo "\n" . "ASSET TAG CHECKS OUT! ALL GOOD HERE!" . "\n";
        }

    // let's compare the COMPUTER MODEL across our two systems, knowing that Jamf is our SOT
        if ($itassets_model != $jamf_model) {
            echo "\n" . "ALERT: The computer model is not in sync across our systems!" . "\n";
            $itassets_payload["model_name"] = $jamf_model;
        } else {
            #echo "\n" . "COMPUTER MODEL CHECKS OUT! ALL GOOD HERE!" . "\n";
        }

    // let's compare the COMPUTER NAME across our two systems, knowing that Jamf is our SOT
        if ($itassets_name != $jamf_name) {
            echo "\n" . "ALERT: The computer name is not in sync across our systems!" . "\n";
            $itassets_payload["name"] = $jamf_name;
        } else {
            #echo "\n" . "COMPUTER NAME CHECKS OUT! ALL GOOD HERE!" . "\n";
        }

    // let's compare the SERIAL NUMBER across our two systems, knowing that Jamf is our SOT
        if ($itassets_serial != $jamf_serial) {
            echo "\n" . "ALERT: The serial number is not in sync across our systems!" . "\n";
            $itassets_payload["serial"] = $jamf_serial;
        } else {
            #echo "\n" . "SERIAL NUMBER CHECKS OUT! ALL GOOD HERE!" . "\n";
        }

    // let's compare the PRIMARY MAC ADDRESS across our two systems, knowing that Jamf is our SOT
        if ($itassets_mac_primary != $jamf_mac_primary) {
            echo "\n" . "ALERT: The PRIMARY MAC ADDRESS is not in sync across our systems!" . "\n";
            $itassets_payload["_snipeit_mac_address_primary_1"] = $jamf_mac_primary;
        } else {
            #echo "\n" . "PRIMARY MAC ADDRESS CHECKS OUT! ALL GOOD HERE!" . "\n";
        }

    // let's compare the ALTERNATE MAC ADDRESS across our two systems, knowing that Jamf is our SOT
        if ($itassets_mac_alt != $jamf_mac_alt) {
            echo "\n" . "ALERT: The ALTERNATE MAC ADDRESS is not in sync across our systems!" . "\n";
            $itassets_payload["_snipeit_mac_address_alternate_28"] = $jamf_mac_alt;
        } else {
            #echo "\n" . "ALTERNATE MAC ADDRESS CHECKS OUT! ALL GOOD HERE!" . "\n";
        }
    
    // let's compare the CPU TYPE across our two systems, knowing that Jamf is our SOT
        if ($itassets_cpu_type != $jamf_cpu_type) {
            echo "\n" . "ALERT: The CPU types are not in sync across our systems!" . "\n";
            $itassets_payload["_snipeit_cpu_type_8"] = $jamf_cpu_type;
        } else {
            #echo "\n" . "CPU TYPE CHECKS OUT! ALL GOOD HERE!" . "\n";
        }
    
    // let's compare the CPU SPEED across our two systems, knowing that Jamf is our SOT
        if ($itassets_cpu_speed_ghz != $jamf_cpu_speed_ghz) {
            echo "\n" . "ALERT: The CPU speeds are not in sync across our systems!" . "\n";
            $itassets_payload["_snipeit_cpu_speed_27"] = $jamf_cpu_speed_ghz;
        } else {
            #echo "\n" . "CPU SPEED CHECKS OUT! ALL GOOD HERE!" . "\n";
        }
    
    // let's compare the CPU CORES across our two systems, knowing that Jamf is our SOT
        if ($itassets_cpu_cores != $jamf_cpu_cores) {
            echo "\n" . "ALERT: The CPU CORES are not in sync across our systems!" . "\n";
            $itassets_payload["_snipeit_cpu_cores_29"] = $jamf_cpu_cores;
        } else {
            #echo "\n" . "CPU CORES CHECK OUT! ALL GOOD HERE!" . "\n";
        }
    
    // let's compare the AMOUNT OF RAM across our two systems, knowing that Jamf is our SOT
        if ($itassets_ram_gb != $jamf_ram_gb) {
            echo "\n" . "ALERT: The amount of RAM is not in sync across our systems!" . "\n";
            $itassets_payload["_snipeit_ram_5"] = $jamf_ram_gb;
        } else {
            #echo "\n" . "AMOUNT OF RAM CHECKS OUT! ALL GOOD HERE!" . "\n";
        }

    // let's compare the SSD SIZE across our two systems, knowing that Jamf is our SOT
        if ($itassets_ssd_size != $jamf_ssd_size) {
            echo "\n" . "ALERT: The SSD SIZE is not in sync across our systems!" . "\n";
            $itassets_payload["_snipeit_storage_6"] = $jamf_ssd_size;
        } else {
            #echo "\n" . "AMOUNT OF RAM CHECKS OUT! ALL GOOD HERE!" . "\n";
        }

    // let's compare the OS VERSION across our two systems, knowing that Jamf is our SOT
        if ($itassets_os_version !== $jamf_os_version) {
            echo "\n" . "ALERT: The OS VERSION is not in sync across our systems!" . "\n";
            $itassets_payload["_snipeit_os_version_30"] = $jamf_os_version;
        } else {
            #echo "\n" . "THE OS VERSION CHECKS OUT! ALL GOOD HERE!" . "\n";
        }

    // assuming shit wasn't in sync, let's take those arrays we created and turn them into strings for our POST, if the arrays are empty we'll just move along
        if (!empty($itassets_payload)) {
            $itassets_payload_string = json_encode($itassets_payload);
            $itassets_payload_string = trim($itassets_payload_string, "[]");
        } else {
            #echo "\n" . "THE ITASSETS ARRAY IS EMPTY! MOVING ON!" . "\n";
        }

    // if we did end up creating strings, let's echo them here to look at in all their glory, if there's nothing to see here we'll just move along, let's start with the Jamf payload
        if (isset($process_xml)) {
            if (!empty($general)) {
                $computer->appendChild($general);
            }
            if (!empty($location)) {
                $computer->appendChild($location);
            }
            $jamf_xml->appendChild($computer);
            $jamf_payload = $jamf_xml->saveXML();
            
            unset($process_xml);
            unset($general);
            unset($location);

            echo "\n" . "WHAT WE'RE SENDING OVER TO JAMF: " . "\n" . $jamf_payload . "\n";

            // first let's define the uri we need to hit in order to update this field in Jamf
                $jamf_uri = 'https://domain.jamfcloud.com/JSSResource/computers/id/' . $jamf_id;

            // let's set up the curl request
                $ch = curl_init($jamf_uri);

            // and also our authorization headers and credentials
	            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: text/xml',
                'Accept: application/xml'
            ));
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, "$jssUser:$jssPass");

            // this will return the output as a string instead of raw data
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jamf_payload);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

            // let's execute the curl and define the output as a variable so we can do shit with it
                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo 'Error:' . curl_error($ch);
                }

                $info = curl_getinfo($ch);
                #echo "<pre>";
                #print_r($info);
                #echo "</pre>";

            // let's close that curl
                curl_close($ch);
        }

    // and now let's do the itassets/ payload
        if (empty($itassets_payload_string)) {
            // move along
        } else {
            echo "\n" . "WHAT WE'RE SENDING OVER TO ITASSETS: " . "\n" . $itassets_payload_string . "\n";

            // define that uri again
                $itassets_uri = 'https://itassets.domain.net/api/v1/hardware/' . $itassets_id;
            
            // let's set up the curl request
                $ch = curl_init($itassets_uri);
            
            // and now let's set up our authorization headers
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Length: ' . strlen($itassets_payload_string)
                    ));
            
            // this will return the output as a string instead of raw data
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            // let's make sure we're sending a PUT
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            
            // let's send that $payload over to itassets
                curl_setopt($ch, CURLOPT_POSTFIELDS, $itassets_payload_string);
            
            // let's execute the curl and define the output as a variable so we can do shit with it
                $output = curl_exec($ch);
            
            // do something with the returned data, here we're just dumping it to the screen
                #echo "\n" . $output . "\n";
            
            // get info about the request
                $info = curl_getinfo($ch);
                #print_r($info);
            
            // and finally let's close that curl request
                curl_close($ch);
        }

    }
}

if (isset($no_jamf)) {
    $no_jamf_string = json_encode($no_jamf);
    $no_jamf_string = trim($no_jamf_string, "[]");

    echo "\n" . "The following serial numbers are not enrolled in Jamf:" . "\n";
    echo $no_jamf_string . "\n";
} else {
    echo "\n" . "ALL MACHINES SCANNED ARE ENROLLED IN JAMF!" . "\n";
}

if (isset($no_fv2)) {
    $no_fv2_string = json_encode($no_fv2);
    $no_fv2_string = trim($no_fv2_string, "[]");

    echo "\n" . "The following serial numbers are not encrypted:" . "\n";
    echo $no_fv2_string . "\n";
} else {
    echo "\n" . "ALL MACHINES SCANNED ARE ENCRYPTED!" . "\n";
}
?>
