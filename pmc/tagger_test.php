<?php

$graph = '';
$stack = array();

$depth = 0;

// Simple, automated annotation of JATS-XML document

//----------------------------------------------------------------------------------------
// Insert one or more annotations into the text below a node
// Based on http://stackoverflow.com/a/20571519
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

/*
$root = null;

function new_node($name, $text = '')
{
	$node = new stdclass;
	$node->type = 'node';
	
	$node->name = $label;
	
	if ($text != '')
	{
		$node->type = 'text';
		$node->text = $text;
	}
	
	return $node;
}
*/



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
// Recursively traverse DOM and process tags
function dive($dom, $node, $callback_func = '')
{	
	global $graph;
	global $stack;
	global $depth;
	
	$depth++;

	for ($i = 0;$i<$depth;$i++) { echo ' '; };
	echo "-- Dive in entering --\n";
	for ($i = 0;$i<$depth;$i++) { echo ' '; };	
	echo "--" . $node->nodeName . "\n";
	for ($i = 0;$i<$depth;$i++) { echo ' '; };	
	echo "-- \"" . $node->nodeValue . "\" \n";
	for ($i = 0;$i<$depth;$i++) { echo ' '; };
	echo "--[" . $node->parentNode->nodeName . "]\n";
	
	
	
	$n = count($stack);
	
	$graph .= '"' . $stack[$n-1] . '" --> "' . $node->nodeName . '";' . "\n";
	
	/*
	
	// Visit any children of this node
	if ($node->hasChildNodes())
	{
		if (1)
		{
			$to_visit = array();
			foreach ($node->childNodes as $children) {
				$to_visit[] = $children;
			}
			
			foreach ($to_visit as $children) {
				$stack[] = $children->nodeName;
				dive($dom, $children);
			}
		}
		else
		{
		foreach ($node->childNodes as $node) {
			dive($dom, $node, 'specimen');
		}
		
		
		}
		
	}
	*/
	
	// Annotate
	if ($callback_func != '')
	{
		$callback_func($dom, $node);
	}
	
	for ($i = 0;$i<$depth;$i++) { echo ' '; };	
	echo "-- Dive in leaving --\n";
	for ($i = 0;$i<$depth;$i++) { echo ' '; };
	echo "--" . $node->nodeName . "\n";
	
	$depth--;
	
	array_pop($stack);

}

//----------------------------------------------------------------------------------------
$xml = '<p>This is a test MVZ 1000 5°1′26′′S 37°29′50′′W and MNV 2000</p>';

$xml = '<p>This is a test MVZ 1000 <a>5&#xB0;1&#x2032;26&#x2032;&#x2032;S 37&#xB0;29&#x2032;50&#x2032;&#x2032;W</a> and MNV 2000</p>';

$xml = '<p><title>Type material</title>Holotype. Male from BRAZIL: Rio Grande do Norte: Felipe Guerra (Gruta da Carrapateira, <named-content content-type="dwc:verbatimCoordinates">5&#xB0;33&#x2032;38&#x2032;&#x2032;S 37&#xB0;39&#x2032;50&#x2032;&#x2032;W</named-content>), 28.VII.2009, M.P.A. Oliveira coll. (UFMG 3897). Paratypes. Female from BRAZIL: Rio Grande do Norte: Felipe Guerra (Gruta do Ge&#xED;lson, <named-content content-type="dwc:verbatimCoordinates">5&#xB0;35&#x2032;53&#x2032;&#x2032;S 37&#xB0;41&#x2032;18&#x2032;&#x2032;W</named-content>), 16.VI.2008, M.P.A. Oliveira coll. (UFMG 3898); male and female, same data as the holotype (UFMG 3899); male and female, ditto (IBSP 45); male and female from BRAZIL: Rio Grande do Norte: Bara&#xFA;na (Gruta dos Cip&#xF3;s, 5&#xB0;2&#x2032;40&#x2032;&#x2032; 37&#xB0;34&#x2032;35&#x2032;&#x2032;W); D.M. Bento coll. (ISLA 1837).</p>';

$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
	
$graph = "digraph G{\n";

// specimen
{
	$nodeCollection = $xpath->query ('//p/text()');
	
	$stack[] = 'root';

	foreach ($nodeCollection as $node)
	{
			dive($dom, $node, 'specimen');
		
	}
}


$graph .= "}\n";

//echo $graph;


echo $dom->saveXML();


?>