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
error_reporting(E_ALL);

$currentDir = './';

// Inculde the phpcrawl-mainclass
require_once($currentDir . "../crawler/PHPCrawl_071/classes/phpcrawler.class.php");
require_once($currentDir . "../crawler/PHPCrawl_071/classes/mycrawler.php");

//Include parsing class and http library
require_once($currentDir . 'classes/simplehtmldom/simple_html_dom.php');
require_once($currentDir . 'classes/httpclient-2011-08-21/http.php');

//Include Entity Classes
require_once($currentDir . 'classes/Form.php');
require_once($currentDir . 'classes/InputField.php');
require_once($currentDir . 'classes/Logger.php');
require_once($currentDir . 'classes/PostOrGetObject.php');
require_once($currentDir . 'classes/Vulnerability.php');

//Include Function Scripts
require_once($currentDir . 'functions/commonFunctions.php');
require_once($currentDir . 'functions/databaseFunctions.php');
require_once($currentDir . 'functions/createPdfReport.php');
require_once($currentDir . 'functions/emailPdfToUser.php');

//Include test scripts
require_once($currentDir . 'tests/testForReflectedXSS.php');
require_once($currentDir . 'tests/testForStoredXSS.php');
require_once($currentDir . 'tests/testForSQLi.php');
require_once($currentDir . 'tests/testDirectObjectRefs.php');
require_once($currentDir . 'tests/testAuthenticationSQLi.php');
require_once($currentDir . 'tests/testUnvalidatedRedirects.php');
require_once($currentDir . 'tests/testDirectoryListingEnabled.php');
require_once($currentDir . 'tests/testHttpBannerDisclosure.php');
require_once($currentDir . 'tests/testAutoComplete.php');
require_once($currentDir . 'tests/testSslCertificate.php');

//Include PDF generator
require_once($currentDir . 'classes/tcpdf/config/lang/eng.php');
require_once($currentDir . 'classes/tcpdf/tcpdf.php');

$log = new Logger();
$log->lfile($currentDir . 'logs/eventlogs');

$log->lwrite('Connecting to database');

$connectionFlag = connectToDb($db);

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

if(stripos($urlToScan, 'http') !== 0)
	$urlToScan = 'http://' . $urlToScan;

$log->lwrite("URL to scan: $urlToScan");

$query = "UPDATE tests SET status = 'Preparing Crawl for $urlToScan' WHERE id = $testId;"; 
$db->query($query);

//Check if crawling is enabled
$crawlUrlFlag = false;
if(stristr($testCases,' crawlurl ') !== false)
	$crawlUrlFlag = true;

if($crawlUrlFlag)
{
	$log->lwrite('Instantiating crawler');
	$crawler = &new MyCrawler();
	$crawler->setURL($urlToScan);
	$crawler->setTestId($testId);
	$crawler->addReceiveContentType("/text\/html/");
	$crawler->addNonFollowMatch("/.(jpg|jpeg|gif|png|bmp|css|js)$/ i");
	$crawler->setCookieHandling(true);
	$crawler->setFirstCrawl(true);
	$crawler->setTestId($testId);
	//$crawler->setFollowMode(0);
	//$crawler->setFollowMode(1);
	//$crawler->setFollowMode(2);//default
	//$crawler->setFollowMode(3);//use this for testing localhost site, otherwise it may start testing xampp, phpmyadmin, etc.

	updateStatus($db, "Crawling $urlToScan...", $testId);
	$log->lwrite('Starting crawler');

	$crawler->go();
	$urlsFound = $crawler->urlsFound;
}
else
	$urlsFound = array($urlToScan);

$logStr = sizeof($urlsFound) . ' URLs found for test: ' . $testId;

$log->lwrite("All URLs found excluding exceptions:");
foreach($urlsFound as $currentUrl)
	$log->lwrite($currentUrl);

$siteBeingTested = getSiteBeingTested($urlToScan);

if(stristr($testCases,' bannerdis ') !== false)
{
	//Test domain for HTTP Banner Disclouse
	$log->lwrite("Beginning testing $urlToScan for HTTP Banner Disclosure");
	if(!$crawlUrlFlag)
		testHttpBannerDisclosure($urlsFound[0], $testId); 
	else
		testHttpBannerDisclosure($siteBeingTested, $testId); 
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
	testDirectoryListingEnabled($urlsFound[0], $siteBeingTested, $testId, $crawlUrlFlag); //The first URL in the array is always the full domain name e.g. http://www.abc.com
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
		testForReflectedXSS($urlsFound[$i], $siteBeingTested, $testId);
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
		testForSQLi($urlsFound[$i], $siteBeingTested, $testId);
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
		testAuthenticationSQLi($urlsFound[$i], $siteBeingTested, $testId);
	}
	$log->lwrite('Finished testing each of the URLs for Broken Authentication using SQL Injection for test: ' . $testId);
	updateStatus($db, "Finished testing each of the URLs for Broken Authenticaton using SQL Injection...", $testId);
}

if(stristr($testCases,' sxss ') !== false)
{
	$log->lwrite('Beginning Stored XSS testing on each of the URLs');
	for($i=0; $i<sizeof($urlsFound); $i++)
	{
		testForStoredXSS($urlsFound[$i], $siteBeingTested, $testId, $urlsFound);		
	}
	$log->lwrite('Finished Stored XSS testing of all URLS for test: ' . $testId);
	updateStatus($db, "Finished Stored Cross-Site Scripting testing...", $testId);
}

//Create PDF report
$log->lwrite('Beginning creating PDF report for test: ' . $testId);
createPdfReport($testId, $fileName);
$log->lwrite('Finished creating PDF report for test: ' . $testId);
updateStatus($db, "Finished creating PDF report...", $testId);

if(stristr($testCases,' emailpdf ') !== false)
{
	//Email PDF report
	$log->lwrite('Beginning emailing PDF report to $email for test: ' . $testId);
	emailPdfToUser($fileName, $username, $email, $testId);
	$log->lwrite('Finished emailing PDF report to $email for test: ' . $testId);
	updateStatus($db, "Finished emailing PDF report...", $testId);
}

$query = "UPDATE tests SET scan_finished = 1 WHERE id = $testId;"; 
$result = $db->query($query);

if(stristr($testCases,' emailpdf ') !== false)
	updateStatus($db, "Scan is complete! The report has been emailed to you and is also in your scan history.", $testId);
else
	updateStatus($db, "Scan is complete! The report is in your scan history.", $testId);
	
$db->close();
?>
