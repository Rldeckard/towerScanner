<?php
	$file = file_get_contents('exports/networkNames.csv');
	$names = explode("|", $file);
	$implode = implode(" ", $names); 
  	$explode = explode("\n", $implode);
  	foreach($explode as $boom) {
  	        $string = explode(' ' , $boom);
  	        if (isset($string[1]) !== FALSE && isset($string[2]) !== FALSE) {
  	        if ($string[2] > 0 == FALSE) {
  	        $diff[$string[1]] = trim($boom);
  	        }
  	        else {
  	        $diff[$string[2]] = trim($boom);
  	        }
                  }	        
  	}
?>