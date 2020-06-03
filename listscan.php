<?php
$sqlservername = "localhost";
$sqlusername = "towerscan";
$sqlpassword = "4correctGATHERguess";
$conn = new mysqli($sqlservername, $sqlusername, $sqlpassword);

function newIPscan($Scan_Address_Array) { //combines feshly scanned IPs with IPs already in the database for more complete scans
        global $conn;
        $unkquery = "SELECT IP_Address FROM TowerScan.Unknown; ";
        foreach($conn->query($unkquery) as $unkresult)
        {
               $unkresults[] = $unkresult["IP_Address"]; //puts IPs into a normal array
        }
        $query = mysqli_query($conn, "SELECT IP_Address FROM TowerScan.Towers; ");
        while ($exit = mysqli_fetch_assoc($query))
        {
                $results[] = $exit["IP_Address"];
        }
        $newResults = array_diff($Scan_Address_Array, $results); //gets the extra IPs from "iplist" and combines to database list
        $newIPs = array_merge($results, $newResults, $unkresults); //puts all lists of Ips together
        
/*	$query = mysqli_query($conn, "SELECT IP_Address FROM TowerScan.Towers WHERE LENGTH(MAC_Address) < 1 AND UP_Check = 'CONNECTED' AND LENGTH(SNMP_Community) > 1;"); //for custom queries
        while ($exit = mysqli_fetch_assoc($query))
        {
                $newIPs[] = $exit["IP_Address"];
        }
        print_r($newIPs);
*/
        return $newIPs;
}

	$networkIPstream = fopen("/var/www/html/allaccess/iplist.txt", 'r'); //Gets IP info from network scan iplist file
        $networkIPlist = stream_get_contents($networkIPstream); //Gets info from stream, makes it usable
        $Scan_Address = explode("\n",$networkIPlist); //Puts giant string into array using line breaks as queue to make new array value
        fclose($networkIPstream); //closes iplist stream as it's no longer needed.
        $IParray = newIPscan($Scan_Address); //using IParray variable is the only way this works with TowerScanner
        include('TowerScanner.php');
?>