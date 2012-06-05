<?php
$currentDir = './';
require_once($currentDir . '../scanner/functions/databaseFunctions.php');

class MyCrawler extends PHPCrawler 
{ 
  function handlePageData(&$page_data) 
  { 	
	array_push($this->urlsFound, $page_data["url"]);
	if($this->firstCrawl)
	{
		$testId = $this->testId;
		
		$newUrl = $page_data['url'];
		$query = "UPDATE tests SET status = 'Found URL $newUrl' WHERE id = $testId;"; 
		if(connectToDb($db))
		{
			$db->query($query); 
			$query = "UPDATE tests SET numUrlsFound = numUrlsFound + 1 WHERE id = $testId;"; 
			$db->query($query); 
			$query = "UPDATE tests SET urls_found=CONCAT(urls_found,'$newUrl<br>') WHERE id = $testId;";//Nearly doubles the duration of the crawl
			$db->query($query); 
		}
	}
	
  }
}
?>
