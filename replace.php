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
 * @version 	0.3.0
 */

ini_set('memory_limit', '256M');

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
	// Source file handler
	$source = fopen($file_path, "r");

	// Source file size
	$file_size = filesize($file_path);

	// Creates or replace the output file
	file_put_contents($output_path,'');
	// Output file handler
	$output = fopen($output_path, 'a');

	// Prints a progress bar
	$bar = '';
	while(strlen($bar) < 25){
		$bar .= "_";
	}
	echo "\nProcessing $file_path (". human_filesize($file_size) .")\n$bar\n";
	ob_flush();

	// Stores the difference between searched and replaced strings lengths
	$len_diff = strlen($replace_string) - strlen($search_string);

	// Stores the count of replace operations
	$replace_count = 0;
	// Stores the count of serialization fixes
	$serialization_count = 0;

	// Progress feedback vars
	$progress = 0;
	$progress_feedback = 0;

	// Stores a maximum 10MB of data before flush into the output file
	$chunk = '';
	// Stores the chunk size to avoid massive strlen() calls
	$chunk_size = 0;

	while(!feof($source)) {
		$line = fgets($source);
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
		$chunk_size += strlen($line);
		$chunk .= $line;
		
		// Flushes chunk to the output file if it's bigger than 1/10 of file_size or max 10MB has been reached
		if($chunk_size > ($file_size / 10) || $chunk_size >= 10240){
			fwrite($output, $chunk);
			clearstatcache($output_path);
			$progress = ceil((filesize($output_path)/ $file_size) * 25);
			$chunk = '';
			$chunk_size = 0;
		}
		// Printing progress bar
		if($progress > $progress_feedback){
			$progress_feedback = $progress;
			echo "·";
			ob_flush();
		}
	}
	// check for unflushed data
	if($chunk_size > 0){
		fwrite($output, $chunk);
		$chunk = '';
		$chunk_size = 0;
	}
	fclose($output);
	fclose($source);
	
	echo "\n\nStrings Replaced: $replace_count\nSerialization fixes: $serialization_count\nCheck it out: $output_path\n\nCheers! :)\n";
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

	$output_path = $params[4] && $params[4] != $file_path
					? $params[4]
					: str_replace(".$file_type", "_output.$file_type", $file_path);

	if(strpos($output_path, $file_type) === false)
		die("Invalid output format.\n");

	$result = replace_file_strings($search_string, $replace_string, $file_path, $output_path);
}

init($argv);

