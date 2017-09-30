<?php

require_once (dirname(__FILE__) . '/lib.php');

//--------------------------------------------------------------------------------------------------
// Use content negotian and citeproc, may fail with some DOIs
// e.g. [14/12/2012 12:52] curl --proxy wwwcache.gla.ac.uk:8080 -D - -L -H   "Accept: application/citeproc+json;q=1.0" "http://dx.doi.org/10.1080/03946975.2000.10531130" 
function get_doi_metadata($doi)
{
	$reference = null;
	
	// CrossRef-only DOIs
	$url = 'http://data.crossref.org/' . $doi;
	
	// DOIs
	$url = 'http://dx.doi.org/' . $doi;
	$json = get($url, "", "application/citeproc+json;q=1.0");

	if ($json == '')
	{
		return $reference;
	}
	
	$reference = json_decode($json);
	
	return $reference;
}

?>