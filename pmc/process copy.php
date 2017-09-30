<?php

// Simple, automated annotation of JATS-XML document


//----------------------------------------------------------------------------------------
function insert_annotations(
	$dom, 
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
// Insert an annotation into text, delimited by <'tag'> and with optional attributes
// Based on http://stackoverflow.com/a/20571519
function insert_annotation($dom, &$node, $annotation, $tag, $attributes=array())
{ 
	// sanity check
	
	if (strpos($node->nodeValue, $annotation) === false)
	{
		return;
	}


  // explode the text at the string
  $parts = explode($annotation, $node->nodeValue);
  
  echo "--o--\n";
  $o = print_r($parts, true);
  echo $o;
  //exit();
  
  // add a new text node with the first part
  $node->parentNode->insertBefore(
    $dom->createTextNode(
      // fetch and remove the first part from the list
      array_shift($parts)
    ),
    $node
  );
  // if here are more then one part
  foreach ($parts as $part) {
    // add a tag before it
    
    $annotation_tag = $dom->createElement($tag);
    
    foreach ($attributes as $k => $v)
    {
    	$annotation_tag->setAttribute($k, $v);
    }
    
    $node->parentNode->insertBefore($annotation_tag, $node);
    
    // with the string that we used to split the text
    $annotation_tag->appendChild($dom->createTextNode($annotation));
    
    // add the part from the list as new text node
    $node->parentNode->insertBefore(
      $dom->createTextNode($part), 
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
			insert_annotation($dom, $node, $m['date'], 'term');
		}
	}

}


//----------------------------------------------------------------------------------------
function lat_long_tagger_1($dom, $node)
{
	if ($node->nodeName == '#text')
	{
		// <24°38′S; 54°07′E>
		if (preg_match('/\<(?<term>[0-9]{1,2}°[0-9]{1,2}\′[S|N][,|;]\s+[0-9]{1,3}°[0-9]{1,2}\′[W|E])\>/', $node->nodeValue, $m))
		{
			insert_annotation($dom, $node, $m['term'], 
				'named-content', 
				array("content-type" => "dwc:verbatimCoordinates")
				);
		}
	}

}

//----------------------------------------------------------------------------------------
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
	
	// sanity check(s)
	// if this node is already marked up then exit
	$tagged = array('named-content');
	if (in_array($node->nodeName, $tagged))
	{
		return;
	}
		
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
	
	
	/*
	//print_r($parts);
	//print_r($annotations);


  // add a new text node with the first part
  $node->parentNode->insertBefore(
    $dom->createTextNode(
      // fetch and remove the first part from the list
      array_shift($parts)
    ),
    $node
  );
  
  // if here are more then one part
  $n = count($parts);
  for ($i = 0; $i < $n; $i++)
  {
    // add a tag before it
    
    $annotation_tag = $dom->createElement('named-content');
	$annotation_tag->setAttribute('content-type', 'dwc:verbatimCoordinates');
    
    $node->parentNode->insertBefore($annotation_tag, $node);
    
    // with the string that we used to split the text
    $annotation_tag->appendChild($dom->createTextNode($annotations[$i]));
    
    //echo $parts[$i] . "\n";
    //echo $annotations[$i] . "\n";
    
    // add the part from the list as new text node
    $node->parentNode->insertBefore(
      $dom->createTextNode($parts[$i]), 
      $node
    );
  }
  
  
  // remove the old text node
  $node->parentNode->removeChild($node);
 */
}


//----------------------------------------------------------------------------------------
function holotype_tagger($dom, $node)
{
	echo "Parent = " . $node->parentNode->nodeName . "\n";

	if ($node->nodeName == '#text')
	{
		if (preg_match('/(?<term>microphthalmus)/', $node->nodeValue, $m))
		{
			insert_annotation($dom, $node, $m['term'], 'term');
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
function dive($dom, $node, $callback_func = '')
{	
	echo "--" . $node->nodeName . "\n";
	echo "--" . $node->nodeValue . "\n";
	echo "--------\n\n";
	
	// Visit any children of this node
	if ($node->hasChildNodes())
	{
		foreach ($node->childNodes as $children) {
			dive($dom, $children);
		}
	}
	
	// Annotate
	if ($callback_func != '')
	{
		$callback_func($dom, $node);
	}

}

//----------------------------------------------------------------------------------------

$filename = 'hindawi/psyche/2012/603875.xml';
//$filename = 'hindawi/psyche/2012/561352.xml';

//$filename = '8a/41/PLoS_One_2013_May_22_8(5)_e63616/pone.0063616.nxml';



$xml = file_get_contents($filename);

$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
	
	
// text processing
$nodeCollection = $xpath->query ('//p');


/*
foreach ($nodeCollection as $node)
{
	foreach ($node->childNodes as $node) {
    	dive($dom, $node, 'lat_long_tagger_1', 'named-content', array('content-type' => 'dwc:verbatimCoordinates'));
    }
}
*/


foreach ($nodeCollection as $node)
{
	foreach ($node->childNodes as $node) {
    	dive($dom, $node, 'geotagger');
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