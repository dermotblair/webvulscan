<?php
/////////////////////////////////////////////////////////////////////////////
// WebVulScan
// - Web Application Vulnerability Scanning Software
//
// Copyright (C) 2012 Dermot Blair (webvulscan@gmail.com)
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// This project includes other open source projects which are as follows:
// - PHPCrawl(http://phpcrawl.cuab.de/) - Licensed under GNU General Public 
//   License Version 2.
// - PHP HTTP Protocol Client(http://www.phpclasses.org/package/3-PHP-HTTP-
//   client-to-access-Web-site-pages.html) - Licensed under BSD 2-Clause 
//   License
// - PHP Simple HTML DOM Parser (http://simplehtmldom.sourceforge.net/) - 
//   Licensed under the MIT license
// - TCPDF(http://www.tcpdf.org/) - Licensed under GNU Lesser General Public 
//   License Version 3
// - jQuery(http://jquery.com/) - Dual licensed the MIT or GNU General Public
//   License Version 2 licenses
// - Calliope(http://www.towfiqi.com/xhtml-template-calliope.html) - 
//   Licensed under the Creative Commons Attribution 3.0 Unported License 
//
// This software was developed, and should only be used, entirely for 
// ethical purposes. Running security testing tools such as this on a 
// website(web application) could damage it. In order to stay ethical, 
// you must ensure you have permission of the owners before testing 
// a website(web application). Testing the security of a website(web application) 
// without authorisation is unethical and against the law in many countries.
//
/////////////////////////////////////////////////////////////////////////////

set_time_limit(0);

//This function checks each of the URLs for direct object references exposed as parameters. e.g. files, directories.

//For testing:
/*
//Include parsing class and http library
require_once('../classes/simplehtmldom/simple_html_dom.php');
//require_once('HTTPclasses/HTTPclient/HTTPClient.class.php');
require_once('../classes/httpclient-2011-08-21/http.php');

//Include Entity Classes
require_once('../classes/Form.php');
require_once('../classes/InputField.php');
require_once('../classes/Logger.php');
require_once('../classes/PostOrGetObject.php');

//Include Function Scripts
require_once('../functions/commonFunctions.php');
require_once('../functions/databaseFunctions.php');
				
testDirectObjectRefs($testUrls,500);//Just for testing
*/

function testDirectObjectRefs($arrayOfURLs, $testId){

connectToDb($db);
updateStatus($db, "Testing all URLs for Insecure Direct Object References...", $testId);

$log = new Logger();
$log->lfile('logs/eventlogs');

$log->lwrite("Identifying which URLs have parameters");

$log->lwrite("All URLs found during crawl:");

$urlsWithParameters = array();

foreach($arrayOfURLs as $currentUrl)
{
	$log->lwrite($currentUrl);
	if(strpos($currentUrl,"?"))
		array_push($urlsWithParameters, $currentUrl);
}

$log->lwrite("URLs with parameters:");
foreach($urlsWithParameters as $currentUrl)
	$log->lwrite($currentUrl);

$log->lwrite("Testing each URL that has parameters");
foreach($urlsWithParameters as $currentUrl)
{
	$parsedUrl = parse_url($currentUrl);
	if($parsedUrl)
	{
		$query = $parsedUrl['query'];
		$parameters = array();
		parse_str($query,$parameters);
		foreach($parameters as $para)
		{
			if(preg_match('/\.([^\.]+)$/',$para))
			{
				//Check if this vulnerability has already been found and added to DB. If it hasn't, add it to DB.
				$tableName = 'test' . $testId;
				$query = "SELECT * FROM test_results WHERE test_id = $testId AND type = 'idor' AND method = 'get' AND url = '$currentUrl' AND attack_str = '$para'";
				$result = $db->query($query);
				if(!$result)
					$log->lwrite("Could not execute query $query");
				else
				{
					$log->lwrite("Successfully executed query $query");
					$numRows = $result->num_rows;
					if($numRows == 0)
					{	
						$log->lwrite("Number of rows is $numRows for query: $query");
						insertTestResult($db, $testId, 'idor', 'get', $currentUrl, $para);
					}
				}
			}
		}
	}
	else
		$log->lwrite("Could not parse malformed URL: $currentUrl");
}
		
}

?>