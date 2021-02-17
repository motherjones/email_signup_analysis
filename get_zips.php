<?php
//increase memory limit assigned to php just in case
ini_set('memory_limit','1256M');

//changed to read file names from prompt instead of in the same line as the command.
$st_file = readline("Enter the name of the SailThru file: ");
$sfg_file = readline("Enter the name of the other file: ");

//This section just turns the csv into arrays with keys being the csv headers
$st_csv = array_map("str_getcsv", file(trim($st_file),FILE_SKIP_EMPTY_LINES));
$sfg_csv = array_map("str_getcsv", file(trim($sfg_file),FILE_SKIP_EMPTY_LINES));

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
//end code for turning csv files into key and value associative arrays

//two arrays to hold values
$st_array = array();
$sfg_array = array();

//iterates through the arrays made above to just get the associative array
for($st = 0;$st < count($st_csv); $st++) {
	$st_array[$st_csv[$st]["Email"]] = $st_csv[$st]["us_zips"];
}

for($sfg = 0;$sfg < count($sfg_csv); $sfg++) {
	$sfg_array[$sfg_csv[$sfg]["E-mail Address"]] = $sfg_csv[$sfg]["ZIP/Postal Code"];
}
//end conversion of array to proper associative array

//main array to hold email to zip association
$zips_array = array();

//loop through SailThru's array to find matching emails in SFG files, then put them into the zips array
foreach($st_array as $email => $zip) {
	if(array_key_exists($email, $sfg_array) && $sfg_array[$email] !== null) {
		$zips_array[$email] = $sfg_array[$email];
	}
}
//create final file
$zips_file = fopen("zips_updated.csv", "w+");

//headers for the final csv file
$headers[0] = "Email";
$headers[1] = "zip_code";

//write headers to final csv file
fputcsv($zips_file, $headers);

//loop through the zips array and write email/zip pairings
foreach($zips_array as $email => $zip) {
	$temp_arr[0] = $email;
	$temp_arr[1] = $zip;
	//output to the file
	fputcsv($zips_file, $temp_arr);
}
//close file
fclose($zips_file);
?>
