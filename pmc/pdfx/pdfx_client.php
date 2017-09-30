<?php
   # Simple PHP client for PDFX (http://pdfx.cs.man.ac.uk)
   # Author: Alex Constantin (aconstantin@cs.man.ac.uk)

   # LOCAL FILE
	$file_path = '/path/to/my.pdf';
	$size = filesize ($file_path);
   $save_path = $file_path;
	
   # REMOTE FILE   	
#   $file_path = 'http://pdfx.cs.man.ac.uk/example.pdf';
#   $file_header = array_change_key_case(get_headers($file_path, TRUE));
#   $size = $file_header['content-length'];	
#   $dir = getcwd()."/articles/xml/"; # make sure this directory exists
#   $save_path = $dir.basename($file_path);

	$url="http://pdfx.cs.man.ac.uk";
   $pdf = fopen($file_path, 'r');
	$header = array('Content-Type: application/pdf', "Content-length: " . $size);
		
	$ch=curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_INFILE, $pdf);
	curl_setopt($ch, CURLOPT_INFILESIZE, $size);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	
	$fp = fopen($save_path . 'x.xml', "w");
	curl_setopt($ch, CURLOPT_FILE, $fp);
	
	if (! $res = curl_exec($ch))
		echo "Error: ".curl_error($ch);
	else {
		echo "Success";
	}
	curl_close($ch);
?>