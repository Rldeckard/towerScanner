<?php

// Set up some variables that may change over time, put this at the top of the script for easy access

// SNMP Community String
$community = '!highspeed';
$alt_community = '_highspeed';
$timeout = 50000;
$sqlservername = "localhost";
$sqlusername = "towerscan";
$sqlpassword = "4correctGATHERguess";
$sqldbmain = "TowerScan";
$conn = new mysqli($sqlservername, $sqlusername, $sqlpassword);
$empty_column = "0000";
$parts = array();
$sections = array();
$section1 = array();
$section2 = array();
$section3 = array();
$section4 = array();
$error = array();
$octet1 = 0;
$octet2 = 0;
$octet3 = 0;
$octet4 = 0;
$spacer = 0;
$length = 0;
$default_mac = "00:00:00:00:00:0A";
$ubiquiti_cw = "0";
snmp_set_quick_print(1); // Format the SNMP output

//Need to add 10 more characers to the Device Name section of the database

//------------------------------------ Should not need to change anything below this line --------------------------------------------//
//------------------------------------------------------------------------------------------------------------------------------------//

      include('updateTowerScanDB.php'); //all the functions needed to put together the Database.
      
  foreach($IParray as $rawip) { 		
      $parts = explode(".", $rawip);
      if (isset($parts[1]) && isset($parts[3]) && !empty($parts[3]))
      { 	
      $octet2 = $parts[1];
      $octet4 = $parts[3];										
      }
      if ($octet4 == 1 || $octet4 == 129 || $octet4 == 137 || $octet4 == 145 || $octet2 == 0)//removes unneeded router IPs
      {												
        $rawip = "0000";									
      }												
      if (($octet4 > 19 && $octet4 < 129) || ($octet4 < 240 && $octet4 > 150))//AP range, typically near top		
      {												
        $section1[] = $rawip;									
        sort($section1);									
      }													
      if ($octet4 == 254)//router range, typically below APs					
      {												
        $section2[] = $rawip;									
      }												
      if ($octet4 < 19 && $octet4 > 1)//switch range, typically close to bottom			
      {												
        $section3[] = $rawip;  									
        sort($section3);									
      }												
      if ($octet4 > 129 && $octet4 < 150)//BH range, typically at bottom			
      {												
        $section4[] = $rawip;									
        sort($section4);									
      }												
     $sections = array($section1, $section2, $section3, $section4);				
  }//closes foreach networkIParray								
  foreach($sections as $section){
   foreach($section as $ip){	
      $parts = explode(".", $ip);
      if (isset($parts[1])){ $octet2 = $parts[1]; }                                                                      
      if (isset($parts[2])){ $octet3 = $parts[2]; }     
      if (isset($parts[3])){ $octet4 = $parts[3]; }                                                  #
      $preTowerNumber =  $octet2 . $octet3; //Tower number code
      $TowerNumberLen = strlen($preTowerNumber);
      if ($TowerNumberLen == 2) { $spacer = "00"; } 
      if ($TowerNumberLen == 3) { $spacer = "0"; }
      if ($TowerNumberLen >= 4) { $TowerNumber = $octet2 . $octet3; }
      else { $TowerNumber = $octet2 . $spacer . $octet3; }
      // get the name of the device
      $name = @snmp2_get($ip, $community, "sysName.0", $timeout);
      if(empty($name)) {
        // If nothing is returned, try different community string
        $name = @snmp2_get($ip, "_highspeed", "sysName.0", $timeout);
        $snmp_version = "other";
        if(empty($name)) {
          // Still nothing, try different SNMPv1
          $name = @snmpget($ip, $community, "sysName.0", $timeout);
          if(empty($name)) {
            // If nothing still is returned, call it unknown
               UpdateTableUnknown($ip, $TowerNumber);
          }//closes unknown empty name
           else {
            // Known SNMPv1 device
            $snmp_version = 1;
          }//closes else
        }//closes snmp1 if
      }//closes epmp empty name if 
      else {
        // Known SNMPv2c device
        $snmp_version = 2;
      }//closes else 
      
      if($snmp_version == 2){
        $device = snmp2_get($ip, $community, "RFC1213-MIB::sysDescr.0", $timeout);
        // Known Netonix Devices
        if(strpos($device, 'Netonix') !== false){
          $inf_type = "POE:";
          $make = "Netonix";
          $device = substr($device, 8);
          UpdateTableTowers($inf_type, $name, $ip, $default_mac, $make, $device, $empty_column, $empty_column, $empty_column, $TowerNumber, $community, $snmp_version);
        }//closes Netonix if
        // Known PacketFlux Devices
        if(strpos($device, 'PacketFlux') !== false){
          $make = "PacketFlux";
          $inf_type = "POE:";
          UpdateTableTowers($inf_type, $name, $ip, $default_mac, $make, $device, $empty_column, $empty_column, $empty_column, $TowerNumber, $community, $snmp_version);
        }//closes PacketFlux if
        // Known MikroTik Devices
        if(strpos($device, 'RouterOS') !== false){
          $make = "MikroTik RouterOS";
          $inf_type = "Router:";
	  print UpdateTableTowers($inf_type, $name, $ip, $default_mac, $make, $device, $empty_column, $empty_column, $empty_column, $TowerNumber, $community, $snmp_version);
        }//closes routeros if
        // Known APC Devices
        if(strpos($device, 'APC') !== false){
          $make = "American Power Conversion";
          $apc_type = trim(snmpget($ip, $community, ".1.3.6.1.4.1.318.1.1.1.1.1.1.0", $timeout),'"');
          $inf_type = "UPS:";
          UpdateTableTowers($inf_type, $name, $ip, $default_mac, $make, $apc_type, $empty_column, $empty_column, $empty_column, $TowerNumber, $community, $snmp_version);
        
        }//closes APC if
	if(strpos($device, 'Remote') !== false){
              $make = "Duracom";
              $inf_type = "UPS:";
              $device = "THIS - UPS";
              UpdateTableTowers($inf_type, $name, $ip, $default_mac, $make, $device, $empty_column, $empty_column, $empty_column, $TowerNumber, $community, $snmp_version);
	}
	if(strpos($device, 'CMM') !== false){
	        $inf_type = "POE:";
        	$make = "Cambium Networks";
 		UpdateTableTowers($inf_type, $name, $ip, $default_mac, $make, $device, $empty_column, $empty_column, $empty_column, $TowerNumber, $community, $snmp_version);
	}
      }//closes snmp_version 2 
      elseif($snmp_version == 1) {
        $device = snmpget($ip, $community, "RFC1213-MIB::sysDescr.0", $timeout);
        // Check for ubnt device
        $make = substr(@snmpget($ip, $community, ".1.2.840.10036.3.1.2.1.2.5", $timeout), 1, -7);
        if(!empty($make) && (strpos($make, 'Ubiquiti') !== false)){
	        if($octet4 < 150 && $octet4 > 129){ $inf_type = "Backhaul:"; } else { $inf_type = "Access Point:"; }
        	  // Known Ubiquiti Devices
        	$ubiquiti_cw = NULL;
		$firmware = snmpget($ip, $community, ".1.2.840.10036.3.1.2.1.4.5", $timeout); 
		if (strpos($firmware,'v5.5.') !== FALSE) {
			$ubnt_ssid = trim(snmpget($ip, $community, ".1.2.840.10036.1.1.1.9.5", $timeout),'"'); //before v5.5.10
			$UBNTgetStats = UBNTgetStats($ip);
			$ubnt_freq = $UBNTgetStats[0];
			$ubiquiti_cw = $UBNTgetStats[1];			
                        if (empty($ubnt_freq)) {
				$ubnt_freq = snmpget($ip, $community, ".1.3.6.1.4.1.14988.1.1.1.1.1.7.5", $timeout); 
                  		$ubiquiti_cw = UBNTsmStats($ip); 
                        } //only for v5.5 SMs(BHS)
                        
		} else {
                	$ubnt_ssid = trim(snmpget($ip, $community, ".1.3.6.1.4.1.41112.1.4.5.1.2.1", $timeout),'"'); //after v5.5.10
			$ubiquiti_cw = snmpget($ip, $community, ".1.3.6.1.4.1.41112.1.4.5.1.14.1", $timeout);
	                $ubnt_freq = snmpget($ip, $community, ".1.3.6.1.4.1.41112.1.4.1.1.4.1", $timeout);
		}
		
		$ubnt_mac = snmpget($ip, $community, ".1.3.6.1.2.1.2.2.1.6.2", 100000); //only AP mac. SMs are screwed
		$ubnt_type = trim(snmpget($ip, $community, ".1.2.840.10036.3.1.2.1.3.5", $timeout),'"');
                print UpdateTableTowers($inf_type, $name, $ip, $ubnt_mac, $make, $ubnt_type, $ubnt_freq, $ubiquiti_cw, $ubnt_ssid, $TowerNumber, $community, $snmp_version);  
                
        } elseif(empty($make) && strpos($device, 'Linux 2.6.33') !== FALSE){
                $ubiquiti_cw = NULL;
                if ($octet4 > 129) { $inf_type = "Backhaul:"; } else { $inf_type = "Access Point:"; }
                $make = "Ubiquiti Networks";
                $ubnt_mac = snmpget($ip, $community, ".1.3.6.1.2.1.2.2.1.6.4", 100000);
                $ubnt_freq = snmpget($ip, $community, ".1.3.6.1.4.1.41112.1.3.1.1.21.1", 1000000);
                if ($ubnt_freq > 24000 && $ubnt_freq < 25000) { 
                	$ubnt_type = "AirFiber 24"; 
                	$ubiquiti_cw = "100";
		} 
		elseif ($ubnt_freq < 6000 && $ubnt_freq > 5000) { 
			$ubnt_type = "AirFiber 5x"; 
		} 
		else {
			$ubnt_type = "Unknown: " . $ubnt_freq;
		}
                $CWandSSID = AFgetCWandSSID($ip); //ssh into airFibers to get SW and SSID
                if ($ubiquiti_cw != "100") { $ubiquiti_cw = $CWandSSID[1]; } //ssh returns array. This splits it off. Doesn't check for Airfiber 24s due to slaves not having the option
                $ubnt_ssid = $CWandSSID[0];
                UpdateTableTowers($inf_type, $name, $ip, $ubnt_mac, $make, $ubnt_type, $ubnt_freq, $ubiquiti_cw, $ubnt_ssid, $TowerNumber, $community, $snmp_version);
        } else {
            UpdateTableUnknown($ip, $TowerNumber);
        }

      }//closes snmpversion1
      elseif($snmp_version == "other") {
        $device = snmp2_get($ip, $alt_community, "RFC1213-MIB::sysDescr.0", $timeout);      
        $newname = trim(snmp2_get($ip, $alt_community, ".1.3.6.1.4.1.17713.21.3.4.3.12.0", $timeout),'"'); 
        if($name == "CambiumNetworks" || $newname == "Cambium"){
          $snmp_version = 2;
          $inf_type = "Access Point:";
          $firmware = snmp2_get($ip, $alt_community, ".1.3.6.1.4.1.17713.21.1.1.1.0", $timeout);
          $name = trim(snmp2_get($ip, $alt_community, ".1.3.6.1.4.1.17713.21.1.1.13.0", $timeout),'"');
          $canopy_mac = trim(snmp2_get($ip, $alt_community, ".1.3.6.1.4.1.17713.21.1.1.15.0", $timeout),'"');  
          $make = "Cambium Networks";
          $canopy_model = "ePMP 1000";
	  $canopy_freq = snmp2_get($ip, $alt_community, ".1.3.6.1.4.1.17713.21.1.2.1.0", $timeout);          
	  $canopy_cw = snmp2_get($ip, $alt_community, "1.3.6.1.4.1.17713.21.1.2.2.0", $timeout);
	  switch($canopy_cw)
	  {
              case "2": $canopy_cw = "40"; break;
	      case "1": $canopy_cw = "20"; break;
              default : $canopy_cw = "0000"; break;
	  }
	  $canopy_ssid = trim(snmp2_get($ip, $alt_community, ".1.3.6.1.4.1.17713.21.1.1.11.0", $timeout),'"');
	  UpdateTableTowers($inf_type, $name, $ip, $canopy_mac, $make, $canopy_model, $canopy_freq, $canopy_cw, $canopy_ssid, $TowerNumber, $alt_community, $snmp_version);
        } elseif(strpos($device, 'Remote') !== false){
              $snmp_version = 2;
              $make = "Duracom";
              $inf_type = "UPS:";
              $device = "THIS - UPS";
              $device_mac = $default_mac;
              UpdateTableTowers($inf_type, $name, $ip, $device_mac, $make, $device, $empty_column, $empty_column, $empty_column, $TowerNumber, $alt_community, $snmp_version);
        } else {
              UpdateTableUnknown($ip, $TowerNumber);
        }  
      } else {
        UpdateTableUnknown($ip, $TowerNumber);
      }
      
      // Known Cambium Devices    
      if(strpos($device, "CANOPY") !== false){
      
      
      $canopy_device = snmp2_get($ip, $community,"WHISP-BOX-MIBV2-MIB::boxDeviceType.0",$timeout);
      $make = "Cambium Networks";
      $canopy_mac = snmp2_get($ip, $community,".1.3.6.1.4.1.161.19.3.3.1.3.0", $timeout);
      
      
        if (strpos($device, '11') !== FALSE)
        {
            $canopy_color = snmp2_get($ip, $community, ".1.3.6.1.4.1.161.19.3.3.2.2.0", $timeout); //11.2 fw
            
            
        }
        
        
        
	if (strpos($canopy_device, 'Timing Slave') !== FALSE)
        {
            $inf_type = "Backhaul Slave:";
            $canopy_cw = snmp2_get($ip, $community,".1.3.6.1.4.1.161.19.3.3.2.83.0",$timeout);
            $canopy_freq = snmp2_get($ip, $community,".1.3.6.1.4.1.161.19.3.2.2.67.0",$timeout);
            $canopy_freq = substr($canopy_freq, 0, 4);
            $canopy_color = "0000";
	} elseif (strpos($canopy_device, 'Subscriber Module') !== FALSE) 
        {
            $inf_type = "Subscriber Module:";
            $canopy_color = snmp2_get($ip, $community, ".1.3.6.1.4.1.161.19.3.3.2.2.0", $timeout); //gets active color code
            $canopy_freq = snmp2_get($ip, $community,".1.3.6.1.4.1.161.19.3.2.2.67.0",$timeout);
            $canopy_freq = substr($canopy_freq, 0, 4);
 
            if ($canopy_freq > 9000 && $canopy_freq < 10000) //required in this spot as the part below relies on freq
                    {
            $canopy_freq = $canopy_freq/10;
            $canopy_freq = strval($canopy_freq); //Makes intvar strval for comparing database variables
 
            }
            
            $canopy_cwraw = trim(snmp2_get($ip, $community, "1.3.6.1.4.1.161.19.3.2.2.122.0", $timeout), '"');
                        
            if ($canopy_freq > 900 && $canopy_freq < 1000) {
                
                switch($canopy_cwraw) {
                
                	case "1": $canopy_cw = "5"; break;
	        	case "2": $canopy_cw = "7"; break;
	        	case "3": $canopy_cw = "10"; break;
        		case "4": $canopy_cw = "20"; break;
	        	case "5": $canopy_cw = "30"; break;
	        	default: $canopy_cw = "0000"; break;
	        	
                }
                
            }
            elseif ($canopy_freq > 2400 && $canopy_freq < 2500) {
            
                switch($canopy_cwraw) {
                
                        case "3": $canopy_cw = "10"; break; //not sure about 5mhz
                        case "4": $canopy_cw = "15"; break;
                        case "5": $canopy_cw = "20"; break;
                        default: $canopy_cw = "0000"; break;
            
                }
                
            }

        } else {
            $canopy_freq = snmp2_get($ip, $community,"WHISP-APS-MIB::radioFreqCarrier.1",$timeout);
            $canopy_freq = substr($canopy_freq, 0, 4); //Division turns var into int
            if (strpos($canopy_device, 'OFDM') !== FALSE) {
            
            $canopy_cwraw = snmp2_get($ip, $community,".1.3.6.1.4.1.161.19.3.3.2.83.0",$timeout);
            $canopy_cw = substr($canopy_cwraw, 0, 2);
            
            }
            
            $canopy_color = snmp2_get($ip, $community,".1.3.6.1.4.1.161.19.3.1.10.1.1.6.1",$timeout);
            $inf_type = "Access Point:";
        }
        
        
        
        if (strpos($canopy_device, 'MIMO OFDM')!== FALSE)
        {
            if (strpos($canopy_device, 'MIMO OFDM - Backhaul') !== FALSE)
            {
                $canopy_model = "PTP 450";
                if (strpos($canopy_device, 'Timing Master') !== FALSE) {
                    $inf_type = "Backhaul Master:";
                }
                
            } else {
                  $canopy_model = "PMP 450";
#                  $canopy_cw = trim($canopy_cw, '.');
            }
            
            if($canopy_cw == FALSE) {
	            $canopy_cw = snmp2_get($ip,$community,"WHISP-BOX-MIBV2-MIB::channelBandwidth.0",$timeout);
            }

        } else {
            if (strpos($canopy_device,'Backhaul') !== FALSE)
            {
                  $canopy_color = snmp2_get($ip, $community, ".1.3.6.1.4.1.161.19.3.3.2.2.0", $timeout); //11.2 fw
                  if (strpos($canopy_device, 'OFDM') !== FALSE)
                  {
                      $canopy_model = "PTP 230 - BH50";
                  } else {
                      $canopy_model = "PTP 100 - BH20";
                  }
                  if (strpos($canopy_device, 'Timing Master') !== FALSE) {
                    $inf_type = "Backhaul Master:";
                    $canopy_freq = snmp2_get($ip, $community, ".1.3.6.1.4.1.161.19.3.1.1.2.0", $timeout);
                    $canopy_freq = substr($canopy_freq, 0, 4);
                  }
                  
            } else { 
                $canopy_model = "PMP 100";
                if ($canopy_freq > 9000 && $canopy_freq < 10000) 
                {
                    $canopy_cw = '8'; //default value for 900mhz pmp 100 channel width. 
                    
                } 
                elseif (($canopy_freq < 6000 && $canopy_freq > 1000) || $canopy_freq > 10000 && $canopy_freq < 60000)
                {
                    $canopy_cw = '20'; 
                }
            
            }  
        }      

        if ($canopy_freq > 9000 && $canopy_freq < 10000) //finds any freq over a standard freq length
        {
        	$canopy_freq = $canopy_freq/10;
        	$canopy_freq = strval($canopy_freq); //Makes intvar strval for comparing database variables
        }
        
        if ($canopy_freq == "wire") //returns wire instead of none when freq is disabled
        {
        	$canopy_freq = "None";
        }
        
        if (strpos($canopy_cw, '.')) //removes 
	{ 
        	$canopy_cw = round($canopy_cw);
#		$canopy_cw = strval($canopy_cw); //changes to string for database comparison
		 
        }
        UpdateTableTowers($inf_type, $name, $ip, $canopy_mac, $make, $canopy_model, $canopy_freq, $canopy_cw, $canopy_color, $TowerNumber, $community, $snmp_version);
      }
#      $error = error_get_last();
   }//stops foreach ip
  }
#print '</div></div></html>';
?>