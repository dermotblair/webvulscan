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
require_once($currentDir . "PHPCrawl_071/classes/phpcrawler.class.php");
require_once($currentDir . "PHPCrawl_071/classes/mycrawler.php");

//Include parsing class and http library
require_once($currentDir . '../scanner/classes/simplehtmldom/simple_html_dom.php');
require_once($currentDir . '../scanner/classes/httpclient-2011-08-21/http.php');

//Include Entity Classes
require_once($currentDir . '../scanner/classes/Form.php');
require_once($currentDir . '../scanner/classes/InputField.php');
require_once($currentDir . '../scanner/classes/Logger.php');
require_once($currentDir . '../scanner/classes/PostOrGetObject.php');

//Include Function Scripts
require_once($currentDir . '../scanner/functions/commonFunctions.php');
require_once($currentDir . '../scanner/functions/databaseFunctions.php');

$log = new Logger();
$log->lfile($currentDir . 'logs/eventlogs');

$log->lwrite('Connecting to database');

connectToDb($db);

$log->lwrite('Instantiating crawler');

$crawler = &new MyCrawler();

isset($_POST['specifiedUrl']) ? $urlToScan = $_POST['specifiedUrl'] : $urlToScan = '';
isset($_POST['testId']) ? $testId = $_POST['testId'] : $testId = 0;

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
//$crawler->setFollowMode(3);//Follow mode can be set to 0,1,2 or 3. See class reference online

$crawler->addReceiveContentType("/text\/html/");

$crawler->addNonFollowMatch("/.(jpg|jpeg|gif|png|bmp|css|js)$/ i");

$crawler->setCookieHandling(true);

$crawler->setFirstCrawl(true);

updateStatus($db, "Crawling $urlToScan...", $testId);
$log->lwrite('Starting crawler');
$crawler->go();

$query = "UPDATE tests SET scan_finished = 1 WHERE id = $testId;"; 
$result = $db->query($query);

$urlsFound = $crawler->urlsFound;

$logStr = sizeof($urlsFound) . ' URLs found for test: ' . $testId;

$log->lwrite("All URLs found excluding exceptions:");
foreach($urlsFound as $currentUrl)
	$log->lwrite($currentUrl);


?> 
