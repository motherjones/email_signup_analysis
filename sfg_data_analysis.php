<?php
$st_file = $argv[1];
$sfg_file = $argv[2];
$sfg_email_col = $argv[3];

//rename files to show they've been hashed
$st_file_hash = str_replace(".csv", "_hashed.csv", $st_file);
$sfg_file_hash = str_replace(".csv", "_hashed.csv", $sfg_file);

//concatenate command tot run hash.pl
$hash_st = "perl hash.pl -i " . $st_file . " -o " . "hashed/" . $st_file_hash;
$hash_sfg = "perl hash.pl -i " . $sfg_file . " -o " . "hashed/" . $sfg_file_hash;

//execute command to use hash.pl
echo "\nHashing emails\n";
exec("mkdir hashed");
exec($hash_st);
exec($hash_sfg);

//cut st email & codes column & sfg email column
echo "\nCutting email columns\n";
exec("mkdir cut_files");
$st_file_hash_cut = str_replace(".csv", "_cut.csv", $st_file_hash);
$sfg_file_hash_cut = str_replace(".csv", "_cut.csv", $sfg_file_hash);
$st_cut = "cut -f 2-3 hashed/" . $st_file_hash . " > cut_files/" . $st_file_hash_cut;
$sfg_cut = "cut -f " . $sfg_email_col . " hashed/" . $sfg_file_hash . " > cut_files/" . $sfg_file_hash_cut;
exec($st_cut);
exec($sfg_cut);

//remove rows with no source codes in ST file
echo "\nRemoving rows with no source codes in ST file\n";
$st_cut_cleansed = str_replace(".csv", "_cleansed.csv", $st_file_hash_cut);
$st_cut_clean = "awk -F'\t' '$2!=\"\"'" . " cut_files/" . $st_file_hash_cut . " > cut_files/" . $st_cut_cleansed;
print $st_cut_clean . "\n";
exec($st_cut_clean);

//remove duplicate emailds
echo "\nRemoving duplicate emails in both ST and SFG files\n";
exec("mkdir uniq_files");
$st_uniq = str_replace(".csv", "_uniq.csv", $st_cut_cleansed);
$sfg_uniq = str_replace(".csv", "_uniq.csv", $sfg_file_hash_cut);
$st_uniq_sort = "sort -u -t '\t' -k1 cut_files/" . $st_cut_cleansed . " > " . "uniq_files/" . $st_uniq;
$sfg_uniq_sort = "sort -u -t '\t' cut_files/" . $sfg_file_hash_cut . " > " . "uniq_files/" . $sfg_uniq;
exec($st_uniq_sort);
exec($sfg_uniq_sort);

//get source code matches from ST to SFG file
$st_file = "uniq_files/" . $st_uniq;
$sfg_file = "uniq_files/" . $sfg_uniq;

echo "\nFinding matches between ST and SFG files\n";
exec("mkdir final_files");
$complete_file = str_replace(".csv", "_complete.csv", $sfg_uniq);
$get_matches = "awk -F'\t' 'FILENAME==\"" . $sfg_file . "\"{A[$1]=$1} FILENAME==\"" . $st_file . "\"{if(A[$1]==$1){print}}' $sfg_file $st_file > final_files/" . $complete_file;
exec($get_matches);

//counting occurrences of ST codes in SFG file
echo "\nFinalizing files and counting source codes\n";
$complete_file_name = "final_files/" . $complete_file;
$read_file = fopen($complete_file_name, "r");
$rename_final = str_replace(".csv", "_final.csv", $complete_file);
$final_file_name = "final_files/" . $rename_final;
$final_file = fopen($final_file_name, "w+");
$counter_arr = array();
while($row = fgetcsv($read_file, 0, "\t")) {
	if(array_key_exists($row[1], $counter_arr)) {
		$counter_arr[$row[1]]++;
	}
	else {
		$counter_arr[$row[1]] = 1;
	}
}

foreach($counter_arr as $line => $value) {
	$temp_arr[0] = $line;
	$temp_arr[1] = $value;
	fputcsv($final_file, $temp_arr);
}
?>
