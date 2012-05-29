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
?>

<script type="text/javascript">
function beginScan(value,valueTwo,valueThree,valueFour,valueFive){
	jQuery.post("scanner/begin_scan.php", {specifiedUrl:value, testId:valueTwo, username:valueThree, email:valueFour,testCases:valueFive});
}


function sizeTbl(h) {
  var tbl = document.getElementById('tbl');
  tbl.style.display = h;
}

</script>

<?php 
			
require_once('functions/databaseFunctions.php');
require_once('classes/Logger.php');

if(isset($_SESSION['username']))
{
	//Get the user's username and email address
	$username = $_SESSION['username'];
		
	if(isset($_SESSION['email']))
		$email = $_SESSION['email'];
	else
		$email = ''; //maybe email to administrator
?>	

	<body>
	<form id="form1" name="form1" method="post" >
        <p>Enter URL to scan:</p>
		<p>
		  <label for="urlToScan"></label>
		  <input type="text" size="40" name="urlToScan" id="urlToScan" />
		<br>
		<a href="javascript:sizeTbl('block')">Options</a></p>
		<div id=tbl name=tbl style="overflow:hidden;display:none">
		Please select which vulnerabilities to test for:<br>
		<input type="checkbox" name="rxss" value="rxss" checked />Reflected Cross-Site Scripting<br>
		<input type="checkbox" name="sxss" value="sxss" checked />Stored Cross-Site Scripting<br>
		<input type="checkbox" name="sqli" value="sqli" checked />Standard SQL Injection<br>
		<input type="checkbox" name="basqli" value="basqli" checked />Broken Authentication using SQL Injection<br>
		<input type="checkbox" name="autoc" value="autoc" checked />Autocomplete enabled on sensitive input fields<br>
		<input type="checkbox" name="idor" value="idor" checked />(Potientially Insecure) Direct Object References<br>
		<input type="checkbox" name="dirlist" value="dirlist" checked />Directory Listing Enabled<br>
		<input type="checkbox" name="bannerdis" value="bannerdis" checked />HTTP Banner Disclosure<br>
		<input type="checkbox" name="sslcert" value="sslcert" checked />SSL Certificate not trusted<br>
		<input type="checkbox" name="unredir" value="unredir" checked />Unvalidated Redirects<br>
		</div>
		<p>
		  <input type="submit" class="button" name="submit" id="submit" value="Start Scan" />
		</p>
	 </form>
	
	

	
	
<?php

	//Drupal Expand/Collapse -> Wont work?
	/*
	drupal_add_js('misc/collapse.js');
	echo '<fieldset class="collapsible collapsed">
		<legend>Title of collapsible content</legend>
		<div class="fieldset-wrapper">Hidden text fwrewrwerere</div>
		</fieldset>';
	*/

	
	if(isset($_POST['urlToScan']))
	{
		$testCases = ' ';//vulnerabilties to test for
		if(isset($_POST['rxss'])) $testCases .= $_POST['rxss'] . ' ';
		if(isset($_POST['sxss'])) $testCases .= $_POST['sxss'] . ' ';
		if(isset($_POST['sqli'])) $testCases .= $_POST['sqli'] . ' ';
		if(isset($_POST['basqli'])) $testCases .= $_POST['basqli'] . ' ';
		if(isset($_POST['autoc'])) $testCases .= $_POST['autoc'] . ' ';
		if(isset($_POST['idor'])) $testCases .= $_POST['idor'] . ' ';
		if(isset($_POST['dirlist'])) $testCases .= $_POST['dirlist'] . ' ';
		if(isset($_POST['bannerdis'])) $testCases .= $_POST['bannerdis'] . ' ';
		if(isset($_POST['sslcert'])) $testCases .= $_POST['sslcert'] . ' ';
		if(isset($_POST['unredir'])) $testCases .= $_POST['unredir'] . ' ';
	
		$urlToScan = trim($_POST['urlToScan']);
		if(!empty($urlToScan))
		{
			$log = new Logger();
			$log->lfile('scanner/logs/eventlogs');

			$log->lwrite('Connecting to database');

			$connectionFlag = connectToDb($db);

			if(!$connectionFlag)
			{
				$log->lwrite('Error connecting to database');
				echo 'Error connecting to database';
				return;
			}

			$log->lwrite('Generating next test ID');
			$nextId = generateNextTestId($db);

			if(!$nextId)
			{
				$log->lwrite('Next ID generated is null');
				echo 'Next ID generated is null';
				return;
			}
			else
			{
				$log->lwrite("Next ID generated is $nextId");
				$testId = $nextId;
				$now = time();
				$query = "INSERT into tests(id,status,numUrlsFound,type,num_requests_sent,start_timestamp,finish_timestamp,scan_finished,url,username,urls_found) VALUES($nextId,'Creating profile for new scan...',0,'scan',0,$now,$now,0,'$urlToScan','$username','')"; 
				$result = $db->query($query);
				if(!$result)
				{
					$log->lwrite("Problem executing query: $query ");
					echo 'Problem inserting a new test into the database. Please try again.';
					return;
				}
				else
				{
					$log->lwrite("Successfully executed query: $query ");
				}
			}

			updateStatus($db, 'Pending...', $testId);

			$query = "UPDATE tests SET numUrlsFound = 0 WHERE id = $testId;"; 
			$db->query($query); 
			$query = "UPDATE tests SET duration = 0 WHERE id = $testId;"; 
			$db->query($query); 
					
				echo '<script type="text/javascript">
				$(document).ready(function() {
				 $.post("scanner/getStatus.php", {testId:' . "$testId" . '}, function(data){$("#status").html(data)});
			   var refreshId = setInterval(function() {
				  $.post("scanner/getStatus.php", {testId:' . "$testId" . '}, function(data){$("#status").html(data)});
			   }, 500);
			   $.ajaxSetup({ cache: false });
				});</script>';
				
				echo '<script type="text/javascript">
				$(document).ready(function() {
				 $.post("scanner/getVulnerabilities.php", {testId:' . "$testId" . '}, function(data){$("#scanstatus").html(data)});
			   var refreshId = setInterval(function() {
				  $.post("scanner/getVulnerabilities.php", {testId:' . "$testId" . '}, function(data){$("#scanstatus").html(data)});
			   }, 1000);
			   $.ajaxSetup({ cache: false });
				});</script>';

				$urlToScan = $_POST['urlToScan'];
				
				$log->lwrite('Calling AJAX function beginCrawl()');
				echo '<script type="text/javascript">';
				echo "beginScan('$urlToScan','$testId','$username','$email', '$testCases');";
				echo '</script>';
				
		}
		else
			echo 'Error: There was no URL entered';
	}

	echo '<div id="status"></div><br>';
	echo '<div id="scanstatus"></div><br>';
}
else
	echo 'You are not logged in. Please log in to use this feature.';
?>