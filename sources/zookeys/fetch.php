<?php

// Zookeys RSS feed

require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/crossref.php');

//----------------------------------------------------------------------------------------
// RSS 0.91
function process_rss($rss, $cache_dir)
{
	$count = 0;
	
	$dom= new DOMDocument;
	$dom->loadXML($rss);
	$xpath = new DOMXPath($dom);
	
	$itemCollection = $xpath->query ('//item');
	foreach ($itemCollection as $item)
	{
		$obj = new stdclass;
		
		$nodeCollection = $xpath->query ('title', $item);
		foreach ($nodeCollection as $node)
		{
			$obj->title = $node->firstChild->nodeValue;
		}
		
		$nodeCollection = $xpath->query ('link', $item);
		foreach ($nodeCollection as $node)
		{
			$obj->link = $node->firstChild->nodeValue;
			
			if (preg_match('/\?id=(?<id>\d+)$/', $obj->link, $m))
			{
				$obj->id = $m['id'];
			}
		}
		
		$nodeCollection = $xpath->query ('description', $item);
		foreach ($nodeCollection as $node)
		{
			$obj->description = $node->firstChild->nodeValue;
			
			// clean
			$obj->description = preg_replace('/\t/', '', $obj->description);
			$obj->description = preg_replace('/\n/', '', $obj->description);
			
			// get DOI
			if (preg_match('/<p>DOI: (?<doi>.*)<\/p>/Uu', $obj->description, $m))
			{
				$obj->doi = $m['doi'];
				
				// grab citeproc JSON
				$reference = get_doi_metadata($obj->doi);
				if ($reference)
				{
					$obj->payload = $reference;
				}
			}		
		}
		$nodeCollection = $xpath->query ('pubDate', $item);
		foreach ($nodeCollection as $node)
		{
			$obj->pubDate = $node->firstChild->nodeValue;
			// Standardise
			$obj->cleanedDate = date("Y-m-d H:i:s", strtotime($obj->pubDate));
		}
		
		// Store
		if (isset($obj->id))
		{
			file_put_contents($cache_dir . '/' . $obj->id . '.json', json_format(json_encode($obj)));
		}
		//print_r($obj);
		
		$count++;
	}
	
	return $count;
}	


//----------------------------------------------------------------------------------------

// feed URL
$url = 'http://zookeys.pensoft.net/rss.php';

// time accessed
$stamp = time();

// Data about feed
$data = null;

// Log
$log_filename = dirname(__FILE__) . '/access.log';
$log_handle = fopen($log_filename, 'a');
$log_string = date("Y-m-d H:i:s", $stamp);

$data_filename = dirname(__FILE__) . '/zookeys.json';
if (file_exists($data_filename))
{
	$json = file_get_contents($data_filename);
	if ($json != '')
	{
		$data = json_decode($json);
	}
}

if (!$data)
{
	$data = new stdclass;
}

if (has_source_changed($url, $data))
{
	echo "Changed\n";
	file_put_contents($data_filename, json_format(json_encode($data)));

	// ensure folder for results exists, store results for same day in same folder
	$cache_dir = dirname(__FILE__) . '/cache/' . date("Y-m-d", $stamp);

	if (!file_exists($cache_dir))
	{
		$oldumask = umask(0); 
		mkdir($cache_dir, 0777);
		umask($oldumask);
	}

	$rss = get($url);
	
	$log_string .= "\t$stamp.xml";

	if ($rss != '')
	{
		// store raw RSS (use timestamp as file name in case > 1 file a day)
		$rss_filename = $cache_dir . '/' . $stamp . '.xml';
		file_put_contents($rss_filename, $rss);

		$count = process_rss($rss, $cache_dir);
		$log_string .= "\t$count";
	}
	else
	{
		$log_string .= "\t-";
	}

}
else
{
	echo "Not changed\n";
	$log_string .= "\t[Not changed]";
	
}

fwrite($log_handle, $log_string . "\n");
fclose($log_handle);

?>
