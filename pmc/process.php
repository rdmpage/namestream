<?php

// Simple, automated annotation of JATS-XML document

// http://stackoverflow.com/questions/5033955/xpath-select-text-node

//----------------------------------------------------------------------------------------
// Insert one or more annotations into the text node $node
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
function specimen_tagger($dom, &$node)
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
function double_quote_tagger($dom, &$node)
{ 
		
	$text = $node->nodeValue;
	
	$matched = false;
	
	if (preg_match_all("/
		“(.*[0-9]{4}.*)”
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


/*
//----------------------------------------------------------------------------------------
function term_tagger($dom, $node)
{

	if ($node->nodeName == '#text')
	{
		if (preg_match('/(?<term>microphthalmus)/', $node->nodeValue, $m))
		{
			insert_annotations($dom, $node, $m['term'], 'term');
		}
	}

}
*/


//----------------------------------------------------------------------------------------

$filename = 'hindawi/psyche/2012/603875.xml';
//$filename = 'hindawi/psyche/2012/561352.xml';

//$filename = '8a/41/PLoS_One_2013_May_22_8(5)_e63616/pone.0063616.nxml';

// Dolphin
$filename = 'c4/60/PLoS_One_2011_Sep_14_6(9)_e24047/pone.0024047.nxml';

//$filename = 'ceon.xml';


// The order in which text is tagged will matter a lot, need to got from more 
// inclusive tags to narrower tags
// need to think about how not to override existing tags (if any)


$xml = file_get_contents($filename);

$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
	


// experiments
// For Hindawi article find things which look like occurrence records
{
	$nodeCollection = $xpath->query ('//p/text()');

	foreach ($nodeCollection as $node)
	{
		double_quote_tagger($dom, $node);
	}
}


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




// geotagging
{
	$nodeCollection = $xpath->query ('//p/text()');

	foreach ($nodeCollection as $node)
	{
		geotagger($dom, $node);
	}
}



// specimen
{
	$nodeCollection = $xpath->query ('//p/text()');

	foreach ($nodeCollection as $node)
	{
		specimen_tagger($dom, $node);
	}
}



 
 file_put_contents('output.xml', $dom->saveXML());


?>