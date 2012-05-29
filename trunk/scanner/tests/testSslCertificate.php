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

//This function verifies the ssl certificate in use by the URL being tested
//It compares it against a list trusted certificates
//This list is compiled by Mozilla and updated weekly (http://curl.haxx.se/docs/caextract.html)
//The function automatically looks for updates from http://curl.haxx.se/ca/cacert.pem every time the funcion runs.
//If updates are found, the file on this server is overwritten with the new file from http://curl.haxx.se/ca/cacert.pem

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
		
testSslCertificate($urlsToTest ,500);//Just for testing
*/

function testSslCertificate($urlsToTest, $testId){

connectToDb($db);
updateStatus($db, "Testing $urlsToTest for untrustworthy SSL certificates...", $testId);

$log = new Logger();
$log->lfile('logs/eventlogs');

$log->lwrite("Starting SSL certificate verification function on $urlsToTest");

//Identify which URLs, if any, begin with https
$log->lwrite("Identifying which URLs, if any, begin with HTTPS");
updateStatus($db, "Identifying which URLs, if any, begin with HTTPS...", $testId);

$usingHttps = false;
$httpsUrl = '';

foreach($urlsToTest as $currentUrl)
{
	if(substr($currentUrl, 0, 5) == 'https')
	{
		$usingHttps = true;
		$httpsUrl = $currentUrl;
		echo "https url = $currentUrl <br>";
		$log->lwrite("Found HTTPS URL: $currentUrl");
		break;
	}
}

if($usingHttps)
{
	//Check if Mozilla's cacert.pem file is online and update our version of it if needed
	$log->lwrite("Checking if cacert.pem is up to date");
	$http = new http_class;
	$http->timeout=0;
	$http->data_timeout=0;
	//$http->debug=1;
	$http->user_agent="Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
	$http->follow_redirect=1;
	$http->redirection_limit=5;

	$cacertsUrl = "http://curl.haxx.se/ca/cacert.pem";

	$error=$http->GetRequestArguments($cacertsUrl,$arguments);
							
	$error=$http->Open($arguments);

	$log->lwrite("URL to be requested is: $cacertsUrl");

	if($error=="")
	{
		$log->lwrite("Sending HTTP request to $cacertsUrl");
		$error=$http->SendRequest($arguments);
		
		if($error=="")
		{
			$headers=array();
			$error=$http->ReadReplyHeaders($headers);
			if($error=="")
			{				
				$responseCode = $http->response_status;//This is a string
				$log->lwrite("Received response code: $responseCode");
				if(intval($responseCode) == 200)
				{
					//Update cacerts.pem file
					$cacerts = file_get_contents($cacertsUrl);
					$oldCacerts = file_get_contents('tests/cacert.pem');
					if($cacerts != $oldCacerts)
					{
						file_put_contents('tests/cacert.pem',$cacerts);
						$log->lwrite("cacert.pem file updated");
					}
					else
					{
						$log->lwrite("cacert.pem is already up to date so was not updated");
					}
				}
				else
					$log->lwrite("Problem accessing Mozilla's URL containing cacert.pem file");
			}
		}
	}
	
	// Initialize session and set URL.
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $httpsUrl);

	// Set so curl_exec returns the result instead of outputting it.
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$user_agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)"; 
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent); 

	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );

	//Check server's certificate against certificates specified in .pem file below
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 

	//If last parameter is 1, checks the SSL certificate for a comman name (the domain of the site sometimes specified in the certificate), e.g. the site that acquired the certificate
	//If last parameter is 2, checks for the common name and, if it exists, checks that it matches the hostname provided
	//Default is 2
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 

	//Using Mozillas certificate file with trusted certificates
	curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/cacert.pem");

	// Get the response and close the channel.
	$response = curl_exec($ch);
	
	if($db)
		incrementHttpRequests($db, $testId);

	if(empty($response))
	{
		//The echo's here are for testing/debugging the function on its own
		echo '<br>SSL Certificate is not trusted!<br>Url: ' . $httpsUrl . '<br>';
		echo 'Method: GET <br>';
		//echo 'Url Requested: ' . $testUrl . '<br>';
		echo 'Error: ' .  curl_error($ch) . '<br>';
		$tableName = 'test' . $testId;
	
		//Check if this vulnerability has already been found and added to DB. If it hasn't, add it to DB.
		$query = "SELECT * FROM test_results WHERE test_id = $testId AND type = 'sslcert' AND method = 'get' AND url = '$httpsUrl' AND attack_str = '$httpsUrl'";
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
				insertTestResult($db, $testId, 'sslcert', 'get', $httpsUrl, $httpsUrl);
			}
		}	
	}
    curl_close($ch);
}

}