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

//This script generates a PDF report using the open source project TCPDF

//For testing:
/*
require_once('databaseFunctions.php');
require_once('../classes/tcpdf/config/lang/eng.php');
require_once('../classes/tcpdf/tcpdf.php');
require_once('../classes/Logger.php');
require_once('../classes/Vulnerability.php');

createPdfReport(98, $fileName);
////echo $fileName . '<br>';
*/

function createPdfReport($testId, &$fileName){

connectToDb($db);
updateStatus($db, "Generating PDF report for test: $testId...", $testId);

$log = new Logger();
$log->lfile('logs/eventlogs');
$log->lwrite("Starting PDF generator function for test: $testId");

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('WebVulScan');
$pdf->SetTitle('Report for Test: ' . $testId);
$pdf->SetSubject('Vulnerabilities Found');

// set default header data
date_default_timezone_set('UTC');
$now = date('l jS F Y h:i:s A');
$headerStr = "Test ID: $testId\n$now";
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Website Vulnerability Scaner', $headerStr);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
global $l;
$pdf->setLanguageArray($l);

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('dejavusans', '', 10, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// Set some content to print
$html = '<br><h1>WebVulScan Detailed Report</h1>';
$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

$pdf->AddPage();//Add another page

//Generate Summary
$log->lwrite("Displaying summary in PDF");
$summary = '';
$query = "SELECT * FROM tests WHERE id = $testId";
$result = $db->query($query);
if(!$result)
	$log->lwrite("Could not execute query $query");
else
{
	$log->lwrite("Successfully executed query $query");
	$row = $result->fetch_object();
	
	$urlsFound = $row->numUrlsFound;
	$requestsSent = $row->num_requests_sent;
	$startTime = $row->start_timestamp;
	$finTime = $row->finish_timestamp;
	$targetSite = $row->url;
	
	$startTimeFormatted = date('l jS F Y h:i:s A', $startTime);
	$finTimeFormatted = date('l jS F Y h:i:s A', $finTime);
	$duration = $finTime - $startTime;
	$mins = intval($duration/60);
	$seconds = $duration % 60;
	$secondsStr = strval($seconds);
	$secondsFormatted = str_pad($secondsStr,2,"0",STR_PAD_LEFT);
	
	$query = "SELECT * FROM test_results WHERE test_id = $testId;"; 
	$result = $db->query($query); 
	$numVulns = 0;
	if($result)
		$numVulns = $result->num_rows;
	else
		$log->lwrite("Could not execute query $query");
		
	//Populate vulnerability types into a list for use when calculating pie chart dimensions
	$vulnTypes = array();
	for($i=0; $i<$numVulns; $i++)
	{
		$row = $result->fetch_object();
		$type = $row->type;
		array_push($vulnTypes, $type);
	}
		
	$summary .= '<table>';
	$summary .= "<tr><td>Target Site:</td><td>$targetSite</td></tr>";
	$summary .= "<tr><td>Start Date/Time:</td><td>$startTimeFormatted</td></tr>";
	$summary .= "<tr><td>Finish Date/Time:</td><td>$finTimeFormatted</td></tr>";
	$summary .= "<tr><td>Duration:</td><td>$mins minutes and $secondsFormatted seconds</td></tr>";
	$summary .= "<tr><td>Report Generated on:</td><td>$now</td></tr>";
	$summary .= "<tr><td>No. URLs Found:</td><td>$urlsFound</td></tr>";
	$summary .= "<tr><td>No. Vulnerabilites Found:</td><td>$numVulns</td></tr>";
	$summary .= "<tr><td>No. HTTP Requests Sent:</td><td>$requestsSent</td></tr>";
	$summary .= '</table>';
}


$html = '<h2>Summary</h2>' . $summary;
	
$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

//Generate pie chart showing priorities of vulnerabilities found
if($numVulns > 0)
{
	//Calculate number of high, medium and low risk vulnerabilities
	$high = 0;
	$medium = 0;
	$low = 0;
	
	$sizeVulnTypes = sizeof($vulnTypes);
	
	foreach($vulnTypes as $currentVulnType)
	{	
		
		$query = "SELECT * FROM vulnerabilities WHERE id = '$currentVulnType'";
		$result = $db->query($query);
		if($result)
		{
			$row = $result->fetch_object();
			$priority = $row->priority;
		}
		
		if($priority == 'High')
			$high++;
		else if($priority == 'Medium')
			$medium++;
		else if($priority == 'Low')
			$low++;
	}
	
	$html = '<br><br><br><h3>Vulnerability Distribution</h3>';
	$html .= '<font color="red">- ' . $high . ' high risk </font><br>';
	$html .= '<font color="blue">- ' . $medium . ' medium risk </font><br>';
	$html .= '<font color="green">- ' . $low . ' low risk </font><br>';
	
	$highPortion =  ($high/$sizeVulnTypes) * 360;
	$mediumPortion =  ($medium/$sizeVulnTypes) * 360;
	$lowPortion = ($low/$sizeVulnTypes) * 360;
	
	$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
	
	$xc = 105;
	$yc = 150;
	$r = 50;

	//High
	$pdf->SetFillColor(0, 255, 0);
	$pdf->PieSector($xc, $yc, $r, 0, $lowPortion, 'FD', false, 0, 2);
	
	$accum = $lowPortion + $mediumPortion;
	
	//Medium
	$pdf->SetFillColor(0, 0, 255);
	$pdf->PieSector($xc, $yc, $r, $lowPortion, $accum, 'FD', false, 0, 2);
	
	//Low
	$pdf->SetFillColor(255, 0, 0);
	$pdf->PieSector($xc, $yc, $r, $accum, 0, 'FD', false, 0, 2);
}

$pdf->AddPage();

if($numVulns > 0)
{
	//Generate Details of Vulnerabilities Found
	$html = '<h2>Vulnerabilities Found</h2><br>';
	$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

	//Identify what vulnerabilities were found
	$log->lwrite("Identifying what vulnerabilities were found during test");
	$vulnsFound = array();//array containing Vulnerability objects of all vulnerabilities found for this test
	$vulnsIds = array();//array containing the IDs of the flaws found (with no duplications) for this test
	$query = "SELECT * FROM test_results WHERE test_id = $testId";
	$result = $db->query($query);
	if(!$result)
		$log->lwrite("Could not execute query $query");
	else
	{
		$log->lwrite("Successfully executed query $query");
		$numRows = $result->num_rows;
		for($i = 0; $i < $numRows; $i++)
		{
			$row = $result->fetch_object();
			
			$test_id = $row->test_id;
			$type = $row->type;
			$method = $row->method;
			$url = $row->url;
			$attack_str = $row->attack_str;
			
			$vuln = new Vulnerability($test_id, $type, $method, $url, $attack_str);
			array_push($vulnsFound, $vuln);
			
			if(!in_array($type, $vulnsIds))
				array_push($vulnsIds, $type);

		}
	}
	
	usort($vulnsIds, "compareVulns");

	//Displaying details of each vulnerability found including description, 
	//solution, priority and showing all instances where it was found
	$log->lwrite("Displaying details in PDF of each vulnerability found");

	foreach($vulnsIds as $currentId)
	{
		$html = '';
		$query = "SELECT * FROM vulnerabilities WHERE id = '$currentId';";
		$result = $db->query($query);
		if(!$result)
			$log->lwrite("Could not execute query $query");
		else
		{
			//Display details of vulnerability
			$row = $result->fetch_object();
			$name = $row->name;
			$description = $row->description;
			$solution = $row->solution;
			$priority = $row->priority;
			$html .= "<h3>$name</h3>";
			$html .= "<h4>Priority: </h4>$priority";
			$html .= "<h4>Description: </h4>";
			$html .= stripslashes($description);
			$html .= "<h4>Recommendations: </h4>";
			$html .= stripslashes($solution);
			$html .= '<br>';
			$html .= '<h4>Instances Found:</h4>';
			
			//Display all instances of vulnerability
			foreach($vulnsFound as $currentVuln)
			{
				if($currentVuln->getType() == $currentId)
				{
					$html .= '<b>URL:</b> ' . htmlspecialchars($currentVuln->getUrl()) . '<br>';
					$html .= '<b>Method:</b> ' . strtoupper($currentVuln->getMethod()) . '<br>';
					
					$type = $currentVuln->getType();
					$attackStr = htmlspecialchars($currentVuln->getAttackStr());
					if($type == 'rxss' || $type == 'sxss' || $type == 'sqli' || $type == 'basqli')
						$html .= "<b>Query Used:</b> $attackStr<br>";
					else if($type == 'idor')
						$html .= "<b>Object Referenced:</b> $attackStr<br>";
					else if($type == 'dirlist')
						$html .= "<b>URL Requested:</b> $attackStr<br>";
					else if($type == 'bannerdis')
						$html .= "<b>Information Exposed:</b> $attackStr<br>";
					else if($type == 'unredir')
						$html .= "<b>URL Requested:</b> $attackStr<br>";
					else if($type == 'autoc')
						$html .= "<b>Input Name:</b> $attackStr<br>";
					
					$html .= '<br>';
				}				
			}
		}
		$html .= '<br><br>';
		//echo $html;
		$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
	
		$html = '';
	}
}
else 
	$html = '<h2>No Vulnerabilities Found</h2><br>';

$html .= '<h1>Thank you for scanning with WebVulScan!</h1>';
$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

$fileName = 'reports/Test_' . $testId . '.pdf';
	
//Output PDF, this function has multiple options
$pdf->Output($fileName, 'F'); //set this to 'F' to save as file, 'I' to output to browser, E: return the document as base64 mime multi-part email attachment
//$pdf->Output('test.pdf', 'I');//for testing
}

//Compares two vulnerability IDs based on their priority
//This function is passed into PHP's usort function along with an array of vulnerability IDs
function compareVulns($vulnOneId, $vulnTwoId)
{
	if(!connectToDb($db))
		return 0;
	$queryOne = "SELECT * FROM vulnerabilities WHERE id = '$vulnOneId'";
	$queryTwo = "SELECT * FROM vulnerabilities WHERE id = '$vulnTwoId'";
	$resultOne = $db->query($queryOne);
	$resultTwo = $db->query($queryTwo);

	if(!($resultOne && $resultTwo))
		return 0;
	
	$rowOne = $resultOne->fetch_object();
	$rowTwo = $resultTwo->fetch_object();

	$vulnOnePriority = $rowOne->priority_num;
	$vulnTwoPriority = $rowTwo->priority_num;
	
	if($vulnOnePriority == $vulnTwoPriority)
		return 0;
	else if($vulnOnePriority > $vulnTwoPriority)
		return -1;
	else //$vulnOnePriority < $vulnTwoPriority
		return 1;
}

?>