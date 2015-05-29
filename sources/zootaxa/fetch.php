<?php

// Zootaxa OAI 

require_once(dirname(dirname(dirname(__FILE__))) . '/class_oai.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/crossref.php');

class ZootaxaHarvester extends OaiHarvester
{

	//------------------------------------------------------------------------------------
	function ProcessOneRecord(&$obj)
	{
		if (isset($obj->doi))
		{
			// grab citeproc JSON
			$reference = get_doi_metadata($obj->doi);
			if ($reference)
			{
				$obj->payload = $reference;
			}
		}
	}	

}


//----------------------------------------------------------------------------------------

// time accessed
$stamp = time();

// Log
$log_filename = dirname(__FILE__) . '/zootaxa.log';
$log_handle = fopen($log_filename, 'a');
$log_string = date("Y-m-d H:i:s", $stamp);

$oai = new ZootaxaHarvester('http://biotaxa.org/Zootaxa/oai', dirname(__FILE__), 'oai_dc', 'Zootaxa');
$oai->Harvest();

$count = $oai->count;
$log_string .= "\t$count";

fwrite($log_handle, $log_string . "\n");
fclose($log_handle);

?>
