<?php
  include('exports/remove.php');
  include('exports/powercode_import_formatter.php');
  $sql = 'SELECT * FROM TowerScan.Towers WHERE Down_Count < 2  ORDER BY IP_Address DESC';
  $var = 'Equipment_Name,MAC,IP,Device_Category,Device_Type,Notes,Status_Check,SNMP_Community,SNMP_Version,Network_Location_ID'."\r\n"; // CSV headers
  $var1 = 'IP_Address,Frequency'."\r\n";
  $var2 = 'Device_Name,IP_Address,Frequency,,,,,,'."\r\n";
  $count = 0;
  foreach ($conn->query($sql) as $row) {
    $Device_Type = '';
    $one = $row["INF_Type"];
    if (strpos($one,':')) { $Device_Category = trim($one, ':'); }
    $two = $row["Tower_Number"];
    $name = $row["Device_Name"];
    $type = $row["Device_Type"];
    $five = $row["Device_Make"];
    $IP = $row["IP_Address"];
    $mac = $row["MAC_Address"];
    $frequency = $row["Frequency"];
    $eight = $row["ColorCode_SSID"];
    $nine = $row["Channel_Width"];  
    
    
    $parts = explode(".", $IP);
    if (isset($parts[3]) !== FALSE) {
        $octet4 = $parts[3];
    }
    if ($octet4 > 59 && $octet4 < 90) {
    }
    else {
    $count++;
    
    if (sizeof($mac) < 17 ) { //adds leading zeros to mac
        $mac_boom = explode(":", $mac);
        foreach($mac_boom as $key => $mac_cell) {
            if(strlen($mac_cell) < 2) {
                $mac_boom[$key] = '0' . $mac_cell;
            }
            $mac = implode(":", $mac_boom);
        }
    }
    
    if (strpos($mac, '-') !== FALSE) { //makes all the mac addresses the same style
        $mac_boom = explode("-", $mac);
        $mac = implode(":", $mac_boom);
    }    
    $mac = strtoupper($mac);
    
    if (isset($diff[$two]) !== FALSE) { //gets tower name to append to device name
        $Site = $diff[$two]; //full tower nam Ex. 0000 Tower
        $Network_Site = $NetIDConverter[$Site]; //site ID powercode specific
    }
    
       if (strpos($one, 'Access Point') !== FALSE) {
       
        switch($five) {
          case "Ubiquiti Networks": 
			$Device_Type = 'Ubiquiti AP'; 
			if (strpos($type, 'PowerBeam') !== FALSE) {
				$name = 'PBE';
			} 
			elseif (strpos($type, 'NanoBeam') !== FALSE) {
				$name = 'NBE';
			}
			elseif (strpos($type, 'NanoBridge') !== FALSE) {
				$name = 'NB';
			}
			elseif (strpos($type, 'NanoStation') !== FALSE) {
				$name = 'NS';
			}
			elseif (strpos($type, 'Rocket') !== FALSE) {
                                $name = 'R';
                        } 
			elseif (strpos($type, 'AirFiber') !== FALSE) {
				$name = 'AF';
			}
			else {
				$name = 'Not';
			}
			
			if (strpos($type, 'M5') !== FALSE) {
				$name = $name . 'M5 .' . $octet4;
			}
			elseif (strpos($type, 'M365') !== FALSE) {
				$name = $name . 'M365 .' . $octet4;
			}
			elseif (strpos($type, 'M2') !== FALSE) {
                                $name = $name . 'M2 .' . $octet4;
                        }
			elseif (strpos($type, '5x') !== FALSE) {
				$name = $name . '5x .' . $octet4;
			}
			elseif (strpos($type, '24') !== FALSE) {
                                $name = $name . '24 .' . $octet4;
                        }
			else {
				$name = $name . 'Found';
			}
		break;
        }
        switch($type) {
          case "PMP 450": 
		$Device_Type = 'Cambium PMP450 AP'; 
		if ($frequency > 2400 && $frequency < 2500) {
			$name = 'C2+ .' . $octet4;
		}
		if ($frequency > 900 && $frequency < 1000) {
		        $name = 'C9+ .' . $octet4;
		} 
          	break;
          case "ePMP 1000": 
			$Device_Type = 'Cambium ePMP 2.4GHz AP'; 
			$name = 'CE2 .' . $octet4;
          	break;
          case "PMP 100": 
                  if ($frequency > 1000) {
                        $Device_Type = 'Cambium PMP100 LOS AP';
                  } 
                  else {
	                $Device_Type = 'Cambium PMP100 NON-LOS AP';
			$name = 'C9 .' . $octet4;
                  }

		  if ($frequency > 2400 && $frequency < 2500) {
			$name = 'C2 .' . $octet4;
		  }
		  elseif ($frequency > 5100 && $frequency < 6000) {
			$name = 'C5 .' . $octet4; 
		  }
                  break;
        }
       $var2 .= $Site.' '.$name.','.$IP.','.$frequency.',,,,,,'."\r\n";
    }
    
    elseif (strpos($one, 'Backhaul') !== FALSE || $octet4 > 129 && $octet4 < 150) {
    
          $var1 .= $IP.','.$frequency."\r\n";
    
          switch($five) {
            case "Ubiquiti Networks": 
		$Device_Type = 'Ubiquiti PTP'; 
		if (strpos($type, 'PowerBeam') !== FALSE) {
                                $name = 'PBE';
                        }
                        elseif (strpos($type, 'NanoBeam') !== FALSE) {
                                $name = 'NBE';
                        }
                        elseif (strpos($type, 'NanoBridge') !== FALSE) {
                                $name = 'NB';
                        }
                        elseif (strpos($type, 'NanoStation') !== FALSE) {
                                $name = 'NS';
                        }
                        elseif (strpos($type, 'Rocket') !== FALSE) {
                                $name = 'R';
                        }
                        elseif (strpos($type, 'AirFiber') !== FALSE) {
                                $name = 'AF';
                        }
                        else {
                                $name = 'Not';
                        }

                        if (strpos($type, 'M5') !== FALSE) {
                                $name = $name . 'M5 .' . $octet4;
                        }
                        elseif (strpos($type, 'M365') !== FALSE) {
                                $name = $name . 'M365 .' . $octet4;
                        }
                        elseif (strpos($type, 'M2') !== FALSE) {
                                $name = $name . 'M2 .' . $octet4;
                        }
                        elseif (strpos($type, '5x') !== FALSE) {
                                $name = $name . '5x .' . $octet4;
                        }
                        elseif (strpos($type, '24') !== FALSE) {
                                $name = $name . '24 .' . $octet4;
                        }
                        else {
                                $name = $name . 'Found';
                        }
		break;
            case "Cambium Networks": $Device_Type = 'Cambium PTP'; 
	      switch($type) {
	          case "PTP 450":
	                if ($frequency > 2400 && $frequency < 2500) {
        	                $name = 'C2+ .' . $octet4;
                	}
	                elseif ($frequency > 900 && $frequency < 1000) {
        	                $name = 'C9+ .' . $octet4;
                	}
                  break;

        	  case "PTP 100 - BH20":
			if ($frequency < 1000) {
        	                $name = 'C9 .' . $octet4;
                	}
			elseif ($frequency > 2400 && $frequency < 2500) {
        	                $name = 'C2 .' . $octet4;
                	}
	                elseif ($frequency > 5100 && $frequency < 6000) {
        	                $name = 'C5 .' . $octet4;
                	}
                  break;
		  
		  case "PTP 230 - BH50":
                        if ($frequency < 1000) {
                                $name = 'C9 .' . $octet4;
                        }
                        elseif ($frequency > 2400 && $frequency < 2500) {
                                $name = 'C2 .' . $octet4;
                        }
                        elseif ($frequency > 5100 && $frequency < 6000) {
                                $name = 'C5 .' . $octet4;
                        }
                  break;
                  
                  default: 
                  		$name = $type;
		  break;			
  	      }
	    break;
	    
            case "SAF": $Device_Type = 'SAF PTP'; break;
            default: $Device_Type = 'Other'; break;
          }    
          $Device_Category = 'Backhaul';
    }
    elseif (strpos($five, 'Netonix') !== FALSE) { //for some reason everything is separate from Netonix in powercode
          $Device_Type = 'Netonix POE Switch'; 
	  $name = 'Netonix .' . $octet4; 
    }
    elseif (strpos($one, 'Router') !== FALSE) {
          if($five == "MikroTik RouterOS") {
                $Device_Type = 'Mikrotik Router';
		$name = 'RTR';
		if ($octet4 > 59 && $octet4 < 90) {
	 		$name = 'RELYRTR';
		}
          }
          else {
                $Device_Type = 'Other';
          }
    } 
    elseif (strpos($one, 'POE') !== FALSE) {
          
          $Device_Category = 'Sync Timing';
          
          if ($five == "PacketFlux") {
                $Device_Type = 'PacketFlux SiteMonitor'; 
		$name = 'SiteMonitor';
          } 
          elseif (strpos($type, "CMM") !== FALSE) {
                $Device_Type = 'Cambium CMM';
		$name = 'CMM';
          } 
          else {
                $Device_Type = 'Other';
          }
    }
    elseif (strpos($one, 'Subscriber Module') !== FALSE) {
          $Device_Category = 'CPE';
          switch($type) {
            case "PMP 450": $Device_Type = 'Relay Host SM - Cambium PMP450'; break;
            case "ePMP 1000": $Device_Type = 'Relay Host SM - Cambium ePMP'; break;
            case "PMP 100": $Device_Type = 'Relay Host SM - Cambium PMP100'; break;
          }
    }
    elseif (strpos($one, 'UPS') !== FALSE) {
          switch($five) {
            case "American Power Conversion": $Device_Type = 'APC UPS'; break;
            case "Duracom": $Device_Type = 'DuraComm RMCU UPS'; break;
            default: $Device_Type = 'Other';
          }
	  $name = 'UPS';
    }
    else {  
          $Device_Category = 'Other';
          $Device_Type = 'Other';
    }        
#    $Site_var .= $Site . "\r\n";    
    $var .= $Site.' '.$name.','.$mac.','.$IP.','.$Device_Category.','.$Device_Type.','.''.','.'ICMP and SNMP'.','.$row["SNMP_Community"].','.$row["SNMP_Version"].','.$Network_Site."\r\n";
    }
  }
#  file_put_contents('sites.csv', $Site_var);
  file_put_contents('exports/backhaul_export.csv', $var1);
  file_put_contents('exports/export.csv', $var);
  file_put_contents('exports/ap_export.csv', $var2);
  
?>