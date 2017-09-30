<?php

// Simple, automated annotation of JATS-XML document

//----------------------------------------------------------------------------------------
// Insert one or more annotations into the text below a node
// Based on http://stackoverflow.com/a/20571519
function insert_annotations(
	&$dom, 
	&$node, 					// root node 
	$spans_outside_tags, 		// array of strings that will be outside the tags
	$spans_inside_tags, 		// text that will be enclosed by the tags
	$tag_name, 					// name of the tag to use
	$tag_attributes = array() 	// array of attributes to be attached to the tags
	)
{
	//print_r($tag_attributes );exit();

  // add a new text node with the first part
  $node->parentNode->insertBefore(
    $dom->createTextNode(
      // fetch and remove the first part from the list
      array_shift($spans_outside_tags)
    ),
    $node
  );
  
  // Remaining parts of the text
  $n = count($spans_outside_tags);
  for ($i = 0; $i < $n; $i++)
  {
    // add a tag before
    
    $annotation_tag = $dom->createElement($tag_name);
    
    if (isset($tag_attributes[$i]))
    {
    	foreach ($tag_attributes[$i] as $k => $v)
    	{
			$annotation_tag->setAttribute($k, $v);
		}
	}
    $node->parentNode->insertBefore($annotation_tag, $node);
    
    // text within tags
    $annotation_tag->appendChild($dom->createTextNode($spans_inside_tags[$i]));
        
    // add the part from the list as new text node
    $node->parentNode->insertBefore(
      $dom->createTextNode($spans_outside_tags[$i]), 
      $node
    );
  }
  
  
  // remove the old text node
  $node->parentNode->removeChild($node);

}



//----------------------------------------------------------------------------------------
function date_tagger_1($dom, $node)
{
	if ($node->nodeName == '#text')
	{
		// 26. XI. 1974
		if (preg_match('/\b(?<date>\d+\.\s*[IVX]{1,3}\.\s*[0-9]{4})/i', $node->nodeValue, $m))
		{
			insert_annotations($dom, $node, $m['date'], 'term');
		}
	}

}


//----------------------------------------------------------------------------------------
// tag all lat/lon pairs in a node's text
function geotagger($dom, &$node)
{ 
	$DEGREES_SYMBOL 		=  '[°|º|o]';
	$MINUTES_SYMBOL			= '(\'|’|\′)';
	$SECONDS_SYMBOL			= '("|\'\'|’’|”|\′\′)';
	
	$INTEGER				= '\d+';
	$FLOAT					= '\d+(\.\d+)?';
	
	$LATITUDE_DEGREES 		= '[0-9]{1,2}';
	$LONGITUDE_DEGREES 		= '[0-9]{1,3}';
	
	$LATITUDE_HEMISPHERE 	= '[N|S]';
	$LONGITUDE_HEMISPHERE 	= '[W|E]';
	
	/*
	// sanity check(s)
	// if this node is already marked up then exit
	$tagged = array('named-content');
	if (in_array($node->nodeName, $tagged))
	{
		return;
	}
	*/
		
	$text = $node->nodeValue;
	
	$matched = false;
	
	// 5°33′38′′S 37°39′50′′W
	if (preg_match_all("/
		(?<latitude_degrees>$LATITUDE_DEGREES)
		$DEGREES_SYMBOL
		\s*
		(?<latitude_minutes>$FLOAT)
		$MINUTES_SYMBOL?
		\s*
		(
		(?<latitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?
		\s*
		(?<latitude_hemisphere>$LATITUDE_HEMISPHERE)
		,?
		(\s+-)?
		;?
		\s*
		(?<longitude_degrees>$LONGITUDE_DEGREES)
		$DEGREES_SYMBOL
		\s*
		(?<longitude_minutes>$FLOAT)
		$MINUTES_SYMBOL?
		\s*
		(
		(?<longitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?
		\s*
		(?<longitude_hemisphere>$LONGITUDE_HEMISPHERE)
		
	/xu", $text, $matches))
	{
		print_r($matches[0]);
		$matched = true;
		//exit();
	}
	
	// santiy check
	if (!$matched)
	{
		return;
	}
	
	
	// arrays 
	$text = $node->nodeValue;
	$parts = array();
	$annotations = array();
	
	$annotations_attributes = array();
	
		$last_pos = 0;
		
		foreach ($matches[0] as $match)
		{
			$annotations[] = $match;
			
			$annotations_attributes[] = array('content-type' => 'dwc:verbatimCoordinates');
			
						
			$start = mb_strpos($text, $match, $last_pos);
			$end = $start + mb_strlen($match);
			
			$parts[] = substr($text, $last_pos, ($start - $last_pos));
			
			// update position so we don't find this point again
			
			$last_pos = $end;
		}
	$parts[] = substr($text, $last_pos, (mb_strlen($text) - $last_pos));
	
	insert_annotations($dom, $node, $parts, $annotations, 'named-content', $annotations_attributes);
	

}

//----------------------------------------------------------------------------------------
// tag all lat/lon pairs in a node's text
// N20°11′/E104°01′
function geotagger_2($dom, &$node)
{ 
	$DEGREES_SYMBOL 		=  '[°|º|o]';
	$MINUTES_SYMBOL			= '(\'|’|\′)';
	$SECONDS_SYMBOL			= '("|\'\'|’’|”|\′\′)';
	
	$INTEGER				= '\d+';
	$FLOAT					= '\d+(\.\d+)?';
	
	$LATITUDE_DEGREES 		= '[0-9]{1,2}';
	$LONGITUDE_DEGREES 		= '[0-9]{1,3}';
	
	$LATITUDE_HEMISPHERE 	= '[N|S]';
	$LONGITUDE_HEMISPHERE 	= '[W|E]';
	
	// sanity check(s)
	// if this node is already marked up then exit
	$tagged = array('named-content');
	if (in_array($node->nodeName, $tagged))
	{
		return;
	}
		
	$text = $node->nodeValue;
	
	$matched = false;
	
	// N20°11′/E104°01′
	if (preg_match_all("/
		(?<latitude_hemisphere>$LATITUDE_HEMISPHERE)
		\s*
		(?<latitude_degrees>$LATITUDE_DEGREES)
		$DEGREES_SYMBOL
		(?<latitude_minutes>$FLOAT)
		$MINUTES_SYMBOL
		\/
		(?<longitude_hemisphere>$LONGITUDE_HEMISPHERE)
		\s*
		(?<longitude_degrees>$LONGITUDE_DEGREES)
		$DEGREES_SYMBOL
		(?<longitude_minutes>$FLOAT)
		$MINUTES_SYMBOL
	/xu", $text, $matches))
	{
		print_r($matches[0]);
		$matched = true;
		//exit();
	}
	
	// santiy check
	if (!$matched)
	{
		return;
	}
	
	
	// arrays 
	$text = $node->nodeValue;
	$parts = array();
	$annotations = array();
	
	$annotations_attributes = array();
	
		$last_pos = 0;
		
		foreach ($matches[0] as $match)
		{
			$annotations[] = $match;
			
			$annotations_attributes[] = array('content-type' => 'dwc:verbatimCoordinates');
			
						
			$start = mb_strpos($text, $match, $last_pos);
			$end = $start + mb_strlen($match);
			
			$parts[] = substr($text, $last_pos, ($start - $last_pos));
			
			// update position so we don't find this point again
			
			$last_pos = $end;
		}
	$parts[] = substr($text, $last_pos, (mb_strlen($text) - $last_pos));
	
	insert_annotations($dom, $node, $parts, $annotations, 'named-content', $annotations_attributes);
	

}

//----------------------------------------------------------------------------------------

function specimen($dom, &$node)
{ 
		
	$text = $node->nodeValue;
	
	$matched = false;
	
	if (preg_match_all("/
		[A-Z]{2,5}
		\s*
		[A-Z]?
		[0-9]{3,}
	/xu", $text, $matches))
	{
		print_r($matches[0]);
		$matched = true;
		//exit();
	}
	
	// santiy check
	if (!$matched)
	{
		return;
	}
	
	
	// arrays 
	$text = $node->nodeValue;
	$parts = array();
	$annotations = array();
	
	$annotations_attributes = array();
	
		$last_pos = 0;
		
		foreach ($matches[0] as $match)
		{
			$annotations[] = $match;
			
						
			$start = mb_strpos($text, $match, $last_pos);
			$end = $start + mb_strlen($match);
			
			$parts[] = substr($text, $last_pos, ($start - $last_pos));
			
			// update position so we don't find this point again
			
			$last_pos = $end;
		}
	$parts[] = substr($text, $last_pos, (mb_strlen($text) - $last_pos));
	
	insert_annotations($dom, $node, $parts, $annotations, 'named-content', $annotations_attributes);
	

}

//----------------------------------------------------------------------------------------
// tag ""
function x($dom, &$node)
{ 
		
	$text = $node->nodeValue;
	
	$matched = false;
	
	if (preg_match_all("/
		“(.*)”
	/xUu", $text, $matches))
	{
		print_r($matches[0]);
		$matched = true;
		//exit();
	}
	
	// santiy check
	if (!$matched)
	{
		return;
	}
	
	
	// arrays 
	$text = $node->nodeValue;
	$parts = array();
	$annotations = array();
	
	$annotations_attributes = array();
	
		$last_pos = 0;
		
		foreach ($matches[0] as $match)
		{
			$annotations[] = $match;
			
						
			$start = mb_strpos($text, $match, $last_pos);
			$end = $start + mb_strlen($match);
			
			$parts[] = substr($text, $last_pos, ($start - $last_pos));
			
			// update position so we don't find this point again
			
			$last_pos = $end;
		}
	$parts[] = substr($text, $last_pos, (mb_strlen($text) - $last_pos));
	
	insert_annotations($dom, $node, $parts, $annotations, 'named-content', $annotations_attributes);
	

}


//----------------------------------------------------------------------------------------
function holotype_tagger($dom, $node)
{
	echo "Parent = " . $node->parentNode->nodeName . "\n";

	if ($node->nodeName == '#text')
	{
		if (preg_match('/(?<term>microphthalmus)/', $node->nodeValue, $m))
		{
			insert_annotations($dom, $node, $m['term'], 'term');
		}
	}

}

/*
//----------------------------------------------------------------------------------------
function process_node_text($dom, $node)
{
	echo "hi\n";
	if ($node->nodeName == '#text')
	{
		$term = 'checking';
		
		echo "x";
	
		if (preg_match('/' . $term . '/ui', $node->nodeValue))
		{
			insert_annotation($dom, $node, $term, 'term');
			//insert_annotation($dom, $node, 'VII. 27. 1977', 'date', array('iso' => '1927-07-27'));
		}
		
	}

}
*/

//----------------------------------------------------------------------------------------
// Recursively traverse DOM and process tags
function dive(&$dom, &$node, $callback_func = '')
{	
	echo "-- Dive in entering --\n";
	echo "--" . $node->nodeName . "\n";
	echo "--" . $node->nodeValue . "\n";
	
	
	// Visit any children of this node
	if ($node->hasChildNodes())
	{
		$to_visit = array();
		foreach ($node->childNodes as $children) {
			$to_visit[] = $children;
		}
			
		foreach ($to_visit as $children) {
			dive($dom, $children);
		}
	}
	
	// Annotate
	if ($callback_func != '')
	{
		$callback_func($dom, $node);
	}
	
	echo "-- Dive in leaving --\n";

}

//----------------------------------------------------------------------------------------

$filename = 'hindawi/psyche/2012/603875.xml';
//$filename = 'hindawi/psyche/2012/561352.xml';

$filename = '8a/41/PLoS_One_2013_May_22_8(5)_e63616/pone.0063616.nxml';

//$filename = 'ceon.xml';


// The order in which text is tagged will matter a lot, need to got from more 
// inclusive tags to narrower tags
// need to think about how not to override existing tags (if any)


$xml = file_get_contents($filename);

/*
$xml = '<p>This is a test MVZ 1000 5°1′26′′S 37°29′50′′W and MNV 2000</p>';

$xml = '<p>This is a test MVZ 1000 <a>5&#xB0;1&#x2032;26&#x2032;&#x2032;S 37&#xB0;29&#x2032;50&#x2032;&#x2032;W</a> and MNV 2000</p>';
*/

$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
	

/*
// experiments
// For Hindawi article find things which look like occurrence records
{
	$nodeCollection = $xpath->query ('//p');

	foreach ($nodeCollection as $node)
	{
		foreach ($node->childNodes as $node) {
			dive($dom, $node, 'x');
		}
	}
}
*/

/*
// geotagging, in this example search within already tagged bits
{
	$nodeCollection = $xpath->query ('//named-content');

	foreach ($nodeCollection as $node)
	{
		foreach ($node->childNodes as $node) {
			dive($dom, $node, 'geotagger');
		}
	}
}
*/



/*
// geotagging
{
	$nodeCollection = $xpath->query ('//p');

	foreach ($nodeCollection as $node)
	{
		foreach ($node->childNodes as $node) {
			dive($dom, $node, 'geotagger');
		}
	}
}
*/

/*
// specimen
{
	$nodeCollection = $xpath->query ('//p');

	foreach ($nodeCollection as $node)
	{
		dive($dom, $node, 'specimen');
	}
}
*/


// geotagging
{
	$nodeCollection = $xpath->query ('//p');

	foreach ($nodeCollection as $node)
	{
			dive($dom, $node, 'geotagger');
	}
}


$xml = $dom->saveXML();
$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);



// specimen
{
	$nodeCollection = $xpath->query ('//p');

	foreach ($nodeCollection as $node)
	{
		dive($dom, $node, 'specimen');
	}
}





/*
foreach ($nodeCollection as $node)
{
	foreach ($node->childNodes as $node) {
    	dive($dom, $node, 'holotype_tagger');
    }
}

foreach ($nodeCollection as $node)
{
	foreach ($node->childNodes as $node) {
		dive($dom, $node, 'date_tagger_1');
    }
}

// citation cleaning

$nodeCollection = $xpath->query ('//mi');


foreach ($nodeCollection as $node)
{
	foreach ($node->childNodes as $node) {
    	dive($dom, $node, 'lat_long_tagger_1', 'named-content', array('content-type' => 'dwc:verbatimCoordinates'));
    }
}
*/


 //echo $dom->saveXML();
 
 file_put_contents('output.xml', $dom->saveXML());


?>