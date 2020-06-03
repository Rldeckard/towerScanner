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
$INF_Type = $Device_Name = $IP_Address = $Device_Make = $Device_Type = $Frequency = $Channel_Width = $ColorCode_SSID = $Tower_Number = '"0000"';
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
label {
        margin: 1;
        display: inline-block;
        float: left;
        clear: left;
        width: 200px; 
        padding-right: 20px;
        padding-bottom: 20px;
	padding-left: 25px;
        text-align: left;
}

#table {
        text-align: left;
        width: 100%;
        box-sizing: border-box;
        font-size: 20px;
}
select, input[type=text] {
        height: 25px;
        display: block;
        text-align: left;
        float: left;
        border-radius: 2px;
}
select {
        background-color: lightgray;
        border-radius: 3px;
}

</style>
<title>
Edit Device
</title>
</head>
<body><div id=container>
<?php
include ('../header.php');

// 1=user 2=field 3=sales 4=billing 5=dispatch 50=manager 99=admin';
if($_SESSION['sess_groupid'] < 0) {
  header('Location: /noaccess.php');
}
print '<div id="body" style="background-color:#F0F0F0">';

#if (isset($_POST['IP_Address']) !== FALSE) {
	if (isset($_GET['IP_Address']) !== FALSE) { 
	$IP = $_GET['IP_Address']; 
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
	$SNMP_Community = $results["SNMP_Community"];
	$SNMP_Version = $results["SNMP_Version"];
	$MAC_Address = $results["MAC_Address"];
	}
        $INF_query = mysqli_query($conn, "SELECT DISTINCT INF_Type FROM TowerScan.Towers; ");
        $Tower_query = mysqli_query($conn, "SELECT DISTINCT Tower_Number FROM TowerScan.Towers WHERE Tower_Number > 0 AND Tower_Number < 5000 ORDER BY Tower_Number; ");
        $Type_query = mysqli_query($conn, "SELECT DISTINCT Device_Type FROM TowerScan.Towers WHERE Device_Type != '' ORDER BY Device_Type; ");
        $Make_query = mysqli_query($conn, "SELECT DISTINCT Device_Make FROM TowerScan.Towers WHERE Device_Make != '' ORDER BY Device_Make; ");	
        $Width_query = mysqli_query($conn, "SELECT DISTINCT Channel_Width FROM TowerScan.Towers WHERE Channel_Width != '' AND Channel_Width != '0' AND Channel_Width != '0000' AND Channel_Width !=  $nine ORDER BY CAST(Channel_Width as INTEGER); ");
        
	print '<h1>Update Database Form</h1>';
	print '<hr>';
	print '<form id=table method=POST>';
	print '<label>Infrastructure Type: </label><select name="inf_type">';
	        print '<option value="'.$one.'">'.$one.'</option>'; //makes sure the current inf type is presented first
		while ($INF_results = mysqli_fetch_assoc($INF_query)) { //creates a dropdown menu of inf types from the database
                        if ($INF_results["INF_Type"] !== $one) { print '<option value="'.$INF_results["INF_Type"].'">'.$INF_results["INF_Type"].'</option>'; } // doesn't duplicate the inf type from above
		}
        print '</select><br>';
	print '<label>Tower Number: </label><select name="tower_number" style="width:75px">';
	        print '<option value="'.$two.'">'.$two.'</option>'; //makes sure the current inf type is presented first
                while ($Tower_results = mysqli_fetch_assoc($Tower_query)) {
                        if ($Tower_results["Tower_Number"] !== $two ) { print '<option value="'.$Tower_results["Tower_Number"].'">'.$Tower_results["Tower_Number"].'</option>'; } // doesn't duplicate the inf type from above
                }
        print '</select><br>';
	print '<label>Device Name: </label><input type=text id=input autocorrect=off min=1 name=device_name value="'.$three.'" />';
	print '<label>Device Type: </label><select name="device_type">';
                print '<option value="'.$four.'">'.$four.'</option>'; //makes sure the current inf type is presented first
                while ($Type_results = mysqli_fetch_assoc($Type_query)) {
                        if ($Type_results["Device_Type"] !== $four) { print '<option value="'.$Type_results["Device_Type"].'">'.$Type_results["Device_Type"].'</option>'; } // doesn't duplicate the inf
                }
        print '</select><br>';
	print '<label>Device Make: </label><select name="device_make">';
                print '<option value="'.$five.'">'.$five.'</option>'; //makes sure the current inf type is presented first
                while ($Make_results = mysqli_fetch_assoc($Make_query)) {
                        if ($Make_results["Device_Make"] !== $five) { print '<option value="'.$Make_results["Device_Make"].'">'.$Make_results["Device_Make"].'</option>'; } // doesn't duplicate the inf
                }
        print '</select><br>';
	print '<label>IP Address: </label><input type=text autocorrect=off min=1 name=ip_address value="'.$six.'" /><br>
	<label>Antenna: </label>
	<label>Facing: </label><input type=text autocorrect=off min=1 max=3 name=facing /><br>
	<label>Frequency: </label><input type=text autocorrect=off min=1 max=8 name=frequency value="'.$seven.'" /><br>
	<label>ColorCode/SSID: </label><input type=text autocorrect=off min=1 name=colorcode_ssid value="'.$eight.'" /><br>
	<label>Channel Width: </label><select name="channel_width" style="width:75px">';
                print '<option value="'.$nine.'">'.$nine.'</option>'; //makes sure the current inf type is presented first
                while ($Width_results = mysqli_fetch_assoc($Width_query)) {
                        print '<option value="'.$Width_results["Channel_Width"].'">'.$Width_results["Channel_Width"].'</option>';
                }
        print '</select><br>';
	print '<label>Parent: </label><input type=text autocorrect=off min=1 name=parent /><br>
	<label>Notes: </label><input type=text autocorrect=off name=notes /><br>
	<span style="float:right"><input type=submit style="background-color:green" name=submit_form value=Save></span></form></body>';
	print '<span style="float:right"><form method=GET action="../devicePage/">';
	if (isset($_GET['IP_Address']) !== FALSE) { print '<input type=hidden name=IP_Address value="'.$IP.'" ><input style="background-color:gray" type=submit value=Cancel>'; }
	print '</form></span>';
		
	if (isset($_POST['inf_type']) !== FALSE) { $INF_Type = $_POST['inf_type']; } //useless if statements to silence the "Undefined index" notice
	if (isset($_POST['tower_number']) !== FALSE) { $Tower_Number = $_POST['tower_number']; }
	if (isset($_POST['device_name']) !== FALSE) { $Device_Name = $_POST['device_name']; }
	if (isset($_POST['device_make']) !== FALSE) { $Device_Make = $_POST['device_make']; } 
	if (isset($_POST['device_type']) !== FALSE) { $Device_Type = $_POST['device_type']; } 
	if (isset($_POST['ip_address']) !== FALSE) { $IP_Address = $_POST['ip_address']; } 
#	$Antenna = escapeshellarg($_POST['antenna']);
#	$Facing = escapeshellarg($_POST['facing']);
        if (isset($_POST['frequency']) !== FALSE) { $Frequency = $_POST['frequency']; }
        if (isset($_POST['colorcode_ssid']) !== FALSE) { $ColorCode_SSID = $_POST['colorcode_ssid']; } 
	if (isset($_POST['channel_width']) !== FALSE) { $Channel_Width = $_POST['channel_width']; }
#       $Parent = escapeshellarg($_POST['parent']);
#       $Notes = escapeshellarg($_POST['notes']);
        
	if (isset($_POST['submit_form']) !== FALSE)
	{
		include('../updateTowerScanDB.php');
                UpdateTableTowers($INF_Type, $Device_Name, $IP_Address, $MAC_Address, $Device_Make, $Device_Type, $Frequency, $Channel_Width, $ColorCode_SSID, $Tower_Number, $SNMP_Community, $SNMP_Version);
                print '<meta content="0;url=../devicePage/?IP_Address='.$IP_Address.'" http-equiv="refresh">'; //if using more than 0 delay it gets weird
                exit();
	} 	

	print '<div style="position:absolute;bottom:0;right:0;">';
include('../footer.php');  
print '</div></div></div></html>';

?>
