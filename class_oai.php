<?php

/**
 * @file class_oai.php Simple OAI harvesting
 *
 */

require_once (dirname(__FILE__) . '/lib.php');

//-------------------------------------------------------------------------------------------------
/**
 * @class OaiHarvester
 *
 * @brief Encapsulate harvesting an OAI repository
 *
 */
class OaiHarvester
{
	var $repository_url;
	var $repository_set;
	var $resumption_token;
	var $base_url;
	var $metadata_prefix;
	
	var $basedir;
	var $data;
	var $data_filename;
	var $cache_dir;
	
	var $count;
	
	//----------------------------------------------------------------------------------------------
	function __construct($url, $basedir, $metadata_prefix = 'oai_dc', $set = '')
	{
		$this->repository_url 	= $url;
		$this->repository_set	= $set;
		$this->metadata_prefix 	= $metadata_prefix;
		$this->xml				= '';
		$this->basedir 			= $basedir;
		$this->data 			= null;
		$this->data_filename 	= $basedir . '/oai.json';
		
		$this->count = 0;
		
		// ensure folder for results exists, store results for same day in same folder
		$this->cache_dir = $this->basedir . '/cache/' . date("Y-m-d");

		if (!file_exists($this->cache_dir))
		{
			$oldumask = umask(0); 
			mkdir($this->cache_dir, 0777);
			umask($oldumask);
		}
		
		
		$this->GetDateLastAccessed();
	}
	
	//----------------------------------------------------------------------------------------------
	function GetDateLastAccessed ()
	{	
		if (file_exists($this->data_filename))
		{
			$json = file_get_contents($this->data_filename);
			if ($json != '')
			{
				$this->data = json_decode($json);
			}
		}

		if (!$this->data)
		{
			$this->data = new stdclass;
			
			// create an arbitrary date 
			
			// one week ago
			$this->data->lastAccessed = date('Y-m-d\TH:i:s\Z', time() - (7 * 24 * 60 * 60));
		}
		
		// for debugging
		//$this->data->lastAccessed = date('Y-m-d\TH:i:s\Z', time() - (7 * 24 * 60 * 60));
	}
	
	//----------------------------------------------------------------------------------------------
	function SetDateLastAccessed ()
	{
		$this->data->lastAccessed = date('Y-m-d\TH:i:s\Z');

		file_put_contents($this->data_filename, json_format(json_encode($this->data)));		
	}	

	//----------------------------------------------------------------------------------------------
	function Harvest ()
	{
		$this->base_url = $this->repository_url;
		$this->base_url .= '?verb=ListRecords';
		
		$this->resumption_token = '';
		
		do {
			$url = $this->base_url;
		
			if ($this->resumption_token == '')
			{
				$url .= '&metadataPrefix=' . $this->metadata_prefix;
				if ($this->repository_set != '')
				{
					$url .= '&set=' . $this->repository_set;
				}
			}
			else
			{
				$url .= '&resumptionToken=' . $this->resumption_token;
			}
			
			// If we've previously accessed this repository then limit search to newly 
			// added/modified records
			if (isset($this->data->lastAccessed))
			{
				$url .= '&from=' .  $this->data->lastAccessed;
			}
		
			//echo $url . "\n";
			
			// make call, harvest XML, clean, store in database
			
			$this->xml = get($url);
			
			if ($this->xml != '')
			{			
				// Post process and store records...
				$this->Process();
			}
			
		} while ($this->resumption_token != '');
		
		$this->SetDateLastAccessed();		
	}
	
	//----------------------------------------------------------------------------------------------
	function ProcessOneRecord(&$obj)
	{
		//print_r($obj);
	}	
	
	//----------------------------------------------------------------------------------------------
	function Process()
	{
		//echo $this->xml;
		
		$dom= new DOMDocument;
		$dom->loadXML($this->xml);
		$xpath = new DOMXPath($dom);

		$xpath->registerNamespace('oai',  'http://www.openarchives.org/OAI/2.0/');
		$xpath->registerNamespace('oai_dc',  'http://www.openarchives.org/OAI/2.0/oai_dc/');
		$xpath->registerNamespace('dc',      'http://purl.org/dc/elements/1.1/');
		
		$records = $xpath->query ('//oai:record');
		foreach ($records as $record)
		{	
			$obj = new stdclass;
			
			$nodeCollection = $xpath->query ('oai:header/oai:identifier', $record);
			foreach ($nodeCollection as $node)
			{
				$obj->guid = $node->firstChild->nodeValue;
				
				if (preg_match('/(?<id>\d+)$/', $obj->guid, $m))
				{
					$obj->id = $m['id'];
				}
			}
			$nodeCollection = $xpath->query ('oai:header/oai:datestamp', $record);
			foreach ($nodeCollection as $node)
			{
				$obj->pubDate = $node->firstChild->nodeValue;
				// Standardise
				$obj->cleanedDate = date("Y-m-d H:i:s", strtotime($obj->pubDate));
			}
							
			$nodeCollection = $xpath->query ('oai:metadata/oai_dc:dc/dc:title', $record);
			foreach ($nodeCollection as $node)
			{
				$obj->title = $node->firstChild->nodeValue;
			}
			
			$nodeCollection = $xpath->query ('oai:metadata/oai_dc:dc/dc:description', $record);
			foreach ($nodeCollection as $node)
			{
				$obj->description = $node->firstChild->nodeValue;
			}			
			
			$nodeCollection = $xpath->query ('oai:metadata/oai_dc:dc/dc:identifier', $record);
			foreach ($nodeCollection as $node)
			{
				if (preg_match('/^10\./', $node->firstChild->nodeValue))
				{	
					$obj->doi = $node->firstChild->nodeValue;
				}	
			}
			$this->ProcessOneRecord($obj);
			
			if (isset($obj->id))
			{
				// save JSON
				file_put_contents($this->cache_dir . '/' . $obj->id . '.json', json_format(json_encode($obj)));
				
				// save XML
				file_put_contents($this->cache_dir . '/' . $obj->id . '.xml', $dom->saveXML($record));
				exit();
				
				
			}
			
			$this->count++;
		}
		
		// Resumption token (if any)
		$this->resumption_token = '';
		$nodeCollection = $xpath->query ('//oai:resumptionToken');
		foreach ($nodeCollection as $node)
		{
			$this->resumption_token = $node->firstChild->nodeValue . "\n";
		}
	}
	

	
}

// test

//http://biotaxa.org/Zootaxa/oai?verb=ListRecords&resumptionToken=eecea39c1ec32d438f9014bb4fdb9221

//$oai = new OaiHarvester('http://biotaxa.org/Zootaxa/oai', dirname(__FILE__), 'oai_dc', 'Zootaxa');


//$oai->Harvest();


?>