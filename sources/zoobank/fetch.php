<?php

// Fetch ZooBank records from RSS feed

require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');

//----------------------------------------------------------------------------------------
// RSS 2.0
function process_zoobank_rss($rss, $cache_dir)
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
			
			if (preg_match('/http:\/\/zoobank.org\/urn:lsid:zoobank.org:(?<type>\w+):/', $obj->link, $m))
			{
				$obj->type = $m['type'];
			}			
			
			$obj->guid = preg_replace('/http:\/\/zoobank.org\/urn:lsid:zoobank.org:\w+:/', '', strtolower($obj->link));
			
			$url = '';
			switch ($obj->type)
			{
				case 'act':
					$url = 'http://zoobank.org/NomenclaturalActs.json/';
					break;

				case 'author':
					$url = 'http://zoobank.org/Authors.json/';
					break;

				case 'pub':
					$url = 'http://zoobank.org/References.json/';
					break;
					
				default:
					break;
			}
			if ($url != '')
			{
				$url .= $obj->guid;
				$json = get($url);
				if ($json != '')
				{
					$data = json_decode($json);
					if (is_array($data))
					{
						$obj->payload = $data[0];
					}
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
		file_put_contents($cache_dir . '/' . $obj->guid . '.json', json_format(json_encode($obj)));
		//print_r($obj);
		
		$count++;
		
		// Give server a break every 10 items
		echo ".";
		if (($count++ % 10) == 0)
		{
			$rand = rand(2000000, 6000000);
			echo '...sleeping for ' . round(($rand / 1000000),2) . ' seconds' . "\n";
			usleep($rand);
		}
	}
	
	return $count;
}	


//----------------------------------------------------------------------------------------

// feed URL
$url = 'http://zoobank.org/rss/rss.xml';

// time accessed
$stamp = time();

// Data about feed
$data = null;

// Log
$log_filename = dirname(__FILE__) . '/access.log';
$log_handle = fopen($log_filename, 'a');
$log_string = date("Y-m-d H:i:s", $stamp);

$data_filename = dirname(__FILE__) . '/zoobank.json';
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

		$count = process_zoobank_rss($rss, $cache_dir);
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
