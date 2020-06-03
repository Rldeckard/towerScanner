<?php

// Set up some variables that may change over time, put this at the top of the script for easy access

// SNMP Community String
$community = '!highspeed';
$timeout = 50000;
$sqlservername = "localhost";
$sqlusername = "towerscan";
$sqlpassword = "4correctGATHERguess";
$sqldbname = "TowerScan";
$conn = new mysqli($sqlservername, $sqlusername, $sqlpassword);
$one = "";
$two = "";
$three = "";
$four = NULL;
$five = NULL;
$six = NULL;
$seven = NULL; 
$eight = NULL;
$nine = NULL;
$IParray = array();
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
?>
<html><head><link rel="stylesheet" type="text/css" href="../css/style.css" />
<style>
#table {
	text-align: left;
	width: 100%;
	box-sizing: border-box;
	font-size: 20px;
	float: left;
}
#output {
/*        display: block; */
}
label {
        display: inline-block;
        float: left;
        clear: left;
        width: 200px;
        padding-right: 20px;
        padding-bottom: 20px;
	padding-left: 25px;
        text-align: left;
}
</style>
<title>
Edit Device
</title>
</head>
<body><div id=container>
<?php
include('/var/www/html/admin/scantower/header.php');

// 1=user 2=field 3=sales 4=billing 5=dispatch 50=manager 99=admin';
if($_SESSION['sess_groupid'] < 0) {
  header('Location: /noaccess.php');
}
print '<div id="body" style="background-color:#F0F0F0">';

#if (isset($_POST['IP_Address']) !== FALSE) {
	if (isset($_GET['IP_Address']) !== FALSE) { 
	$IP = $_GET['IP_Address']; //separate variable to pass IP at the bottom of the page
	$IP_Address = escapeshellarg($IP);
	$query = mysqli_query($conn, "SELECT * FROM TowerScan.Towers WHERE IP_Address = $IP_Address;");
	$results = mysqli_fetch_assoc($query); 
	$one = $results["INF_Type"];
	$two = $results["Tower_Number"];
	$three = $results["Device_Name"];
	$four = $results["Device_Type"];
	$five = $results["Device_Make"];
	$six = $results["IP_Address"];
	$seven = $results["Frequency"];
	$eight = $results["ColorCode_SSID"];
	$nine = $results["Channel_Width"];
	$ten = $results["UP_Check"];
	
	if ($ten == "CONNECTED") {
                $color = '#0000EE';
	} 
	else {
	        $color = '#E30000';
	}
	
	}
	
	print '<h1>View Device</h1>';
	print '<form action="" id=table>';
	print '<hr>';
	print '<label>Infrastructure Type: </label><span id=output style="font-weight:bold;color:#E30000;">'.substr($one, 0, -1).'</span><br><br>';
	print '<label>Tower Number: </label>'.$two.'<br><br>';
	print '<label>Device Name: </label><span id=output style="font-weight:bold">'.$three.'</span><br><br>';
	print '<label>Device Type: </label>'.$four.'<br><br>';
	print '<label>Device Make: </label>'.$five.'<br><br>';
	print '<label>IP Address: </label><a id=output style="color:'.$color.';text-decoration:underline;" href=http://'.$six.' target=" ">'.$six.'</a><br><br>';
	print '<label>Antenna: </label><br><br>';
	print '<label>Facing: </label><br><br>';
	print '<label>Frequency: </label>'.$seven.'<br><br>';
	print '<label>ColorCode/SSID: </label>'.$eight.'<br><br>';
	print '<label>Channel Width: </label>'.$nine.'<br><br>';
	print '<label>Parent: </label><br><br>';
	print '<label>Notes: </label><br><br>'; //optional
	print '</form>';
	print '<form method=POST style="position:relative;bottom:80px;left:800px;clear:right;width:100%"><input type=submit name=delete_device style="background-color:#b70009;color:white;" value="Delete Device">
	<input type=submit name=edit_device  value="Edit Device">
	<input type=submit name=device_logs style="background-color:gray;color:white;font-weight:bold" value="Device Logs"></form>
	<form method=POST style="position:absolute;top:10px;right:50;">
	<input type=submit style="background-color:gray;height:40px;color:white;border-radius:20px;font-size:12;border: solid 1px gray;width:60px;padding: 0" name=device_rescan value="Rescan">
	</form>';
	if (isset($_POST['device_rescan']) !== FALSE) { 
                $IParray = [$results["IP_Address"]];	
	        include('../TowerScanner.php');
	        header('Refresh:0');
	}
	if (isset($_POST['device_logs']) !== FALSE) { //this whole section will most likely need changed to a form if more elements are added above
	        header('Location: ../changelog/?IP_Address='. $IP);
	}
	if (isset($_POST['edit_device']) !== FALSE) { 
		header('Location: ../manualUpdate/?IP_Address=' . $IP); 
	} //passes IP to updated database page
	elseif (isset ($_POST['delete_device']) !== FALSE) { 
        	include('../updateTowerScanDB.php');
        	$output = deleteDevice($IP, $three, $two);
        	if ($output == "SUCCESS") { header('Location: ../index.php');  
                } else { print $output; } 
        	
	}
	print '</body>';
include('../footer.php');  
print '</div></div></html>';

?>
