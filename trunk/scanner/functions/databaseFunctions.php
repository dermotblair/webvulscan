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

//Common database functions used.

//Connects to database. Returns true on success, False on failure.
function connectToDb(&$db)
{
	$db = $db = new mysqli( 'localhost', 'root', '', 'webvulscan'); 
	if (mysqli_connect_errno()) { 
		return false;
	}
	return true;
}

//Update status of test in db
//e.g. updateStatus($db, 'Starting scan...', 1234);
//Returns true on success, False on failure.
function updateStatus($db, $newStatus, $testId)
{
	$query = "UPDATE tests SET status = '$newStatus' WHERE id = $testId;"; 
	$result = $db->query($query); 
	return $result;
}

function insertTestResult($db, $testId, $type, $method, $url, $attackStr)
{
	$query = "INSERT into test_results(test_id, type, method, url, attack_str) VALUES($testId,'$type','$method','$url','$attackStr')"; 
	$result = $db->query($query); 
	return $result;
}

//Generates the next test id
//Return the next test id on success. Otherwise returns false.
function generateNextTestId($db)
{
	$query = "SELECT MAX(id) FROM tests";
	$result = $db->query($query);
	if(!$result)
		return $result;
	
	$row = $result->fetch_array();
	
	$maxId = $row[0] + 1;
	//$maxId = $row->id;//or else $row->MAX(id)
	return $maxId;
}

//Adds 1 to the current number of HTTP requests sent
//Returns true on success, false on failure
function incrementHttpRequests($db, $testId)
{
	$query = "UPDATE tests SET num_requests_sent = (num_requests_sent + 1) WHERE id = $testId";
	$result = $db->query($query);
	return $result;
}

?>