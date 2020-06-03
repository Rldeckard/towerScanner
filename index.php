<?php

// Set up some variables that may change over time, put this at the top of the script for easy access

// SNMP Community String
$community = '!highspeed';
$timeout = 50000;
include('connectdb.php');
snmp_set_quick_print(1); // Format the SNMP output
$scan_sections = array(); 
$scan_section1 = array();
$scan_section2 = array();
$scan_section3 = array();
$scan_section4 = array();
$IParray = array();
$number = 0;
$x = 0;
$down = 0;
$left = 200;
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


$android = strpos($_SERVER['HTTP_USER_AGENT'],"Android"); //checks to see if it's a mobile device that's connecting
$bberry = strpos($_SERVER['HTTP_USER_AGENT'],"BlackBerry");
$iphone = strpos($_SERVER['HTTP_USER_AGENT'],"iPhone");
$ipod = strpos($_SERVER['HTTP_USER_AGENT'],"iPod");
$webos = strpos($_SERVER['HTTP_USER_AGENT'],"webOS");

if ($android || $bberry || $iphone || $ipod || $webos == true) //redirects phones to mobile site
{
	print '<html><head><link rel="stylesheet" type="text/css" href="css/m_style.css" />';
}
else {
	print '<html><head><link rel="stylesheet" type="text/css" href="css/style.css" />';
	
}
print '<title>THIS - Tower Scanner</title>';
print '</head>';
print '<body><div id="container">';
include('header.php');
?>

<style>

#checkbox {
    position: relative;
    left: 100px;
    border-radius: 4px;
    padding: 3px;
    }
input[type=checkbox] {
    width: 25px;
    height: 25px;
    position: relative;
    left: 100px;
    top: 8px;
    cursor: pointer;
    background-color: red;
    }
option {
    font-weight: bold;
}
    

</style>
<?php
// 1=user 2=field 3=sales 4=billing 5=dispatch 50=manager 99=admin';
if($_SESSION['sess_groupid'] < 0) {
  header('Location: /noaccess.php');
}


function GetTowers($query){ //prints our evernote format

   $fetch = mysqli_num_rows($query); //gets query from tower number or general search
   if ($fetch > 0)
   { //if statement if there's an entry for an IP already. Used to update current entries
     while ($results = mysqli_fetch_assoc($query))
     {	
#	if ($results["Down_Count"] < 7) 
#	{
		if ($results["UP_Check"] === "CONNECTED") { //colors IP depending if device is up or down
       			$up_check_color = '#0000EE';
		} elseif ($results["UP_Check"] === "DISCONNECTED") {
		        $up_check_color = '#E30000';
		} else {
                        $up_check_color = 'black';
               	}
	        print '<form method=POST id="results" style="font-family:tahoma;font-size:13px;">'; //evernote formatting below
	        if ($results["INF_Type"] !== "0000"){ print '<br /><span style="font-weight:bold;color:#E30000">'.$results["INF_Type"].'</span><br />'; }  
	        if ($results["Device_Name"] !== "0000"){ print '<span>-Name : <span style="font-weight:bold;">'.$results["Device_Name"].'</span></span><br>'; }
	        if ($results["IP_Address"] !== "0000"){ print '<span>-IP  : <a style="color:'.$up_check_color.';text-decoration:underline;" href="http://'.$results["IP_Address"].'" target="_ap">'.$results["IP_Address"].'</a></span><br>'; }
	        if ($results["Device_Make"] !== "0000"){ print '<span>--Make     : '.$results["Device_Make"].' </span><br />'; }
	        if ($results["Device_Type"] !== "0000"){ print '<span>--Model  : '.$results["Device_Type"].'</span><br />'; }
	        if ($results["Frequency"] !== "0000"){ //removes fields that aren't needed for non broadcasting equipment
			print '<span>--Antenna : </span><br />';
			print '<span>--Facing : </span><br />';
			print '<span>--Freq: '.$results["Frequency"].'</span><br />'; 
		}
	        if ($results["Channel_Width"] !== "0000"){ 
			print '<span>--Channel Width: '.$results["Channel_Width"].'</span><br>';
		        print '<span>--Polarity : </span><br />'; //no special reason this isn't with the stuff above. Just wanted it below channel width
		}
        	if ($results["ColorCode_SSID"] !== "0000"){ print '<span>--Color Code/SSID: '.$results["ColorCode_SSID"].'</span><br />'; }
	        print '<span>--Parent : </span><br />';
        	print '<span>--Notes : </span><br />';
	        print '<span>--Config File: </span><br />';
		print '<a id="more" style="color:#0000EE;text-decoration:underline;padding:5px;" href="devicePage/?IP_Address='.$results["IP_Address"].'" >More</a>'; //detail link of equipment
		print '</form>';
#	}
     }//closes while                      
   }//closes if fetch                   
}
print '<div id="body">';
print '<h1>Tower Scanner</h1>';
print '<form action="" method=POST id=form>';
print '<label id="note_label">Access TowerNote:</label>'; 
print '<span style="position:relative;left:10;">Area: <input type=number  max=99 min=1 placeholder=0 name=area_code size=2 maxlength=2 autocomplete="off" value=';
if(isset($_POST['area_code'])) { print $_POST['area_code']; }
  print '></span> ';
print '<span style="position:relative;left:20px;">Location Code: <input type=number max=9999 min=1 placeholder=0 name=tower_code size=6 maxlength=4 autocomplete="off" value=';
if(isset($_POST['tower_code'])) {  print $_POST['tower_code']; }
  print '></span>';  
print '<input type=checkbox id="checkbox" name=search_tower>';
print '<span id="checkbox_label" style="position:relative;left:110px">Update Tower</span><br><br>';
print '<label id="search_label"> or  Search: </label><input type=text id="search" name=tower_search placeholder="Tower Search" maxlength=40 style="width:30%;" autocomplete="off"';
if (isset($_POST['tower_search'])) {  $search = $_POST['tower_search']; }
print '> ';
print '<input type=submit style="position:relative;height:25px;width:100px" name=submit value=Submit>';
print '<select id="dropSearch" name="dropSearch" style="position:relative;left:10px;background-color:lightgray;border-radius:5px;height:25px;">'; //dropbox starts here, values defined further down
print '<option value="General">General Search</option>';
print '<option value="Tower_Number">Tower Number</option>';
print '<option value="INF_Type">Infrastructure Type</option>';
print '<option value="Device_Name">Device Name</option>';
print '<option value="IP_Address">IP Address</option>';
print '<option value="Device_Make">Make</option>';
print '<option value="Device_Type">Model</option>';
print '<option value="Frequency">Frequency</option>';
print '<option value="ColorCode_SSID">ColorCode/SSID</option>';
print '<option value="Channel_Width">Channel Width</option>';
print '</select><br>';

print "<script>

function onClick(selectObject) {

        var value = selectObject;

        if (value > 0) {
	        window.location.href = '?file_id=' + value; //redirects to index.php setting the file id for the section below
        }

}

function resetForm() {
	document.getElementById('form').reset();
}

</script>

	<select id=export_dropdown style='position:absolute;right:20px;top:20px;background-color:#22BB22;height:30px;border-radius:5px;font-weight:bold' onchange='onClick(this.value); resetForm();'>
		<option value='0' selected disabled hidden>Database Exports</option>
		<option value='1'>PC Full Export</option>
		<option value='2'>PowerCode BH Export</option>
		<option value='3'>PowerCode AP Export</option>
	</select>

</form>";



if(isset($_GET['file_id']) !== FALSE) {
        include('exports/database_export.php');
        sleep(1); //pauses before downloading nonexistant file
        
                switch($_GET['file_id']) //longest way possible of lining out a dropbox menu
                {
                case "1": header('Location: exports/export.csv'); break;
                case "2": header('Location: exports/backhaul_export.csv'); break;
                case "3": header('Location: exports/ap_export.csv'); break;
                }
}

if(isset($_POST['submit'])){
  $start_time = MICROTIME(TRUE);
  
  if (isset($_POST['search_tower']) !== FALSE && isset($_POST['tower_code']) !== FALSE && isset($_POST['area_code']) !== FALSE) { //rescans tower after the check box is pressed
	$IPentry = '.'.$_POST['area_code'].'.'.$_POST['tower_code'].'.'; //stars process for appending unknown devices to be rescanned
	$nmapip = '10'.$IPentry.'0/24 ';
	exec('nmap -sP '.$nmapip, $output); //scans the /24. Faster than scanning in a loop. Only returns up IPs so might cause issues later if that changes
        foreach ($output as $line)
	{
		preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $line, $matches); //pulls IP from nmap return string
		if (!empty($matches[0])){
			$matchip = $matches[0];
			array_push($IParray, $matchip);
		}
	}
			
	$unkquery = mysqli_query($conn, "SELECT IP_Address FROM TowerScan.Unknown;");
	$unkfetch = mysqli_num_rows($unkquery);
	if ($unkfetch > 0) {
		while ($unkresult = mysqli_fetch_assoc($unkquery)) { 
			if (strpos($unkresult["IP_Address"], $IPentry) !== FALSE) { array_push($IParray, $unkresult["IP_Address"]); } //puts unknown IPs into list only if it's not already there
		}
	}
	include('TowerScanner.php');
  }
  print '<hr />';

	if(!empty($search))
	{
		switch($_POST['dropSearch']) //longest way possible of lining out a dropbox menu
		{
		case "General": 
		$param = "( Device_Name LIKE '%$search%') OR 
		(Tower_Number LIKE '%$search%') OR 
		(INF_Type LIKE '%$search%') OR
		( Device_Make LIKE '%$search%') OR 
		(Device_Type LIKE '%$search%') OR 
		(ColorCode_SSID LIKE '%$search%') OR
		( Frequency LIKE '%$search%') OR 
		(Channel_Width LIKE '%$search%') OR
                (IP_Address LIKE '%$search%')"; 
	        break;
		case "Device_Name": $param = "( Device_Name LIKE '%$search%')"; break; 
                case "Tower_Number": $param = "(Tower_Number LIKE '%$search%')"; break; 
                case "INF_Type": $param = "(INF_Type LIKE '%$search%')"; break; 
		case "Device_Name": $param = "( Device_Make LIKE '%$search%')"; break;
		case "Device_Make": $param = "( Device_Make LIKE '%$search%')"; break;
		case "Device_Type": $param = "(Device_Type LIKE '%$search%')"; break;
		case "ColorCode_SSID": $param = "(ColorCode_SSID LIKE '%$search%')"; break;
		case "Frequency": $param = "( Frequency LIKE '%$search%')"; break; 
		case "Channel_Width": $param = "(Channel_Width LIKE '%$search%')"; break;
                case "IP_Address": $param = "(IP_Address LIKE '%$search%')"; break; 
		}
		$search_query = mysqli_query($conn, "SELECT * FROM TowerScan.Towers WHERE $param ; " );
		GetTowers($search_query);
	} 
	else {
	$octet2 = $_POST['area_code'];
	$octet3 = $_POST['tower_code'];
	$octet4 = 1; //to start IP loop from 1-255

	while($octet4 < 255) //runs through all IP's and organizes for presentation. Can probably updated to list by inf type but this works. Better way might be to sort in the database though. 
	{
		$scan_rawip = "10." . $octet2.".".$octet3.".".$octet4;
		if ($octet4 == 1 || $octet4 == 129 || $octet4 == 137 || $octet4 == 145 || $octet2 == 0)//removes unneeded router IPs
		{                                                                                         
		        $scan_rawip = "0000";
		}                                                                                         
		if (($octet4 > 19 && $octet4 < 129) || ($octet4 < 240 && $octet4 > 150))//AP range, typically near top            
		{                                                                                         
			$scan_section1[] = $scan_rawip;                                                                   
		        sort($scan_section1);                                                                        
		}                                                                                                
		if ($octet4 == 254)//router range, typically below APs                                    
		{                                                                                         
		        $scan_section2[] = $scan_rawip;                                                                   
		}                                                                                         
		if ($octet4 < 19 && $octet4 > 1)//switch range, typically close to bottom                 
		{                                                                                         
		        $scan_section3[] = $scan_rawip;                                                                   
		        sort($scan_section3);                                                                        
		}         	                                                                                
		if ($octet4 > 129 && $octet4 < 150)//BH range, typically at bottom                        
		{                                                                                         
		        $scan_section4[] = $scan_rawip;                                                                   
		        sort($scan_section4);                                                                        
		}         	                                                                               
		$scan_sections = array($scan_section1, $scan_section2, $scan_section3, $scan_section4);                            
		$octet4++;
	}//closes while loop                                                  
	foreach ($scan_sections as $scan_section){                                                           
		foreach($scan_section as $ip){                                        
			$ip = escapeshellarg($ip);
			$query = mysqli_query($conn, "SELECT * FROM TowerScan.Towers WHERE IP_Address = $ip AND Down_Count < 7; ");
			getTowers($query);  
		}
	}
	}
  
#  $x = 0;
#  foreach($output as $line) {
#    $test = strpos($line, 'Host is up');
#    if ($test === false) {
#      //do nothing
#    } else {
#      $string = $output[$x-1];
#      preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $string, $matches);
#      $ip = $matches[0];
#  $preTowerNumber =  $octet2 . $octet3; //Tower number code
#  print $preTowerNumber;
#  $TowerNumberLen = strlen($preTowerNumber);
#  print $TowerNumberLen;
#  if ($TowerNumberLen == 2) { $spacer = "00"; }
#  if ($TowerNumberLen == 3) { $spacer = "0"; } 
#  if ($TowerNumberLen >= 4) { $TowerNumber = $octet2 . $octet3; }
#  else { $TowerNumber = $octet2 . $spacer . $octet3; }
#  print $TowerNumber;
#  GetTowers($TowerNumber);




   
  $stop_time = MICROTIME(TRUE);
  $time = $stop_time - $start_time;
  $time = round($time, 2);
  PRINT '<hr />Elapsed time was '.$time.' seconds.<hr />';
}
else
{	print '<hr>';    	
	print '<h1 id="changes_header"><br>Recent Changes</h1>';
	$query = mysqli_query($conn, "SELECT IP_Address FROM TowerScan.ChangeLog WHERE DateTime >= NOW() - INTERVAL 1 DAY ORDER BY DateTime DESC; "); //last 24 hours of changes 
        $fetch = mysqli_num_rows($query);
        print '<span id=change_count style="font-weight:bold;position:absolute;right:50px;top:170px">Showing <label style="font-size:23px;font-weight:normal;color:red">' . $fetch . '</label> new updates</span>';
        if ($fetch > 0)
        { //if statement if there's an entry for an IP already. Used to update current entries
		print '<div class="paddingBlock">'; 
		while ($changeresults = mysqli_fetch_assoc($query)) //gets IPs from changelog
            	{
			$towerquery = mysqli_query($conn, "SELECT * FROM TowerScan.Towers WHERE IP_Address = ".escapeshellarg($changeresults["IP_Address"])." ;"); //uses IPs to populate AP info
			while ($results = mysqli_fetch_assoc($towerquery))
			{	if ($results["UP_Check"] === "CONNECTED") {
			                $up_check_color = '#0000EE';
			        } elseif ($results["UP_Check"] === "DISCONNECTED") { 
			                $up_check_color = 'red';
			        } else { 
			                $up_check_color = 'black'; 
                                }
                                 
        			if ($number % 2 == 0 || $number == 0 ) { print '<div class="eqHWrap eqDisplay">'; } //don't touch. Specificall designed to make it act like it's printing two divs and wrapping them in another div for easier presentation
				print '<div class="eqHW equal">';
                                print '<p>';
				if ($results["INF_Type"] !== "0000"){ print '<span style="font-weight:bold;color:#E30000;">'.$results["INF_Type"].'</span><br>'; }
	        		if ($results["Device_Name"] !== "0000"){ print '--Name: <span style="font-weight:bold;">'.$results["Device_Name"].'</span><br>'; }
	        		if ($results["IP_Address"] !== "0000"){ print '<span>-IP : <a  style="color:'.$up_check_color.';text-decoration:underline;" href="http://'.$results["IP_Address"].'" target="_ap">'.$results["IP_Address"].'</a></span><br>'; }
			        if ($results["Device_Make"] !== "0000"){ print '<span>--Make: '.$results["Device_Make"].' </span><br />'; }
				if ($results["Device_Type"] !== "0000"){ print '<span>--Model: '.$results["Device_Type"].'</span><br />'; }
				if ($results["Frequency"] !== "0000"){ //removes fields that aren't needed for non broadcasting equipment
                			 print '<span>--Antenna : </span><br />';
		                	 print '<span>--Facing : </span><br />';
	                		 print '<span>--Freq: '.$results["Frequency"].'</span><br />';
			        }
        			if ($results["Channel_Width"] !== "0000"){
	 		        	 print '<span>--Channel Width: '.$results["Channel_Width"].'</span><br>';
	                		 print '<span>--Polarity : </span><br />'; //no special reason this isn't with the stuff above. Just wanted it below channel width
		        	}
			        if ($results["ColorCode_SSID"] !== "0000"){ print '<span>--Color Code/SSID: '.$results["ColorCode_SSID"].'</span><br />'; }
				print '<span>--Parent : </span><br />';
				print '<span>--Notes : </span><br />';
				print '<span>--Config File: </span><br />';
				print '<a style="color:#0000EE;text-decoration:underline;padding:5px;" href="./devicePage/?IP_Address='.$results["IP_Address"].'" >More</a>'; //detail link of equipment
				print '</p>';
				if ($number % 2 !== 0 && $number !== 0 ) { print '</div>'; }
				print '</div>';
				$number++;
#				$left += 500;
#				$down -= 250;
			}
#			print '</table>';                       
		}
		print '</div>';
        }//closes if fetch
}
include('footer.php');  
print '</div></div></html>';
?>