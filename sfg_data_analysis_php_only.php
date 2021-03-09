<?php
//increase PHP memory limit to read in large files
ini_set('memory_limit','2048M');

//changed to read file names from prompt instead of in the same line as the command.
$st_file = trim(readline("Enter the name of the SailThru file: "));
$sfg_file = trim(readline("Enter the name of the other file: "));

//Turn csv files input into arrays
$st_csv = array_map("str_getcsv", file($st_file,FILE_SKIP_EMPTY_LINES));
$sfg_csv = array_map("str_getcsv", file($sfg_file,FILE_SKIP_EMPTY_LINES));

//generate key value pairs
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
//end csv to arrays

//declare the two arrays, one for SailThru file and one for SFG file
$source_arr = array();
$amount_arr = array();

//grab array keys to find the columns for operations
$st_keys = array_keys($st_csv[0]);
$sfg_keys = array_keys($sfg_csv[0]);

//variables to hold column headers as keys in associative arrays
$st_email = "";
$sfg_email = "";
$st_source = "";
$sfg_trans_amnt = "";

//failure check vars
$found_email = false;
$found_source = false;

//loop through array keys to find the columns for email and source from SailThru file
foreach($st_keys as $key => $value) {
	if(stripos(trim($value),"email") !== false && (stripos($value2, "opt") === false && stripos($value2, "preference") === false)) {
		echo "Found email column in SailThru file: " . $value . PHP_EOL;
		$st_email = $value;
		$found_email = true;
	}
	if(stripos($value, "source") !== false) {
		echo "Found source column in SailThru file: " . $value . PHP_EOL;
		$st_source = $value;
		$found_source = true;
	}
}

//if either or both field is not found, generate error and exit script
if($found_email === false || $found_source === false) {
	if($found_email === false) {
		echo "Email column not found in SailThru file. Script exiting." . PHP_EOL;
	}
	if($found_source === false) {
		echo "Source column not found in SailThru file. Script exiting." . PHP_EOL;
	}
	die();
}

//failure check vars
$found_things = false;
$found_trans_amount = false;

//loop through array keys to find columns for email and transaction amount in SFG file
foreach($sfg_keys as $key2 => $value2) {
	if(stripos($value2, "email") !== false && (stripos($value2, "opt") === false && stripos($value2, "preference") === false)) {
		echo "Found email column in SFG file: " . $value2 . PHP_EOL;
		$sfg_email = $value2;
		$found_things = true;
	}
	if(stripos($value2, "amount") !== false && stripos($value2, "transaction") !== false) {
		echo "Found transaction column in SFG file: " . $value2 . PHP_EOL;
		$sfg_trans_amnt = $value2;
		$found_trans_amount = true;
	}
}

//if either or both field is not found, generate an error and exit script
if($found_things === false || $found_trans_amount === false) {
	if($found_things === false) {
		echo "Email column not found in SFG file. Script exiting." . PHP_EOL;
	}
	if($found_trans_amount === false) {
		echo "Transaction amount column not found in SFG file. Script exiting." . PHP_EOL;
	}
	die();
}

//create array with emails and source association
for($i = 0; $i < count($st_csv);$i++) {
	$email_st = "";
	$email_st = strtolower(trim($st_csv[$i][$st_email]));
	$source_arr[$email_st] = $st_csv[$i][$st_source];
}

//create array with emails and transaction amount association
for($x = 0; $x < count($sfg_csv); $x++) {
	$email_rr = "";
	$email_rr = strtolower(trim($sfg_csv[$x][$sfg_email]));
	if(array_key_exists(trim($sfg_csv[$x][$sfg_email]),$amount_arr)) {
		$amount_arr[$email_rr] += $sfg_csv[$x][$sfg_trans_amnt];
	}
	else {
		$amount_arr[$email_rr] = $sfg_csv[$x][$sfg_trans_amnt];
	}
}

//define array to hold the results
$results_arr = array();

//loop through the email/source associative array and look for email matches in the email/transaction amount array
foreach($source_arr as $email => $source) {
	if(array_key_exists($source, $results_arr)) {
		$results_arr[$source]["Total Source"]++;
	}
	else {
		$results_arr[$source]["Total Source"] = 1;
	}

	//assigning source count and the total amount for the source
	if(array_key_exists($email, $amount_arr) && ($amount_arr[$email] !== "" && $amount_arr[$email] > 0)) {
		$results_arr[$source]["Source Count"]++;
		$results_arr[$source]["Total Amount"] += $amount_arr[$email];
	}
	else {
		continue;
	}
}

//clear some memory before doing the final operation
$source_arr = null;
$amount_arr = null;

//create and open the final csv file for output
$final_file = fopen("final_file.csv", "w+");

//column headers
$headers[0] = "Source";
$headers[1] = "Totals by Source in SailThru";
$headers[2] = "Total Matches in SFG";
$headers[3] = "Total Dollar Amount";
$headers[4] = "Average Dollar Amount";

//write the column headers to the csv file
fputcsv($final_file, $headers);

echo "working.";

//loop through the results array and write the information to the file
foreach($results_arr as $source => $values) {
	$items[0] = $source;
	$items[1] = $values["Total Source"];
	if($values["Source Count"] != "" && $values["Source Count"] > 0) {
		$items[2] = $values["Source Count"];
		$items[3] = $values["Total Amount"];
		//calculate average amount
		if($values["Source Count"] > 0) {
			$items[4] = round($values["Total Amount"] / $values["Source Count"], 2);
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
