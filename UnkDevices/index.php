<?php

// Set up some variables that may change over time, put this at the top of the script for easy access

// SNMP Community String
$community = '!highspeed';
$timeout = 50000;
include('../connectdb.php');
snmp_set_quick_print(1); // Format the SNMP output
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);
//------------------------------------ Should not need to change anything below this line --------------------------------------------//
//------------------------------------------------------------------------------------------------------------------------------------//

//Start session
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if(!isset($_SESSION['sess_user_id']) || (trim($_SESSION['sess_user_id']) == '')) {
  header("location: /login.php");
  exit();
}

print '<html><head><link rel="stylesheet" type="text/css" href="../css/style.css" />';
print '<title>THIS - Netherlands</title></head>';
print '<body><div id="container">';
include('../header.php');

// 1=user 2=field 3=sales 4=billing 5=dispatch 50=manager 99=admin';
if($_SESSION['sess_groupid'] < 0) {
  header('Location: /noaccess.php');
}
print '<div id="body">';

print '<h1>Device Faults</h1>'; //starts off showing empty values then unknowns
print '<form action="" method=POST>';
      
        $updown = "lightblue";
        $number = 0;
        $down = 50;
        $left = 100;
        $fquery = mysqli_query($conn, "SELECT * FROM TowerScan.Towers WHERE Frequency = '' OR ColorCode_SSID = '' OR Device_Make = '' OR Device_Type = '' ORDER BY IP_ADDRESS;"); //gets empty values. 
        $ffetch = mysqli_num_rows($fquery);
        print '<span style="font-weight:bold;position:absolute;right:50px;top:10px">Showing <label style="font-size:23px;font-weight:normal;color:red">' . $ffetch . '</label> devices faults</span>';
        if ($ffetch > 0)
        {
            while ($fresults = mysqli_fetch_assoc($fquery))
             {
                 if ($number % 7 == 0 ){ $left = 100; $down += 50; } //moves ip down to next line
                 if ($fresults["UP_Check"] !== "CONNECTED") { $updown = "red"; } else { $updown = "blue"; } //determines if device was up at last check
                 print '<span style="position:absolute;left:'.$left.'px;top:'.$down.'px;font-size:1.2em;"><a style="color:'.$updown.';" href="http://'.$fresults["IP_Address"].'" target="_ap">'.$fresults["IP_Address"].'</a></span><br>';
                 $left += 150;
                 $number++;    
             }
        }
/*        $nquery = mysqli_query($conn, "SELECT IP_Address, UP_Check FROM TowerScan.Towers WHERE Device_Make = 'Netonix' ORDER BY IP_ADDRESS;");
        $nfetch = mysqli_num_rows($nquery);
        $down += 50;
        print '<h1 style="position:absolute;top:'.$down.'">Unconfigured Netonix Switches</h1>';
        print '<span style="font-weight:bold;position:absolute;right:50px;top:'.$down.'px">Showing <label style="font-size:23px;font-weight:normal;color:red">' . $nfetch . '</label> unconfigured Netonix Switches</span>';
        if ($nfetch > 0)
        {
                $down += 100;
            while ($nresults = mysqli_fetch_assoc($nquery))
            {
		 $portLabels = @snmp2_get($nresults["IP_Address"], "!highspeed", ".1.3.6.1.2.1.2.2.1.2.1", $timeout);
                 $portStatus = @snmp2_get($nresults["IP_Address"], "!highspeed", ".1.3.6.1.2.1.2.2.1.8.1", $timeout);
                 if ($portStatus == "up" && strpos($portLabels, "Port ") !== FALSE) {
                 print_r ($portLabels).'<br>';
                 print_r ($portStatus).'<br>';
                 }
	    }
                 if ($number % 7 == 0 ){ $left = 100; $down += 50; } //moves ip down to next line
                 if ($nresults["UP_Check"] !== "CONNECTED") { $updown = "red"; } else { $updown = "blue"; } //determines if device was up at last check
                 print '<span style="position:absolute;left:'.$left.'px;top:'.$down.'px;font-size:1.2em;"><a style="color:'.$updown.';" href="http://'.$nresults["IP_Address"].'" target="_ap">'.$nresults["IP_Address"].'</a></span><br>';
                 $left += 150;
                 $number++;
            
        }//closes if fetch
*/
        $query = mysqli_query($conn, "SELECT * FROM TowerScan.Unknown ORDER BY IP_ADDRESS;");
        $fetch = mysqli_num_rows($query);
	$down += 50;
	print '<h1 style="position:absolute;top:'.$down.'">Unknown Devices</h1>';
        print '<span style="font-weight:bold;position:absolute;right:50px;top:'.$down.'px">Showing <label style="font-size:23px;font-weight:normal;color:red">' . $fetch . '</label> unknown devices</span>';
        if ($fetch > 0)
        { 
		$down += 100;
            while ($results = mysqli_fetch_assoc($query))
            {    
                 if ($number % 7 == 0 ){ $left = 100; $down += 50; } //moves ip down to next line
                 if ($results["UP_Check"] !== "CONNECTED") { $updown = "red"; } else { $updown = "blue"; } //determines if device was up at last check
                 print '<span style="position:absolute;left:'.$left.'px;top:'.$down.'px;font-size:1.2em;"><a style="color:'.$updown.';" href="http://'.$results["IP_Address"].'" target="_ap">'.$results["IP_Address"].'</a></span><br>'; 
                 $left += 150;                  
                 $number++;
            }
        }//closes if fetch
print '</form></body>';
include('../footer.php');  
print '</div></div></html>';
?>