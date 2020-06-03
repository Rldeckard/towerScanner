<?php

// Set up some variables that may change over time, put this at the top of the script for easy access

// SNMP Community String
#$community = '';
#$password = '';
#$timeout = 50000;
include('connectdb.php');
$empty_column = "0000";
#snmp_set_quick_print(1); // Format the SNMP output

//Need to add 10 more characers to the Device Name section of the database

//------------------------------------ Should not need to change anything below this line --------------------------------------------//
//------------------------------------------------------------------------------------------------------------------------------------//
/* function newIPscan($Scan_Address_Array) { //combines feshly scanned IPs with IPs already in the database for more complete scans
	global $conn;
	$query = mysqli_query($conn, "SELECT IP_Address FROM TowerScan.Towers; ");
	while ($exit = mysqli_fetch_assoc($query))
	{
        	$results[] = $exit["IP_Address"];
	}
	$newIPs = array_diff($Scan_Address_Array, $results);
	foreach ($newIPs as $newIP) {
		array_push($results, $newIP);
	}
	return $results;
}*/

function deleteDevice($IP_Address, $Device_Name, $Tower_Number) {
	$IP_Address = escapeshellarg($IP_Address);
	global $conn;
	if (!mysqli_query($conn, "DELETE FROM TowerScan.Towers WHERE IP_Address = $IP_Address; "))
	{
        	return "Error: Device not removed!";
	} else {
		return "SUCCESS";
		ChangeTable($IP_Address, "Device Removed: ", $Device_Name, $Tower_Number);
	}
}

function AFgetCWandSSID($IP_Address) {//gets the cw  and SSID as snmp can't do it
        $cmd = 'cat /tmp/system.cfg | grep radio.1.linkname= && cat /tmp/system.cfg | grep radio.1.txchanbw= ';//list of commands, don't forget the "&&"
        $connection = ssh2_connect($IP_Address);//set ssh target
        $auth = ssh2_auth_password($connection,'admin',$password);
        if ($auth == FALSE) { $auth = ssh2_auth_password($connection, 'admin', $password2); }
        $stream = ssh2_exec($connection, $cmd);
        stream_set_blocking($stream, true);//necessary to get ouput from ssh2_exec
        $stream_out = ssh2_fetch_stream( $stream, SSH2_STREAM_STDIO );//this gets the ouput of our ssh command
        $outputs = stream_get_contents($stream_out);//this strips extra stuff out
        $outputArray = explode("\n", $outputs);
        fclose($stream);

        foreach ($outputArray as $output)
        {
 	       $results[] = trim(strstr($output, '='), '=');
        }
	switch($results[1]) //Converts UBNT cw to actuall cw.
	{
        	case "16": $Channel_Width = "10"; break;
		case "64": $Channel_Width = "20"; break;
	        case "512": $Channel_Width = "50"; break;
        	case "4096": $Channel_Width = "30"; break;
        	case "2048": $Channel_Width = "100"; break;
	        default: $Channel_Width = "0000"; break;
	}

 	return array($results[0], $Channel_Width);//puts our variables into an array for transport out of function
}

function ChangeTable($IP_Address, $Prev_Entry, $New_Entry){
	global $conn;
	global $TowerNumber;
	if (!mysqli_query($conn, "INSERT INTO TowerScan.ChangeLog (IP_Address, Prev_Entry, New_Entry, Tower_Number)
		VALUES ($IP_Address, $Prev_Entry, $New_Entry, $TowerNumber)")) {

		return mysqli_error($conn);
        } else {
        	return "SUCCESS";
        }


}

function UBNTgetStats($IP_Address) {
	$cmd = 'cat /tmp/system.cfg | grep radio.1.freq= && cat /tmp/system.cfg | grep radio.1.clksel= && cat /tmp/system.cfg | grep radio.1.chanbw=';
	$connection = ssh2_connect($IP_Address);//set ssh target
        $auth = ssh2_auth_password($connection,'admin',$password);
        if ($auth == FALSE) { $auth = ssh2_auth_password($connection, 'admin', $password); }
        $stream = ssh2_exec($connection, $cmd);
        stream_set_blocking($stream, true);//necessary to get ouput from ssh2_exec
        $stream_out = ssh2_fetch_stream( $stream, SSH2_STREAM_STDIO );//this gets the ouput of our ssh command
        $outputs = stream_get_contents($stream_out);//this strips extra stuff out
        $outputArray = explode("\n", $outputs); //leaving as an array for future expansion
	foreach ($outputArray as $output)
        {
               $results[] = trim(strstr($output, '='), '=');
        }
	$chanbw = $results[1] . $results[2]; //combines two random radio values to all for cleaner code when finding channel width
        switch($chanbw) {
	        case "28": $ChannelWidth = "8"; break;
		case "40": $ChannelWidth = "5"; break;
		case "20": $ChannelWidth = "10"; break;
		case "10": $ChannelWidth = "20"; break;
		case "130": $ChannelWidth = "30"; break;
#		case "": $ChannelWidth = 40; break; //same as 20mhz -_-
        }
	return array($results[0], $ChannelWidth);
}

function UBNTsmStats($IP_Address) {

	$cmd = 'cat /tmp/system.cfg | grep radio.1.clksel= && cat /tmp/system.cfg | grep radio.1.chanbw=';
        $connection = ssh2_connect($IP_Address);//set ssh target
        $auth = ssh2_auth_password($connection,'admin',$password);
	if ($auth === FALSE) { ssh2_auth_password($connection, 'admin', $password); }
        $stream = ssh2_exec($connection, $cmd);
        stream_set_blocking($stream, true);//necessary to get ouput from ssh2_exec
        $stream_out = ssh2_fetch_stream( $stream, SSH2_STREAM_STDIO );//this gets the ouput of our ssh command
        $outputs = stream_get_contents($stream_out);//this strips extra stuff out
        $outputArray = explode("\n", $outputs); //leaving as an array for future expansion
        foreach ($outputArray as $output)
        {
               $results[] = trim(strstr($output, '='), '=');
        }
        if (isset($results[0]) && isset($results[1])) { $chanbw = $results[0] . $results[1]; }  //combines two random radio values to all for cleaner code when finding channel wi
        switch($chanbw) {
                case "28": $ChannelWidth = "8"; break;
                case "40": $ChannelWidth = "5"; break;
                case "20": $ChannelWidth = "10"; break;
                case "10": $ChannelWidth = "20"; break;
                case "130": $ChannelWidth = "30"; break;
                return $ChannelWidth;
        }

/* Specifically for admiration


	global $conn;
        $parts = explode(".", $IP_Address); //leaving as an array for future expansion
	if (isset($parts) !== FALSE) {
		$octet4 = $parts[3];
		$octet3 = $parts[2];
		$octet2 = $parts[1];
	}
	switch($octet4){ //find partner IP to grab signal info as v5.5 SMs don't share and this is faster
		case "130": $octet = 131; break;
		case "131": $octet = 130; break;
		case "138": $octet = 139; break;
		case "139": $octet = 138; break;
	}
	$newIP = '10.'.$octet2.'.'.$octet3.'.'.$octet;
	$query = mysqli_query($conn, "SELECT Frequency, Channel_Width, ColorCode_SSID FROM TowerScan.Towers WHERE IP_Address = $newIP; ");
	$rows = mysqli_fetch_rows($query);
	if ($rows == 1) {
		$results = mysqli_fetch_assoc($query);
		$Frequency = $results["Frequency"];
		$Channel_Width = $results["Channel_Width"];
		$SSID = $results["ColorCode_SSID"];
	}

        return array($Frequency, $Channel_Width, $SSID); */
}
function UnknownTable($IP_Address, $upCheck){
        global $conn;
        global $TowerNumber;
	$upCheck = escapeshellarg($upCheck);
        if (!mysqli_query($conn, "INSERT INTO TowerScan.Unknown
                (IP_Address, Tower_Number, UP_Check)
                VALUES ($IP_Address, $TowerNumber, $upCheck)")){
               $error =  mysqli_error($conn);
        } //close insert if
}//closes function UnknownTable

function UpdateTableUnknown($IP_Address){

        global $conn;
        $IP_Address = escapeshellarg($IP_Address);
        $upCheck = exec('nmap -nsP '.$IP_Address.' ');
        if (strpos($upCheck, '1 host up') !== FALSE){
          $upCheck = "CONNECTED";
        }
        else {
          $upCheck = "DISCONNECTED";
        }
        $query = mysqli_query($conn, "SELECT * FROM TowerScan.Unknown WHERE IP_Address = $IP_Address; ");
        $fetch = mysqli_num_rows($query);
        $tquery = mysqli_query($conn, "SELECT * FROM TowerScan.Towers WHERE IP_Address = $IP_Address; ");
        $tfetch = mysqli_num_rows($tquery);

        if ($tfetch > 0)
        {
		$upCheck = escapeshellarg($upCheck);
	        if ($upCheck !== "CONNECTED") {
	            mysqli_query($conn, "UPDATE TowerScan.Towers SET UP_Check = 'DISCONNECTED' WHERE IP_Address = $IP_Address; ");
	            mysqli_query($conn, "UPDATE TowerScan.Towers SET Down_Count = Down_Count + 1 WHERE IP_Address = $IP_Address; ");
                }

        	if ($fetch > 0){ mysqli_query($conn, "DELETE FROM TowerScan.Unknown WHERE IP_Address = $IP_Address; "); }
        }
        elseif ($fetch > 0)
        { //if statement if there's an entry for an IP already. Used to update current entries
        	while ($results = mysqli_fetch_assoc($query))
	        {
			if ($results["UP_Check"] !== $upCheck)
			{
                        	$upCheck = escapeshellarg($upCheck);
		                mysqli_query($conn, "UPDATE TowerScan.Unknown SET UP_Check = $upCheck WHERE IP_Address = $IP_Address; ");
        		}
		}
        }//closes if fetch
        else {
                UnknownTable($IP_Address, $upCheck);
        }
}//closes function UpdateTableTowers




function TowersTable($INF_Type, $Device_Name, $IP_Address, $MAC_Address, $Device_Make, $Device_Type, $Frequency, $Channel_Width, $ColorCode_SSID, $Tower_Number, $SNMP_Community, $SNMP_Version){ //puts new devices into database

	global $conn;
	$INF_Type = escapeshellarg($INF_Type);
	$Device_Name = escapeshellarg($Device_Name);
	$Device_Make = escapeshellarg($Device_Make);
	$Frequency = escapeshellarg($Frequency);
	$Channel_Width = escapeshellarg($Channel_Width);
	$ColorCode_SSID = escapeshellarg($ColorCode_SSID);
	$Tower_Number = escapeshellarg($Tower_Number);
	$Device_Type = escapeshellarg($Device_Type);
	$MAC_Address = escapeshellarg($MAC_Address);
	$SNMP_Community = escapeshellarg($SNMP_Community);
	$SNMP_Version = escapeshellarg($SNMP_Version);
	if (!mysqli_query($conn, "INSERT INTO TowerScan.Towers
		(UP_Check, INF_Type, Device_Name, IP_Address, MAC_Address, Device_Make, Device_Type, Frequency, Channel_Width, ColorCode_SSID, Tower_Number, Down_Count, SNMP_Community, SNMP_Version)
		VALUES ('CONNECTED', $INF_Type, $Device_Name, $IP_Address, $MAC_Address, $Device_Make, $Device_Type, $Frequency, $Channel_Width, $ColorCode_SSID, $Tower_Number, 0, $SNMP_Community, $SNMP_Version)")){
		return mysqli_error($conn);
#		return $IP_Address;
	}
	else {
        	ChangeTable($IP_Address, '"New Device Added: "', $Device_Name); //strictly for change log viewability
	}

}//closes function TowersTable

function UpdateTableTowers($INF_Type, $Device_Name, $IP_Address, $MAC_Address, $Device_Make, $Device_Type, $Frequency, $Channel_Width, $ColorCode_SSID, $Tower_Number, $SNMP_Community, $SNMP_Version){

        $Channel_Width = strval($Channel_Width); //changes to string for database comparison
        $Frequency = strval($Frequency);
	global $conn;
	$IP_Address = escapeshellarg($IP_Address);

	$query = mysqli_query($conn, "SELECT * FROM TowerScan.Towers WHERE IP_Address = $IP_Address; ");
	$fetch = mysqli_num_rows($query);
	$unkquery = mysqli_query($conn, "SELECT * FROM TowerScan.Unknown WHERE IP_Address = $IP_Address; ");
	$unkrow = mysqli_num_rows($unkquery);

	if ($unkrow > 0)
	{
	    mysqli_query($conn, "DELETE FROM TowerScan.Unknown WHERE IP_Address = $IP_Address; ");
	}

        if ($fetch > 0)
        { //if statement if there's an entry for an IP already. Used to update current entries
          while ($results = mysqli_fetch_assoc($query))
          {
              if ($results["UP_Check"] !== "CONNECTED")
              {
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET UP_Check = 'CONNECTED' WHERE IP_Address = $IP_Address; ");
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET Down_Count = 0 WHERE IP_Address = $IP_Address; ");
              }
              if ($results["INF_Type"] !== $INF_Type)
              {
                  $INF_Type = escapeshellarg($INF_Type);
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET INF_Type = $INF_Type WHERE IP_Address = $IP_Address;");
              }
              if ($results["Device_Name"] !== $Device_Name && !empty($Device_Name))
              {
		  ChangeTable($IP_Address, '"Device Name: "' . escapeshellarg($results["Device_Name"]), '"Device Name: "' . $Device_Name);
                  $Device_Name = escapeshellarg($Device_Name);
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET Device_Name = $Device_Name WHERE IP_Address = $IP_Address;");
              }
              if ($results["Device_Make"] !== $Device_Make && !empty($Device_Make))
              {
                  ChangeTable($IP_Address, '"Device Make: "' . escapeshellarg($results["Device_Make"]), '"Device Make: "' . $Device_Name);
                  $Device_Make = escapeshellarg($Device_Make);
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET Device_Make = $Device_Make WHERE IP_Address = $IP_Address;");
              }
              if ($results["Frequency"] !== $Frequency && !empty($Frequency))
              {
                  $Frequency = escapeshellarg($Frequency);
		  ChangeTable($IP_Address, '"Frequency: "' . escapeshellarg($results["Frequency"]), '"Frequency: "' . $Frequency);
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET Frequency = $Frequency WHERE IP_Address = $IP_Address;");
              }
              if ($results["Channel_Width"] !== $Channel_Width && !empty($Channel_Width))
              {
                  $Channel_Width = escapeshellarg($Channel_Width);
		  ChangeTable($IP_Address, '"Channel Width: "' . escapeshellarg($results["Channel_Width"]), '"Channel Width: "' . $Channel_Width);
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET Channel_Width = $Channel_Width WHERE IP_Address = $IP_Address;");
              }
              if ($results["ColorCode_SSID"] !== $ColorCode_SSID && !empty($ColorCode_SSID))
              {
                  $ColorCode_SSID = escapeshellarg($ColorCode_SSID);
		  ChangeTable($IP_Address, '"ColorCode/SSID: "' . escapeshellarg($results["ColorCode_SSID"]), '"ColorCode/SSID: "' . $ColorCode_SSID);
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET ColorCode_SSID = $ColorCode_SSID WHERE IP_Address = $IP_Address;");
              }
              if ($results["Tower_Number"] !== $Tower_Number)
              {
                  $Tower_Number = escapeshellarg($Tower_Number);
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET Tower_Number = $Tower_Number WHERE IP_Address = $IP_Address;");
              }
              if ($results["Device_Type"] !== $Device_Type && !empty($Device_Type))
              {
                  $Device_Type = escapeshellarg($Device_Type);
		  ChangeTable($IP_Address, escapeshellarg($results["Device_Type"]), $Device_Type);
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET Device_Type = $Device_Type WHERE IP_Address = $IP_Address;");
              }
              if ($results["SNMP_Community"] !== $SNMP_Community && !empty($SNMP_Community))
              {
#                  ChangeTable($SNMP_Community, '"SNMP_Community: "' . escapeshellarg($results["SNMP_Community"]), '"SNMP_Community: "' . $SNMP_Community);
                  $SNMP_Community  = escapeshellarg($SNMP_Community);
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET SNMP_Community = $SNMP_Community WHERE IP_Address = $IP_Address;");
              }
	      if ($results["SNMP_Version"] !== $SNMP_Version && !empty($SNMP_Version))
              {
#                  ChangeTable($SNMP_Community, '"SNMP_Community: "' . escapeshellarg($results["SNMP_Community"]), '"SNMP_Community: "' . $SNMP_Community);
                  $SNMP_Version  = escapeshellarg($SNMP_Version);
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET SNMP_Version = $SNMP_Version WHERE IP_Address = $IP_Address;");
              }
              if ($results["MAC_Address"] !== $MAC_Address && !empty($MAC_Address))
              {
#                  ChangeTable($SNMP_Community, '"SNMP_Community: "' . escapeshellarg($results["SNMP_Community"]), '"SNMP_Community: "' . $SNMP_Community);
                  $MAC_Address = escapeshellarg($MAC_Address);
                  mysqli_query($conn, "UPDATE TowerScan.Towers SET MAC_Address = $MAC_Address WHERE IP_Address = $IP_Address;");
              }
              return mysqli_error($conn);
          }//closes while
        }//closes if fetch
	else{
		return TowersTable($INF_Type, $Device_Name, $IP_Address, $MAC_Address, $Device_Make, $Device_Type, $Frequency, $Channel_Width, $ColorCode_SSID, $Tower_Number, $SNMP_Community, $SNMP_Version);
	}
}//closes function UpdateTableTowers
?>
