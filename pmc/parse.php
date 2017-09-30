<?php

$counter = 0;

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
// Insert an annotation into text, delimited by <'tag'> and with optional attributes
// Based on http://stackoverflow.com/a/20571519
function insert_annotation_2($dom, &$node)
{ 
	$DEGREES_SYMBOL 		=  '[°|º]';
	$MINUTES_SYMBOL			= '(\'|’|\′)';
	$SECONDS_SYMBOL			= '("|\'\'|’’|”|\′\′)';
	
	$INTEGER				= '\d+';
	$FLOAT					= '\d+(\.\d+)?';
	
	$LATITUDE_DEGREES 		= '[0-9]{1,2}';
	$LONGITUDE_DEGREES 		= '[0-9]{1,3}';
	
	$LATITUDE_HEMISPHERE 	= '[N|S]';
	$LONGITUDE_HEMISPHERE 	= '[W|E]';
	
	// sanity check
	/*
	if (strpos($node->nodeValue, $annotation) === false)
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
	
	print_r($parts);
	print_r($annotations);


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
    
    echo $parts[$i] . "\n";
    echo $annotations[$i] . "\n";
    
    // add the part from the list as new text node
    $node->parentNode->insertBefore(
      $dom->createTextNode($parts[$i]), 
      $node
    );
  }
  
  
  //echo $dom->saveXML($parentNode);
  // remove the old text node
  $node->parentNode->removeChild($node);

}


//----------------------------------------------------------------------------------------
// Recursively traverse DOM and process tags
function dive($dom, $node)
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
	
	if ($node->nodeName == '#text')
	{
		//
		//insert_annotation($dom, $node, 'INDONESIA', 'country');
		
		
		//insert_annotation($dom, $node, 'VII. 27. 1977', 'date', array('iso' => '1927-07-27'));
		
		insert_annotation_2($dom, $node);
		
	}
}


$xml = '<p><italic>Paragoniastes uesci</italic> is the only member of the genus with the pronotum entirely covered with longitudinal ridges slightly diverging anteriorly in combination with long and well-marked elytral humeral striae.</p>';

$xml = '<p>Body (Figures <xref ref-type="fig" rid="fig1">1</xref>(c) and <xref ref-type="fig" rid="fig1">1</xref>(d)) 1.45&#x2013;1.50&#x2009;mm long. Head 1.2-1.3 times longer than wide (without eyes). Antennae (holotype) with scape 1.1-1.2 times longer than pronotal width; 2nd article as long as wide; 3rd 1.6-1.7 times longer than wide, 1.8-1.9 times longer than 2nd and 1.3-1.4 times longer than 4th; 4th somewhat longer than wide; 5th 1.6-1.7 times longer than wide and 1.2-1.3 times longer than 3rd. Pronotum slightly wider than long, areolate, except anterior quarter covered with longitudinal ridges slightly diverging anteriorly. Elytra smooth, irregularly covered with transverse carinulae. Elytral internal and external discal striae well-marked; humeral stria present on more that three quarters of elytral length. Presence of 13 setae arranged along internal discal striae; strial setae about as long as interval between them, and about as long as interstrial setae. Mesofemora bearing posteriorly at most obsolete subbasal tooth-like process.</p>';

//$xml = '<p>Holotype male (EUMJ): &#x201c;Ngaglik, Yogyakarta 7&#xb0;42&#x2032;28.34&#x2032;&#x2032;S 110&#xb0;24&#x2032;45.34&#x2032;&#x2032;E Java, INDONESIA 28. II. 2010 H. Yoshitomi leg.&#x201d;</p>';

$xml = '<p>3 Males and 3 females (EUMJ), &#x201c;(Indonesia) <b>Ciburum alt.</b> 1,600&#x2009;m Mt. Gede, Jawa Barat VII. 27. 1977 Shinji Nagai leg.&#x201d;; 1 male (EUMJ), ditto but &#x201c;20. VII. 1997.&#x201d;</p>';

$xml = '<fig id="fig3"><label>Figure 3</label><caption><p><italic>Hydrocyphon takizawai</italic> sp.n., holotype, male (a&#x2013;e) and paratype, female (f&#x2013;h). (a, f) Sternites V&#x2013;VII; (b) tergite VIII; (c) sternite IX; (d) tegmen; (e) penis; (g) ovipositor; (h) prehensor.</p></caption><graphic xlink:href="603875.fig.003" /></fig>';

$xml = '<p>BRAZIL: Bara&#x000fa;na (Gruta do Britador, 5&#x000b0;1&#x02032;26&#x02032;&#x02032;S 37&#x000b0;29&#x02032;50&#x02032;&#x02032;W); 11/VI/2010; D.M. Bento coll.; 2 males 2 females 2 juv (ISLA 1843); (Gruta do Pinga, 5&#x000b0;3&#x02032;8&#x02032;&#x02032; 37&#x000b0;32&#x02032;23&#x02032;&#x02032;W); 28/I/2010; D.M. Bento coll.; 1 male 1 female 1 juv. (ISLA 1835); (Gruta dos Cip&#x000f3;s, 5&#x000b0;2&#x02032;40&#x02032;&#x02032; 37&#x000b0;34&#x02032;35&#x02032;&#x02032;W); D.M. Bento coll. 1 male 1 female 2 juv. (ISLA 1850); (Gruta Furna Feia; 5&#x000b0;2&#x02032;12&#x02032;&#x02032;S 37&#x000b0;33&#x02032;37&#x02032;&#x02032;W); 29/I/2010; D.M. Bento coll.; 2 males 2 females 1 juv. (ISLA 1834); (Gruta do Lago; 5&#x000b0;2&#x02032;11&#x02032;&#x02032; 37&#x000b0;34&#x02032;15&#x02032;&#x02032;W; 26/I/2010; D.M. Bento coll.; 1 male 5 juv. (ISLA 1846); ditto; 30/VII/2010; D.M. Bento coll.; 1 male 1 female 2 juv. (ISLA 1840); (Gruta Esquecida, 5&#x000b0;2&#x02032;20&#x02032;&#x02032;S 37&#x000b0;33&#x02032;41&#x02032;&#x02032;W); D.M. Bento coll.; 1 male 2 females 1 juv. (ISLA 1826); same details, 12/VI/2010; D.M. Bento coll.; 1 male 1 female 3 juv. (ISLA 1838); Felipe Guerra (Gruta da Carrapateira, 5&#x000b0;33&#x02032;38&#x02032;&#x02032;S 37&#x000b0;39&#x02032;50&#x02032;&#x02032;W), 28.VII.2009, 4 males 2 females (UFMG 3900); (Gruta do Arapu&#x000e1;, 5&#x000b0;31&#x02032;48&#x02032;&#x02032;S 37&#x000b0;36&#x02032;58&#x02032;&#x02032;W); 3/VIII/2010; D.M. Bento coll.; 1 female (ISLA 1828); ditto, 7/I/2010; D.M. Bento coll.; 3 males 4 females (ISLA 1831); (Gruta Beira Rio, 5&#x000b0;33&#x02032;7&#x02032;&#x02032; 37&#x000b0;37&#x02032;43&#x02032;&#x02032;W); D.M. Bento coll.; 2 males 2 females 2 juv. (ISLA 1822); (Gruta do Crotes, 5&#x000b0;33&#x02032;39&#x02032;&#x02032;S 37&#x000b0;39&#x02032;32&#x02032;&#x02032;W); 19/I/2010; D.M. Bento coll.; 3 males 3 females 1 juv. (ISLA 1827); ditto; 4/VI/2010; 1 female 2 juv. (ISLA 1832); ditto; no date, 1 female (ISLA 1847); (Gruta do Ge&#x000ed;lson, 5&#x000b0;35&#x02019;53&#x0201d;S 37&#x000b0;41&#x02019;18&#x0201d;W), 16.VI.2008, R.L. Ferreira coll., 6 males 2 females 7juv. (UFMG 3901); (Gruta do Buraco Redondo, 5&#x000b0;34&#x02032;43&#x02032;&#x02032;S 37&#x000b0;39&#x02032;5&#x02032;&#x02032;W); D.M. Bento coll.; 1 male (ISLA 1825); (Gruta Lapa 1, 5&#x000b0;33&#x02032;42&#x02032;&#x02032;S 37&#x000b0;41&#x02032;42&#x02032;&#x02032;W); D.M. Bento coll.; 1 male (ISLA 1821); (Gruta da Rumana; 5&#x000b0;33&#x02032;54&#x02032;&#x02032;S 37&#x000b0;39&#x02032;7&#x02032;&#x02032;W); 10/I/2010; D.M. Bento coll.; 2 males 1 female (ISLA 1842); Governador Dix-sept Rosado (Gruta da Boniteza, 5&#x000b0;30&#x02032;51&#x02032;&#x02032;S 37&#x000b0;33&#x02032;22&#x02032;&#x02032;W); 2/II/2009; D.M. Bento coll.; 1 male 2 juv. (ISLA 1829); (Gruta Capoeira do Jo&#x000e3;o Carlos, 5&#x000b0;30&#x02032;57&#x02032;&#x02032;S 37&#x000b0;31&#x02032;42&#x02032;&#x02032;W); D.M. Bento coll.; 1 male 4 females (ISLA 1823); ditto; 3/6/2010; 2 females 4 juv. (ISLA 1845); (Gruta do Lajedo Grande, 5&#x000b0;27&#x02032;42&#x02032;&#x02032;S 37&#x000b0;33&#x02032;14&#x02032;&#x02032;W); D.M. Bento coll.; 1 females (ISLA 1820); (Gruta do Marimbondo Caboclo, 5&#x000b0;29&#x02032;44&#x02032;&#x02032;S 37&#x000b0;32&#x02032;42&#x02032;&#x02032;W); D.M. Bento coll.; 4 males 5 females 1 juv. (ISLA 1824); ditto; 20/VII/2010; D.M. Bento coll.; 3 males 2 females 4 juv. (ISLA 1844); Martins (Gruta Casa de Pedra, 6&#x000b0;4&#x02032;17&#x02032;&#x02032;S 37&#x000b0;52&#x02032;59&#x02032;&#x02032;W), VI.2008, R.L. Ferreira coll., 4 males 6 females 1juv. (UFMG 3902); Mossor&#x000f3; (Gruta do Trinta, 5&#x000b0;12&#x02032;44&#x02032;&#x02032;S 37&#x000b0;15&#x02032;51&#x02032;&#x02032;W); 10/VI/2010; D.M. Bento coll.; 1 female (ISLA 1830).</p>';

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;	


// http://stackoverflow.com/questions/6090667/php-domdocument-errors-warnings-on-html5-tags
libxml_use_internal_errors(true);
$dom->loadXML($xml);
libxml_clear_errors();

echo $xml;

$counter = 0;
foreach ($dom->documentElement->childNodes as $node) {
    dive($dom, $node);
}

// Annotated version
echo $dom->saveXML();


?>