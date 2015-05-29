<?php


$source_dir = dirname(__FILE__) . '/sources';


$sources = scandir($source_dir);

foreach ($sources as $source)
{
	if (preg_match('/^[a-z]/', $source))
	{	
		echo $source . "\n";
		
		// process
		
		$cache_dir = $source_dir . '/' . $source . '/cache';
		$caches = scandir($cache_dir);
		
		foreach ($caches as $cache)
		{	
			echo "$cache\n";
			if (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $cache))
			{
				$data_dir = $cache_dir . '/' . $cache;
				$data = scandir($data_dir);
				
				foreach ($data as $datum)
				{	
					$filename = $data_dir . '/' . $datum;
					$ext = pathinfo($filename, PATHINFO_EXTENSION);

					switch ($source)
					{
						case 'zoobank':
						default:
							if ($ext == 'json')
							{
								$contents = file_get_contents($filename);
								echo $contents;
							}
							break;
					}
				
				}
			}
		}
	}
}

?>