<?php
$sqlservername = "localhost";
$sqlusername = "towerscan";
$sqlpassword = "4correctGATHERguess";
$conn = new mysqli($sqlservername, $sqlusername, $sqlpassword);
session_start();
if(!isset($_SESSION['sess_user_id']) || (trim($_SESSION['sess_user_id']) == '')) {
  header("location: /login.php");
  exit();
}

print '<html><head><link rel="stylesheet" type="text/css" href="../css/style.css" /></head>';
print '<title>THIS - Tower Scanner</title>';
print '<body><div id="container">';
include('../header.php');

// 1=user 2=field 3=sales 4=billing 5=dispatch 50=manager 99=admin';
if($_SESSION['sess_groupid'] < 0) {
  header('Location: /noaccess.php');
}

print '<div id="body" style="background-color:#F0F0F0">';
	print '<h1>Change Log</h1>';
	print '<table id="changelog" align="center">';
	print '<tr><th style=width: 1%>ID</th><th style=width:1%>IP Address</th><th style=width:35%>Previous Entry</th><th style=width:35%>New Entry</th><th style=width:1%>Tower Number</th><th style=width:20%>Modified Date/Time</th></tr>';
	if (isset($_GET['IP_Address']) !== FALSE) {
        	$IP = $_GET['IP_Address'];
	        $IP_Address = escapeshellarg($IP);
		$query = mysqli_query($conn, "SELECT * FROM TowerScan.ChangeLog  WHERE IP_Address = $IP_Address ORDER BY DateTime DESC;");
		$fetch = mysqli_num_rows($query);
		print '<form  method=get action="../devicePage/" >';
		print '<input type=hidden name=IP_Address value="'.$IP.'" >'; //hidden input to export ip
		print '<input style="background-color:gray;margin:10px" type=submit value="Back to Device"></input></form>';
	} else {
	        $query = mysqli_query($conn, "SELECT * FROM TowerScan.ChangeLog ORDER BY DateTime DESC LIMIT 1000;");
        	$fetch = mysqli_num_rows($query);
	}
	if ($fetch > 0)
	{ //if statement if there's an entry for an IP already. Used to update current entries
	        $comp_date = new DateTime();
	        $oneDayPeriod = new DateInterval('P1D'); //Period 1 day interval
	        $comp_date->sub($oneDayPeriod);
#	        var_Dump($comp_date);
#	        print $comp_date;
                $count = 0;
        	while ($results = mysqli_fetch_assoc($query))
		{	$count++;
#			if ($results["DateTime"] == $comp_date["date"]) {
#			        print '<tr style="border-style:solid;border-bottom-color:red;">';
#                        }
#                        else {
			print '<tr>';
#			}
                        print '<td><a href="../devicePage/?IP_Address='.$results["IP_Address"].'" style=color:b70009;font-weight:bold>'.$count.'</td>';
			print '<td><a href="http://'.$results["IP_Address"].'/" target="_external">'.$results["IP_Address"].'</td>';
			print '<td align="center" width=100px>'.$results["Prev_Entry"].'</td>';
			print '<td align="center">'.$results["New_Entry"].'</td>';
			print '<td>'.$results["Tower_Number"].'</td>';
			print '<td>'.$results["DateTime"].'</td></tr>';
		 
		}
        }//closes if fetch
        
        print '</table>';
include('/admin/scantower/footer.php');
print '</div></div></html>';

?>
