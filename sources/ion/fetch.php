<?php

// Fetch ION records from RSS feeds

require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/class_lsid.php');

//----------------------------------------------------------------------------------------
// ATOM
function process_ion_rss($rss, $cache_dir)
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
		$nodeCollection = $xpath->query ('guid', $item);
		foreach ($nodeCollection as $node)
		{
			$count++;
			
			// LSID
			$obj->guid = $node->firstChild->nodeValue;
					
			// Integer id	
			$obj->id = preg_replace('/urn:lsid:organismnames.com:name:/', '', $obj->guid);

			// Resolve LSID
			$xml = ResolveLSID($obj->guid);
			if ($xml != '')
			{
				// Store
				file_put_contents($cache_dir . '/' . $obj->id . '.xml', $xml);
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
		file_put_contents($cache_dir . '/' . $obj->id . '.json', json_format(json_encode($obj)));
	}
	
	return $count;
}	


//----------------------------------------------------------------------------------------

// feed URLs
$urls = array(
'http://www.organismnames.com/RSS/Acanthocephala.xml',
'http://www.organismnames.com/RSS/Acritarcha.xml',
'http://www.organismnames.com/RSS/Animalia.xml',
'http://www.organismnames.com/RSS/Annelida.xml',
'http://www.organismnames.com/RSS/Apicomplexa.xml',
'http://www.organismnames.com/RSS/Archaeocyatha.xml',
'http://www.organismnames.com/RSS/Arthropoda.xml',
'http://www.organismnames.com/RSS/Ascetospora.xml',
'http://www.organismnames.com/RSS/Brachiopoda.xml',
'http://www.organismnames.com/RSS/Bryozoa.xml',
'http://www.organismnames.com/RSS/Chaetognatha.xml',
'http://www.organismnames.com/RSS/Chitinozoa.xml',
'http://www.organismnames.com/RSS/Chordata.xml',
'http://www.organismnames.com/RSS/Ciliophora.xml',
'http://www.organismnames.com/RSS/Cnidaria.xml',
'http://www.organismnames.com/RSS/Conodonta.xml',
'http://www.organismnames.com/RSS/Conulariida.xml',
'http://www.organismnames.com/RSS/Ctenophora.xml',
'http://www.organismnames.com/RSS/Cycliophora.xml',
'http://www.organismnames.com/RSS/Echinodermata.xml',
'http://www.organismnames.com/RSS/Echiura.xml',
'http://www.organismnames.com/RSS/Entoprocta.xml',
'http://www.organismnames.com/RSS/Gastrotricha.xml',
'http://www.organismnames.com/RSS/Gnathostomulida.xml',
'http://www.organismnames.com/RSS/Graptolithina.xml',
'http://www.organismnames.com/RSS/Hemichordata.xml',
'http://www.organismnames.com/RSS/Hemimastigophora.xml',
'http://www.organismnames.com/RSS/Kinorhyncha.xml',
'http://www.organismnames.com/RSS/Labyrinthomorpha.xml',
'http://www.organismnames.com/RSS/Loricifera.xml',
'http://www.organismnames.com/RSS/Mesozoa.xml',
'http://www.organismnames.com/RSS/Micrognathozoa.xml',
'http://www.organismnames.com/RSS/Microspora.xml',
'http://www.organismnames.com/RSS/Mollusca.xml',
'http://www.organismnames.com/RSS/Myxozoa.xml',
'http://www.organismnames.com/RSS/Nematoda.xml',
'http://www.organismnames.com/RSS/Nematomorpha.xml',
'http://www.organismnames.com/RSS/Nemertinea.xml',
'http://www.organismnames.com/RSS/Onychophora.xml',
'http://www.organismnames.com/RSS/Perkinsozoa.xml',
'http://www.organismnames.com/RSS/Petalonamae.xml',
'http://www.organismnames.com/RSS/Phoronida.xml',
'http://www.organismnames.com/RSS/Placididea.xml',
'http://www.organismnames.com/RSS/Placozoa.xml',
'http://www.organismnames.com/RSS/Platyhelminthes.xml',
'http://www.organismnames.com/RSS/Porifera.xml',
'http://www.organismnames.com/RSS/Priapulida.xml',
'http://www.organismnames.com/RSS/Protozoa.xml',
'http://www.organismnames.com/RSS/Rotifera.xml',
'http://www.organismnames.com/RSS/Sarcomastigophora.xml',
'http://www.organismnames.com/RSS/Sipuncula.xml',
'http://www.organismnames.com/RSS/Tardigrada.xml',
'http://www.organismnames.com/RSS/Xenusia.xml'
);

// test
//$urls = array('http://www.organismnames.com/RSS/Chordata.xml');

// time accessed
$stamp = time();

// Log
$log_filename = dirname(__FILE__) . '/access.log';


foreach ($urls as $url)
{
	$feedname = $url;
	$feedname = str_replace('http://www.organismnames.com/RSS/', '', $feedname);
	$feedname = str_replace('.xml', '', $feedname);
	
	$log_handle = fopen($log_filename, 'a');
	$log_string = date("Y-m-d H:i:s", $stamp);
	
	// Data about this feed
	$data = null;

	$data_filename = dirname(__FILE__) . '/' . $feedname . '.json';
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
		echo $feedname . " changed\n";
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
	
		$log_string .= "\t$feedname.xml";

		if ($rss != '')
		{
			// store raw RSS
			$rss_filename = $cache_dir . '/' . $feedname . '.xml';
			file_put_contents($rss_filename, $rss);

			// process RSS and extract LSIDs
			$count = process_ion_rss($rss, $cache_dir);
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
}


?>
