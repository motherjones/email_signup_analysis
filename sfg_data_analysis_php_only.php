<?php
ini_set('memory_limit','1256M');

$st_file = $argv[1];
$sfg_file = $argv[2];

$st_csv = array_map("str_getcsv", file($st_file,FILE_SKIP_EMPTY_LINES));
$sfg_csv = array_map("str_getcsv", file($sfg_file,FILE_SKIP_EMPTY_LINES));

$st_keys = array_shift($st_csv);
$sfg_keys = array_shift($sfg_csv);

foreach ($st_csv as $r=>$row) {
	$r = trim(strtolower($r));
  $st_csv[$r] = array_combine($st_keys, $row);
}

foreach ($sfg_csv as $l=>$line) {
	$l = trim(strtolower($l));
  $sfg_csv[$l] = array_combine($sfg_keys, $line);
}

$source_arr = array();
$amount_arr = array();



for($i = 0; $i < count($st_csv);$i++) {
	$email_st = "";
	$email_st = strtolower(trim($st_csv[$i]["Email"]));
	$source_arr[$email_st] = $st_csv[$i]["source"];
}

for($x = 0; $x < count($sfg_csv); $x++) {
	$email_rr = "";
	$email_rr = strtolower(trim($sfg_csv[$x]["Email Address"]));
	//echo $email_rr . " this is to test spacing" . PHP_EOL;
	if(array_key_exists(trim($sfg_csv[$x]["Email Address"]),$amount_arr)) {
		$amount_arr[$email_rr] += $sfg_csv[$x]["Transaction Amount"];
	}
	else {
		$amount_arr[$email_rr] = $sfg_csv[$x]["Transaction Amount"];
	}
}

$results_arr = array();

foreach($source_arr as $email => $source) {
	if(array_key_exists($source, $results_arr)) {
		$results_arr[$source]["Total Source"]++;
	}
	else {
		$results_arr[$source]["Total Source"] = 1;
	}


	if(array_key_exists($email, $amount_arr) && ($amount_arr[$email] !== "" && $amount_arr[$email] > 0)) {
		$results_arr[$source]["Source Count"]++;
		$results_arr[$source]["Total Amount"] += $amount_arr[$email];
	}
	else {
		continue;
	}
}

$final_file = fopen("final_file.csv", "w+");

$headers[0] = "Source";
$headers[1] = "Totals by Source in SailThru";
$headers[2] = "Total Matches in SFG";
$headers[3] = "Total Dollar Amount";
$headers[4] = "Average Dollar Amount";

fputcsv($final_file, $headers);

echo "working.";

foreach($results_arr as $source => $values) {
	$items[0] = $source;
	$items[1] = $values["Total Source"];
	if($values["Source Count"] != "" && $values["Source Count"] > 0) {
		$items[2] = $values["Source Count"];
		$items[3] = $values["Total Amount"];
		if($values["Source Count"] > 0) {
			$items[4] = $values["Total Amount"] / $values["Source Count"];
		}
	}
	else {
		$items[2] = 0;
		$items[3] = "N/A";
		$items[4] = "N/A";
	}

	fputcsv($final_file, $items);
	echo ".";
}

echo PHP_EOL . "Done" . PHP_EOL;

fclose($final_file);
?>
