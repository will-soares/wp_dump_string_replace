<?php
/**
 * Name: wp_dump_string_replace
 * Description: PHP script for strings replacement in Wordpress dumps. Useful to move production database to DEV and STG environments.
 * Version: 0.2.0
 * Author: Will Soares
 * Author URI: https://github.com/will-soares
 * Code Repo: https://github.com/will-soares/wp_dump_string_replace
 * Requires PHP: 5.6.20
 * License: MIT License
 *
 * @package 	wp_dump_string_replace
 * @author		Will Soares
 * @copyright   Copyright (C) 2021 Will Soares
 * @license		MIT License
 * @link		https://github.com/will-soares
 * @version 	0.2.1
 */

ini_set('memory_limit', '1024M');

ob_start();

function get_serialization($txt){
	// Searching for string declaration
	foreach(str_split(',;[({') as $c){
		$sep =  $c .'s:';
		$i = strrpos($txt, $sep);
		if($i !== false)
			return (object) [
					'sep' => $sep,
					'len' => intval(explode(':', substr($txt, $i+3) )[0])
				];
	}

	return false;

}

function human_filesize($bytes, $decimals = 2) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

function replace_file_strings($search_string, $replace_string, $file_path, $output_path){
	echo "\nReading $file_path (". human_filesize(filesize($file_path)) .")\n";
	ob_flush();
	// the content of file_path
	$content = file_get_contents($file_path);

	// An array containing each content line
	$lines = explode("\n",$content);
	$content = '';

	// Stores the difference between searched and replaced strings lengths
	$len_diff = strlen($replace_string) - strlen($search_string);

	// Creates or replacing the output file
	file_put_contents($output_path,'');

	// Some feedback would be welcome
	$replace_count = 0;
	$serialization_count = 0;
	$processed_lines = 0;
	$printed = 0;
	$total_lines = count($lines);
	$bar = '';

	while(strlen($bar) < 100)
		$bar .= '_';

	echo "\nProcessing $total_lines lines...\n$bar\n";

	foreach($lines as $line){
		$n = substr_count($line, $search_string);
		for($i = 0; $i < $n; $i++){
			$len = strpos($line, $search_string);
			$new_line = substr($line, 0, $len);
			
			$s = get_serialization($new_line);

			if($s !== false){
				$new_line = str_replace($s->sep . $s->len .':', $s->sep .  strval($s->len + $len_diff) .':', $new_line);
				$serialization_count++;
			}
			$new_line .= $replace_string . substr($line, $len + strlen($search_string));
			$line = $new_line;
			unset($new_line);
			$replace_count++;
		}
		$processed_lines++;

		$percent = floor($processed_lines / $total_lines * 100);

		if($printed != $percent){
			$printed = $percent;
			echo '*';
			ob_flush();
		}
		$content .= "$line\n";
	}
	file_put_contents($output_path, $content);
	echo "\nLines Processed: $processed_lines\nStrings Replaced: $replace_count\nSerialization fixes: $serialization_count\nCheck it out: $output_path\n\nCheers! :)\n";
}

function init($params){
	if(count($params) < 4)
		die("Invalid paramenters.\nPlease use: $params[0] search_string replace_string source_path [... output_path]\n");

	// Parsing and validating parameters
	$search_string = $params[1];
	$replace_string = $params[2];
	$file_path = $params[3];
	$file_type = substr($file_path, strrpos($file_path, '.')+1);

	if(stripos('txt sql', $file_type) === false)
		die("Invalid file type: ". strtoupper($file_type) ."\n");

	$output_path = $params[4] ? $params[4] : str_replace(".$file_type", "_output.$file_type", $file_path);

	if(strpos($output_path, $file_type) === false)
		die("Invalid output format.\n");

	$result = replace_file_strings($search_string, $replace_string, $file_path, $output_path);
}

init($argv);

