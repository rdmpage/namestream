<?php

// Fetch IndexFungorum records from API

require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');


//----------------------------------------------------------------------------------------
function process_index_fungorum_record($xml, $cache_dir)
{
	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$xpath->registerNamespace('dc',      'http://purl.org/dc/elements/1.1/');
	$xpath->registerNamespace('dcterms', 'http://purl.org/dc/terms/');
	$xpath->registerNamespace('tdwg_pc', 'http://rs.tdwg.org/ontology/voc/PublicationCitation#');
	$xpath->registerNamespace('tdwg_co', 'http://rs.tdwg.org/ontology/voc/Common#');
	$xpath->registerNamespace('tdwg_tn', 'http://rs.tdwg.org/ontology/voc/TaxonName#');
	$xpath->registerNamespace('rdfs',    'http://www.w3.org/2000/01/rdf-schema#');
	$xpath->registerNamespace('rdf',     'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
		
	$obj = new stdclass;
	
	// Identifier (must be position 1 because if basionymn present IF adds it as well
	$nodeCollection = $xpath->query ('//tdwg_tn:TaxonName[1]/@rdf:about');
	foreach ($nodeCollection as $node)
	{
		$obj->id = str_replace('urn:lsid:indexfungorum.org:names:', '', $node->firstChild->nodeValue);
	}

	// basionym
	$nodeCollection = $xpath->query ('//tdwg_tn:hasBasionym/@rdf:resource');
	foreach ($nodeCollection as $node)
	{
		$obj->basionym_id = str_replace('urn:lsid:indexfungorum.org:names:', '', $node->firstChild->nodeValue);
	}

	// Name
	$nodeCollection = $xpath->query ('//tdwg_tn:nameComplete');
	foreach ($nodeCollection as $node)
	{
		$obj->nameComplete = $node->firstChild->nodeValue;
	}

	$nodeCollection = $xpath->query ('//tdwg_tn:genusPart');
	foreach ($nodeCollection as $node)
	{
		$obj->genusPart = $node->firstChild->nodeValue;
	}
	$nodeCollection = $xpath->query ('//tdwg_tn:specificEpithet');
	foreach ($nodeCollection as $node)
	{
		$obj->specificEpithet = $node->firstChild->nodeValue;
	}
	$nodeCollection = $xpath->query ('//tdwg_tn:infraspecificEpithet');
	foreach ($nodeCollection as $node)
	{
		$obj->infraspecificEpithet = $node->firstChild->nodeValue;
	}
	$nodeCollection = $xpath->query ('//tdwg_tn:authorship');
	foreach ($nodeCollection as $node)
	{
		$obj->authorship = $node->firstChild->nodeValue;
	}
	$nodeCollection = $xpath->query ('//tdwg_tn:basionymAuthorship');
	foreach ($nodeCollection as $node)
	{
		$obj->basionymAuthorship = $node->firstChild->nodeValue;
	}
	$nodeCollection = $xpath->query ('//tdwg_tn:combinationAuthorship');
	foreach ($nodeCollection as $node)
	{
		$obj->combinationAuthorship = $node->firstChild->nodeValue;
	}

	$nodeCollection = $xpath->query ('//tdwg_tn:rankString');
	foreach ($nodeCollection as $node)
	{
		$obj->rankString = $node->firstChild->nodeValue;
	}
	$nodeCollection = $xpath->query ('//tdwg_tn:nomenclaturalCode/@rdf:resource');
	foreach ($nodeCollection as $node)
	{
		$obj->nomenclaturalCode = str_replace('http://rs.tdwg.org/ontology/voc/TaxonName#', '', $node->firstChild->nodeValue);
	}
	
	
	// publication
	$nodeCollection = $xpath->query ('//tdwg_co:publishedIn');
	foreach ($nodeCollection as $node)
	{
		$obj->publishedIn = $node->firstChild->nodeValue;
	}
	
	$nodeCollection = $xpath->query ('//tdwg_pc:PublicationCitation/dc:identifier');
	foreach ($nodeCollection as $node)
	{
		$obj->identifier = $node->firstChild->nodeValue;
	}
	$nodeCollection = $xpath->query ('//tdwg_pc:PublicationCitation/tdwg_pc:title');
	foreach ($nodeCollection as $node)
	{
		$obj->title = $node->firstChild->nodeValue;
	}
	$nodeCollection = $xpath->query ('//tdwg_pc:PublicationCitation/tdwg_pc:volume');
	foreach ($nodeCollection as $node)
	{
		$obj->volume = $node->firstChild->nodeValue;
	}
	$nodeCollection = $xpath->query ('//tdwg_pc:PublicationCitation/tdwg_pc:number');
	foreach ($nodeCollection as $node)
	{
		$obj->number = $node->firstChild->nodeValue;
	}	
	$nodeCollection = $xpath->query ('//tdwg_pc:PublicationCitation/tdwg_pc:pages');
	foreach ($nodeCollection as $node)
	{
		$obj->pages = $node->firstChild->nodeValue;
	}	
	$nodeCollection = $xpath->query ('//tdwg_pc:PublicationCitation/tdwg_pc:year');
	foreach ($nodeCollection as $node)
	{
		$obj->year = $node->firstChild->nodeValue;
	}
	
	// store raw XML
	file_put_contents($cache_dir . '/' . $obj->id . '.xml', $xml);	
	
	// store JSON
	file_put_contents($cache_dir . '/' . $obj->id . '.json', json_format(json_encode($obj)));
}

//----------------------------------------------------------------------------------------
// Get list of new ids
function process_index_fungorum($xml, $cache_dir)
{
	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$count = 0;

	$nodeCollection = $xpath->query ('//NewDataSet/IndexFungorum/FungusNameLSID');
	foreach ($nodeCollection as $node)
	{
		$id = $node->firstChild->nodeValue;
		
		echo ".";
		
		$url = 'http://www.indexfungorum.org/IXFWebService/Fungus.asmx/NameByKeyRDF?NameLsid=' . $id;
		
		$record_xml = get($url);
		if ($record_xml != '')
		{
			$count++;
			process_index_fungorum_record($record_xml, $cache_dir);
		}
	}

	return $count;
}


//----------------------------------------------------------------------------------------

// API URL
$url = 'http://www.indexfungorum.org/IXFWebService/Fungus.asmx/NewNames?rank=sp.';

// time accessed
$stamp = time();

// Data about feed
$data = null;

// Log
$log_filename = dirname(__FILE__) . '/access.log';
$log_handle = fopen($log_filename, 'a');
$log_string = date("Y-m-d H:i:s", $stamp);

$data_filename = dirname(__FILE__) . '/indexfungorum.json';
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
	$data->startDate = '20150101'; // default start 1 January 2015
}

// ensure folder for results exists, store results for same day in same folder
$cache_dir = dirname(__FILE__) . '/cache/' . date("Y-m-d", $stamp);

if (!file_exists($cache_dir))
{
	$oldumask = umask(0); 
	mkdir($cache_dir, 0777);
	umask($oldumask);
}


$url .= '&startDate=' . $data->startDate;
$xml = get($url);

$log_string .= "\t$stamp.xml";

if ($xml != '')
{
	// store raw API result (use timestamp as file name)
	$xml_filename = $cache_dir . '/' . $stamp . '.xml';
	file_put_contents($xml_filename, $xml);

	$count = process_index_fungorum($xml, $cache_dir);
	$log_string .= "\t$count";
}
else
{
	$log_string .= "\t-";
}

// Store last time accessed
$data->startDate = date("Ymd", $stamp);
file_put_contents($data_filename, json_format(json_encode($data)));

// Store log
fwrite($log_handle, $log_string . "\n");
fclose($log_handle);

?>
