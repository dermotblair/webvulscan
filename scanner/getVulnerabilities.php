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
$currentDir = './';
require_once($currentDir . 'functions/databaseFunctions.php');

isset($_POST['testId']) ? $testId = $_POST['testId'] : $testId = 0;

$query = 'SELECT * FROM test_results WHERE test_id = ' . $testId; 
connectToDb($db);
$result = $db->query($query); 
if($result)
{
	$numRows = $result->num_rows;
	if($numRows > 0)
	{
		echo '<b>Vulnerabilites Found:</b>';

		for($i=0; $i<$numRows; $i++)
		{
			$row = $result->fetch_object();
			$type = $row->type;
			$method = strtoupper($row->method);
			$url = $row->url;
			$info = $row->attack_str;
			
			if($type == 'rxss')
			{
				$type = 'Reflected Cross-Site Scripting';
				$info = 'Query Used: ' . $info;
			}
			else if($type == 'sxss')
			{
				$type = 'Stored Cross-Site Scripting';
				$info = 'Query Used: ' . $info;
			}
			else if($type == 'sqli')
			{
				$type = 'SQL Injection';
				$info = 'Query Used: ' . $info;
			}
			else if($type == 'idor')
			{
				$type = '(Potentially Insecure) Direct Object Reference';
				$info = 'Object Referenced: ' . $info;
			}
			else if($type == 'basqli')
			{
				$type = 'Broken Authentication using SQL Injection';
				$info = 'Query Used: ' . $info;
			}
			else if($type == 'unredir')
			{
				$type = 'Unvalidated Redirects';
				$info = 'URL Requested: ' . $info;
			}
			else if($type == 'dirlist')
			{
				$type = 'Directory Listing enabled';
				$info = 'URL Requested: ' . $info;
			}
			else if($type == 'bannerdis')
			{
				$type = 'HTTP Banner Disclosure';
				$info = 'Information Exposed: ' . $info;
			}
			else if($type == 'autoc')
			{
				$type = 'Autocomplete not disabled on password input field';
				$info = 'Input Name: ' . $info;
			}
			else if($type == 'sslcert')
			{
				$type = 'SSL certificate is not trusted';
				$info = 'URL Requested: ' . $info;
			}
				
			echo "<p><b>$type</b><br>";
			$urlHtml = htmlspecialchars($url);
			echo "$method $urlHtml<br>";
			$infoHtml = htmlspecialchars($info);
			echo "$infoHtml</p>";
		}	
		$result->free();
		$db->close();
	}
	else
	{
		echo '<b>No Vulnerabilities Found Yet</b>';
	}
}
?>