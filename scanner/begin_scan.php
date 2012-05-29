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

error_reporting(E_ALL);
require_once('functions/databaseFunctions.php');

// Inculde the phpcrawl-mainclass
require_once("../crawler/PHPCRAWL_071/classes/phpcrawler.class.php");
require_once("../crawler/PHPCRAWL_071/classes/mycrawler.php");

//Include parsing class and http library
require_once('classes/simplehtmldom/simple_html_dom.php');
//require_once('HTTPclasses/HTTPclient/HTTPClient.class.php');
require_once('classes/httpclient-2011-08-21/http.php');

//Include Entity Classes
require_once('classes/Form.php');
require_once('classes/InputField.php');
require_once('classes/Logger.php');
require_once('classes/PostOrGetObject.php');
require_once('classes/Vulnerability.php');

//Include Function Scripts
require_once('functions/commonFunctions.php');
require_once('functions/databaseFunctions.php');
require_once('functions/createPdfReport.php');
require_once('functions/emailPdfToUser.php');

//Include test scripts
require_once('tests/testForReflectedXSS.php');
require_once('tests/testForStoredXSS.php');
require_once('tests/testForSQLi.php');
require_once('tests/testDirectObjectRefs.php');
require_once('tests/testAuthenticationSQLi.php');
require_once('tests/testUnvalidatedRedirects.php');
require_once('tests/testDirectoryListingEnabled.php');
require_once('tests/testHttpBannerDisclosure.php');
require_once('tests/testAutoComplete.php');
require_once('tests/testSslCertificate.php');

//Include PDF generator
require_once('classes/tcpdf/config/lang/eng.php');
require_once('classes/tcpdf/tcpdf.php');

set_time_limit(0);

$log = new Logger();
$log->lfile('logs/eventlogs');

$log->lwrite('Connecting to database');

$connectionFlag = connectToDb($db);

$log->lwrite('Instantiating crawler');

$crawler = &new MyCrawler();

isset($_POST['specifiedUrl']) ? $urlToScan = $_POST['specifiedUrl'] : $urlToScan = '';
isset($_POST['testId']) ? $testId = $_POST['testId'] : $testId = 0;
isset($_POST['username']) ? $username = $_POST['username'] : $username = 'User';
isset($_POST['email']) ? $email = $_POST['email'] : $email = 'webvulscan@gmail.com';//admin address
isset($_POST['testCases']) ? $testCases = $_POST['testCases'] : $testCases = '';//admin address

if(empty($urlToScan))
{
	echo 'urlToScan is empty';
	$log->lfile('urlToScan is empty');
	return;
}

$log->lwrite("URL to scan: $urlToScan");

$query = "UPDATE tests SET status = 'Preparing Crawl for $urlToScan' WHERE id = $testId;"; 
$db->query($query);

$crawler->setURL($urlToScan);
$crawler->setTestId($testId);

$crawler->addReceiveContentType("/text\/html/");

$crawler->addNonFollowMatch("/.(jpg|jpeg|gif|png|bmp|css|js)$/ i");

$crawler->setCookieHandling(true);

$crawler->setFirstCrawl(true);

$crawler->setTestId($testId);

//$crawler->setPageLimit(0,false);

//$crawler->setAggressiveLinkExtraction(false);

//$crawler->setFollowRedirects(false);
//$crawler->setFollowRedirectsTillContent(false);

//$crawler->setFollowMode(0);
//$crawler->setFollowMode(1);
//$crawler->setFollowMode(2);//default
//$crawler->setFollowMode(3);//use this for testing localhost site, otherwise it starts testing xampp, phpmyadmin, etc.

updateStatus($db, "Crawling $urlToScan...", $testId);
$log->lwrite('Starting crawler');

$crawler->go();

//$array = $crawler->getReport();
//$log->lwrite('links followed: ' . $array['links_followed']);//31
/*$log->lwrite('links_found:');

$crawler->handlePageData($array);
$list = $array['links_found'];
$log->lwrite('links_found');
foreach($list as $item)
	$log->lwrite($item);
*/
/*
$log->lwrite("Creating results table for test with ID: $testId");
$query = 'CREATE TABLE test' . $testId . '(type text, method text, url text, attackStr text)';
$result = $db->query($query);
if(!$result)
{
	$log->lwrite("Error creating table for test: $testId");
	echo "Error creating table for test: $testId";
	return;
}
else
{
	$log->lwrite("Successfully created table for test: $testId");
}
*/

$urlsFound = $crawler->urlsFound;

//unset($crawler);//free memory

$logStr = sizeof($urlsFound) . ' URLs found for test: ' . $testId;

$log->lwrite("All URLs found excluding exceptions:");
foreach($urlsFound as $currentUrl)
	$log->lwrite($currentUrl);

if(stristr($testCases,' bannerdis ') !== false)
{
	//Test domain for HTTP Banner Disclouse
	$log->lwrite("Beginning testing $urlToScan for HTTP Banner Disclosure");
	testHttpBannerDisclosure($urlsFound[0], $testId); //The first URL in the array is always the full domain name e.g. http://www.abc.com
	$log->lwrite("Finished testing $urlToScan for HTTP Banner Disclosure for test: $testId");
	updateStatus($db, "Finished testing $urlToScan for HTTP Banner Disclosure...", $testId);
}

if(stristr($testCases,' autoc ') !== false)
{
	//Test domain for autocomplete not disabled on input fields of type password
	$log->lwrite('Beginning testing each of the URLs for autocomplete not disabled on sensitive input fields');
	for($i=0; $i<sizeof($urlsFound); $i++)
	{
		testAutoComplete($urlsFound[$i], $testId);
	}
	$log->lwrite('Finished testing each of the URLs for autocomplete not disabled on sensitive input fields for test: ' . $testId);
	updateStatus($db, "Finished testing each of the URLs for autocomplete not disabled on sensitive input fields...", $testId);
}

if(stristr($testCases,' dirlist ') !== false)
{
	//Test domain for Directory Listing enabled
	$log->lwrite("Beginning testing $urlToScan for Directory Listing enabled");
	testDirectoryListingEnabled($urlsFound[0], $testId); //The first URL in the array is always the full domain name e.g. http://www.abc.com
	$log->lwrite("Finished testing $urlToScan for Directory Listing enabled for test: $testId");
	updateStatus($db, "Finished testing $urlToScan for Directory Listing enabled...", $testId);
}

if(stristr($testCases,' idor ') !== false)
{
	//Test all URLs for Insecure Direct Object References
	$log->lwrite('Beginning testing each of the URLs for Insecure Direct Object References');
	testDirectObjectRefs($urlsFound, $testId);
	$log->lwrite('Finished testing each of the URLs for Insecure Direct Object References for test: ' . $testId);
	updateStatus($db, "Finished testing each of the URLs for Insecure Direct Object References...", $testId);
}

if(stristr($testCases,' unredir ') !== false)
{
	//Test all URLs for Unvalidated Redirects
	$log->lwrite('Beginning testing each of the URLs for Unvalidated Redirects');
	testUnvalidatedRedirects($urlsFound, $testId);
	$log->lwrite('Finished testing each of the URLs for Unvalidated Redirects for test: ' . $testId);
	updateStatus($db, "Finished testing each of the URLs for Unvalidated Redirects...", $testId);
}

if(stristr($testCases,' sslcert ') !== false)
{
	//Test URLs for untrustworthy SSL certificates
	$log->lwrite('Beginning testing URLs for untrustworthy SSL certificates');
	testSslCertificate($urlsFound, $testId);
	$log->lwrite('Finished testing each of the URLs for untrustworthy SSL certificates for test: ' . $testId);
	updateStatus($db, "Finished testing each of the URLs for untrustworthy SSL certificates...", $testId);
}
	
if(stristr($testCases,' rxss ') !== false)
{
	//Test all URLs for Reflected Cross-Site Scripting
	$log->lwrite('Beginning Reflected XSS testing on each of the URLs');
	for($i=0; $i<sizeof($urlsFound); $i++)
	{
		testForReflectedXSS($urlsFound[$i], $urlsFound[0], $testId);
	}
	$log->lwrite('Finished Reflected XSS testing of all URLS for test: ' . $testId);
	updateStatus($db, "Finished Reflected Cross-Site Scripting testing...", $testId);
}

if(stristr($testCases,' sqli ') !== false)
{
	//Test all URLs for SQL Injection
	$log->lwrite('Beginning SQL Injection testing on each of the URLs');
	for($i=0; $i<sizeof($urlsFound); $i++)
	{
		testForSQLi($urlsFound[$i], $urlsFound[0], $testId);
	}
	$log->lwrite('Finished SQL Injection testing of all URLS for test: ' . $testId);
	updateStatus($db, "Finished SQL Injection testing...", $testId);
}

if(stristr($testCases,' basqli ') !== false)
{
	//Test all URLs for Broken Authentication using SQL Injection
	$log->lwrite('Beginning testing each of the URLs for Broken Authentication using SQL Injection');
	for($i=0; $i<sizeof($urlsFound); $i++)
	{
		testAuthenticationSQLi($urlsFound[$i], $urlsFound[0], $testId);
	}
	$log->lwrite('Finished testing each of the URLs for Broken Authentication using SQL Injection for test: ' . $testId);
	updateStatus($db, "Finished testing each of the URLs for Broken Authenticaton using SQL Injection...", $testId);
}

if(stristr($testCases,' sxss ') !== false)
{
	//Test all URLs for Stored Cross-Site Scripting
	
	/*$logDebug = new Logger();
	$logDebug->lfile("debuglogs$testId");
	$count = 0;*/

	$log->lwrite('Beginning Stored XSS testing on each of the URLs');
	for($i=0; $i<sizeof($urlsFound); $i++)
	{
		/*$sql = "select * from tests where id = $testId";
		$res = $db->query($sql);
		$row = $res->fetch_object();
		$httpReqsBefore = $row->num_requests_sent;*/
	
		testForStoredXSS($urlsFound[$i], $urlsFound[0], $testId, $urlsFound);
		
		/*$sql = "select * from tests where id = $testId";
		$res = $db->query($sql);
		$row = $res->fetch_object();
		$httpReqsAfter = $row->num_requests_sent;
		$uri = $urlsFound[$i];
		
		$numSent = $httpReqsAfter - $httpReqsBefore;
		$count += $numSent;
		$logDebug->lwrite("$numSent requests sent testing $uri");
		$logDebug->lwrite("Total so far is $count");*/
		
	}
	$log->lwrite('Finished Stored XSS testing of all URLS for test: ' . $testId);
	updateStatus($db, "Finished Stored Cross-Site Scripting testing...", $testId);
}

//Create PDF report
$log->lwrite('Beginning creating PDF report for test: ' . $testId);
createPdfReport($testId, $fileName);
$log->lwrite('Finished creating PDF report for test: ' . $testId);
updateStatus($db, "Finished creating PDF report...", $testId);

//Email PDF report
$log->lwrite('Beginning emailing PDF report to $email for test: ' . $testId);
emailPdfToUser($fileName, $username, $email, $testId);
$log->lwrite('Finished emailing PDF report to $email for test: ' . $testId);
updateStatus($db, "Finished emailing PDF report...", $testId);

$query = "UPDATE tests SET scan_finished = 1 WHERE id = $testId;"; 
$result = $db->query($query);

updateStatus($db, "Scan is complete! The report has been emailed to you and is also in your scan history.", $testId);

$db->close();
?>