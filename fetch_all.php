<?php



$source_dir = dirname(__FILE__) . '/sources';


$sources = scandir($source_dir);

foreach ($sources as $source)
{
	if (preg_match('/^[a-z]/', $source))
	{	
		echo $source . "\n";
		
		// process
		$command = 'php ' . $source_dir . '/' . $source . '/fetch.php';
		system($command);
	}
}

?>