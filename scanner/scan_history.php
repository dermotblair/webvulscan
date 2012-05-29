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

require_once('functions/databaseFunctions.php');

global $user;
	
if(isset($_SESSION['username']))
{
	//Get the user's username and email address
	$username = $_SESSION['username'];
		
	if(!connectToDb($db))
	{
		echo 'There was a problem connecting to the database';
		return;
	}
	
	$query = "SELECT * FROM tests WHERE type = 'scan' AND username = '$username'";
	//echo $query;
	$result = $db->query($query);
	if($result)
	{
		$numRows = $result->num_rows;
		if($numRows == 0)
			echo 'You have not performed any previous scans';
		else
		{
			echo '<table border="3" width="900"><tr><th>ID</th><th>Start Time</th><th>URL</th><th>No. Vulnerabilities</th><th>Report</th></tr>';
			for($i=0; $i<$numRows; $i++)
			{
				$row = $result->fetch_object();
				$id = $row->id;
				$startTime = $row->start_timestamp;
				$startTimeFormatted = date('l jS F Y h:i:s A', $startTime);
				$url = $row->url;
				
				$numVulns = 'Unknown';
				$query = "SELECT * FROM test_results WHERE test_id = $id";
				$resultTwo = $db->query($query);
				if($resultTwo)
					$numVulns = $resultTwo->num_rows;
			
				$report = '<a href="scanner/reports/Test_' . $id . '.pdf" target="_blank">View</a>';
				
				echo '<tr>';
				echo "<td align='center'>$id</td>";
				echo "<td align='left'>$startTimeFormatted</td>";
				echo "<td align='left'>$url</td>";
				echo "<td align='center'>$numVulns</td>";
				echo "<td align='center'>$report</td>";
				echo '</tr>';
			
			}
			echo '</table>';

		}
	
	}
	else
		echo 'There was a problem retrieving your data from the database';
}
else
	echo 'You are not logged in. Please log in to use this feature.';





?>