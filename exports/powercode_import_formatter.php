<?php
	$var = file_get_contents('exports/powercode_import.csv');
	$var1 = explode("\n", $var);
	foreach($var1 as $var2) {
	
		$var3 = explode(",", $var2);
		$NetIDConverter[trim($var3[1])] = $var3[0];

	}
	

	

?>