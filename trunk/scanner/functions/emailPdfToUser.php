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

//This script emails a PDF report to the user
/*
//For testing:

require_once('databaseFunctions.php');
require_once('../classes/Logger.php');

emailPdfToUser('/../reports/Test_101.pdf', 'Dermot', 'youremail@hotmail.com',101);
*/

function emailPdfToUser($fileName, $username, $email, $testId)
{

connectToDb($db);
updateStatus($db, "Emailing PDF report to $email...", $testId);

$log = new Logger();
$log->lfile('logs/eventlogs');
$log->lwrite("Starting email PDF function for test: $testId");

if(file_exists($fileName))
{
	$log->lwrite("File: $fileName exists");
	
	$fileatt = $fileName; // Path to the file 
	$fileatt_type = "application/pdf"; // File Type 
	$fileatt_name = 'Test_' . $testId . '.pdf'; // Filename that will be used for the file as the attachment 

	$email_from = "webvulscan@gmail.com"; // Who the email is from, don't think this does anything
	$email_subject = "WebVulScan Detailed Report"; // The Subject of the email 
	$email_message = "Hello $username,<br><br>";
	$email_message .= 'Thank you for scanning with WebVulScan. Please find the scan results attached in the PDF report.<br><br>';
	$email_message .= 'Please reply to this email if you have any questions.<br><br>';
	$email_message .= 'Kind Regards,<br><br>';
	$email_message .= 'WebVulScan Team<br>';

	$email_to = $email; // Who the email is to 

	$headers = "From: ".$email_from; 

	$file = fopen($fileatt,'rb'); 
	$data = fread($file,filesize($fileatt)); 
	fclose($file); 

	$semi_rand = md5(time()); 
	$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 

	$headers .= "\nMIME-Version: 1.0\n" . 
	"Content-Type: multipart/mixed;\n" . 
	" boundary=\"{$mime_boundary}\""; 

	$email_message .= "This is a multi-part message in MIME format.\n\n" . 
	"--{$mime_boundary}\n" . 
	"Content-Type:text/html; charset=\"iso-8859-1\"\n" . 
	"Content-Transfer-Encoding: 7bit\n\n" . 
	$email_message .= "\n\n"; 

	$data = chunk_split(base64_encode($data)); 

	$email_message .= "--{$mime_boundary}\n" . 
	"Content-Type: {$fileatt_type};\n" . 
	" name=\"{$fileatt_name}\"\n" . 
	//"Content-Disposition: attachment;\n" . 
	//" filename=\"{$fileatt_name}\"\n" . 
	"Content-Transfer-Encoding: base64\n\n" . 
	$data .= "\n\n" . 
	"--{$mime_boundary}--\n"; 

	$mailSent = mail($email_to, $email_subject, $email_message, $headers); 

	if($mailSent) 
		$log->lwrite("$fileName successfully sent to $email");
	else 
		$log->lwrite("There was a problem sending $fileName to $email");	

}
else
	$log->lwrite("File: $fileName does not exist");

}





?>