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
//This function checks for if authentication can be bypassed using SQL injection

//For testing script on its own:
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

testAuthenticationSQLi('http://testasp.vulnweb.com/Login.asp?RetURL=/Default.asp?', 'http://testasp.vulnweb.com',500);
*/
function testAuthenticationSQLi($urlToCheck, $urlOfSite, $testId){

connectToDb($db);
updateStatus($db, "Testing $urlToCheck for Broken Authentication using SQL Injection...", $testId);

$log = new Logger();
$log->lfile('logs/eventlogs');

$log->lwrite("Starting Broken Authentication SQLi test function on $urlToCheck");

$postUrl = $urlToCheck;
	
$postUrlPath = parse_url($postUrl, PHP_URL_PATH);

//Check URL is not responding with 5xx codes
$log->lwrite("Checking what response code is received from $urlToCheck");
$http = new http_class;
$http->timeout=0;
$http->data_timeout=0;
//$http->debug=1;
$http->user_agent="Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
$http->follow_redirect=1;
$http->redirection_limit=5;
$http->setTestId($testId);

$error=$http->GetRequestArguments($urlToCheck,$arguments);
						
$error=$http->Open($arguments);

$log->lwrite("URL to be requested is: $urlToCheck");

if($error=="")
{
	$log->lwrite("Sending HTTP request to $urlToCheck");
	$error=$http->SendRequest($arguments);
	
	if($error=="")
	{
		$headers=array();
		$error=$http->ReadReplyHeaders($headers);
		if($error=="")
		{				
			$responseCode = $http->response_status;//This is a string
			$log->lwrite("Received response code: $responseCode");
			if(intval($responseCode) >= 500 && intval($responseCode) <600)
			{
				$log->lwrite("Response code: $responseCode received from: $urlToCheck");
				return;
			}
		}
	}
	$http->Close();
}
if(strlen($error))
{
	echo "<H2 align=\"center\">Error: ",$error,"</H2>\n";
	$log->lwrite("Error: $error");
}

$html = file_get_html($postUrl, $testId);

if(empty($html))//Checks if null or false, etc.
{
	//This can happen due to file_get_contents returning a 500 code. Then the parser won't parse it
	updateStatus($db, "Problem getting contents from $urlToCheck...", $testId);
	$log->lwrite("Problem getting contents from $urlToCheck");
	return;
}

//Array containing all form objects found
$arrayOfForms = array();
//Array containing all input fields
$arrayOfInputFields = array();

$log->lwrite("Searching $postUrl for forms");

$formNum = 1;//Must use an integer to identify form as forms could have same names and ids
foreach($html->find('form') as $form) 
{
	isset($form->attr['id']) ? $formId = htmlspecialchars($form->attr['id']) : $formId = '';
	isset($form->attr['name']) ? $formName = htmlspecialchars($form->attr['name']) : $formName ='';
	isset($form->attr['method']) ? $formMethod = htmlspecialchars($form->attr['method']) : $formMethod = 'get';
	isset($form->attr['action']) ? $formAction = htmlspecialchars($form->attr['action']) : $formAction = '';
	  
	$formMethod = strtolower($formMethod);
	  
	//If the action of the form is empty, set the action equal to everything
	//after the URL that the user entered
	if(empty($formAction))
	{
		$strLengthUrl = strlen($urlToCheck);
		$strLengthSite = strlen($urlOfSite);
		$firstIndexOfSlash = strpos($urlToCheck, '/', $strLengthSite -1);
		$formAction = substr($urlToCheck, $firstIndexOfSlash + 1, $strLengthUrl);
	}
	
	$log->lwrite("Found form on $postUrl: $formId $formName $formMethod $formAction $formNum");
	
	$newForm = new Form($formId, $formName, $formMethod, $formAction, $formNum);
	array_push($arrayOfForms, $newForm); 
	  
	foreach($form->find('input') as $input) 
	{
		isset($input->attr['id']) ? $inputId = htmlspecialchars($input->attr['id']) : $inputId = '';
		isset($input->attr['name']) ? $inputName = htmlspecialchars($input->attr['name']) : $inputName = '';
		isset($input->attr['value']) ? $inputValue = htmlspecialchars($input->attr['value']) : $inputValue = '';
		isset($input->attr['type']) ? $inputType = htmlspecialchars($input->attr['type']) : $inputType = '';
		
		$log->lwrite("Found input field on $postUrl: $inputId $inputName $formId $formName $inputValue $inputType $formNum");
		
		$inputField = new InputField($inputId, $inputName, $formId, $formName, $inputValue, $inputType, $formNum);
		
		array_push($arrayOfInputFields, $inputField);
	}	
	
	$formNum ++;
}

//At this stage, we should have captured all forms and their input fields into the appropriate arrays

//Begin testing each of the forms

//Defintion of all payloads used and warnings to examine for
//Payloads can be added to this
$arrayOfPayloads = array( "1'or'1'='1",
						  "1'or'1'='1';#");
						  
//Check if the URL passed into this function displays the same webpage at different intervals
//If it does then attempt to login and if this URL displays a different page, the vulnerability is present
//e.g. a login page would always look different when you are and are not logged in
$log->lwrite("Checking if $urlToCheck displays the same page at different intervals");

$responseBodies = array();

$http = new http_class;
$http->timeout=0;
$http->data_timeout=0;
//$http->debug=1;
$http->user_agent="Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
$http->follow_redirect=1;
$http->redirection_limit=5;
$http->setTestId($testId);

for($a=0; $a<3; $a++)
{
	$error=$http->GetRequestArguments($urlToCheck,$arguments);
						
	$error=$http->Open($arguments);

	if($error=="")
	{
		$number = $a + 1;
		$log->lwrite("Sending HTTP request number $number to $urlToCheck");
		$error=$http->SendRequest($arguments);
		
		if($error=="")
		{
			$headers=array();
			$error=$http->ReadReplyHeaders($headers);
			if($error=="")
			{
				$error = $http->ReadWholeReplyBody($body);
				if(strlen($error) == 0)
				{	
					array_push($responseBodies, $body);
				}
			}
		}
		$http->Close();
	}
	if(strlen($error))
		echo "<H2 align=\"center\">Error: a= $a ",$error,"</H2>\n";
}

$pageChanges = true;
$bodyOfUrl = "";
if( ($responseBodies[0] == $responseBodies[1]) && ($responseBodies[1] == $responseBodies[2]) )
{
	$bodyOfUrl = $responseBodies[0];
	$pageChanges = false;
}

$log->lwrite('Beginning testing of forms');

for($i=0; $i<sizeof($arrayOfForms); $i++)
{
	$currentForm = $arrayOfForms[$i];
	$currentFormId = $currentForm->getId();
	$currentFormName = $currentForm->getName();
	$currentFormMethod = $currentForm->getMethod();
	$currentFormAction = $currentForm->getAction();
	$currentFormNum = $currentForm->getFormNum();
		
	$arrayOfCurrentFormsInputs = array();
	
	$log->lwrite("Beginning testing of form on $postUrl: $currentFormId $currentFormName $currentFormMethod $currentFormAction");
	
	for($j=0; $j<sizeof($arrayOfInputFields); $j++)
	{
		$currentInput = $arrayOfInputFields[$j];
		$currentInputIdOfForm = $currentInput->getIdOfForm();
		$currentInputNameOfForm = $currentInput->getNameOfForm();
		$currentInputFormNum = $currentInput->getFormNum();
		
		if($currentFormNum == $currentInputFormNum)
		{
			array_push($arrayOfCurrentFormsInputs, $currentInput);
		}
	}
	
	$log->lwrite("Beginning testing input fields of form on $postUrl: $currentFormId $currentFormName $currentFormMethod $currentFormAction");	
	
	foreach($arrayOfPayloads as $currentPayload)
	{
		echo '<br>Size of current form inputs = ' . sizeof($arrayOfCurrentFormsInputs) . '<br>';
		$arrayOfValues = array(); //Array of PostOrGetObject objects
		
		for($k=0; $k<sizeof($arrayOfCurrentFormsInputs); $k++)
		{
				
			$currentFormInput = $arrayOfCurrentFormsInputs[$k];
			$currentFormInputName =  $currentFormInput->getName();
			$currentFormInputType =  $currentFormInput->getType();
			$currentFormInputValue =  $currentFormInput->getValue();
			
			if($currentFormInputType!= 'reset')
			{
					
				$log->lwrite("Using payload: $currentPayload, to all input fields of form w/ action: $currentFormAction");
				//Add current input and other inputs to array of post values and set their values
				if($currentFormInputType == 'text' || $currentFormInputType == 'password')
				{
					$postObject = new PostOrGetObject($currentFormInputName, $currentPayload);
					array_push($arrayOfValues, $postObject);
				}
				else if($currentFormInputType == 'checkbox' || $currentFormInputType == 'submit')
				{
					$postObject  = new PostOrGetObject($currentFormInputName, $currentFormInputValue);
					array_push($arrayOfValues, $postObject);
				}
				else if($currentFormInputType == 'radio')
				{
					$postObject  = new PostOrGetObject($currentFormInputName, $currentFormInputValue);
					//Check if a radio button in the radio group has already been added
					$found = false;
					for($n = 0; $n < sizeof($arrayOfValues); $n++)
					{
						if($arrayOfValues[$n]->getName() == $postObject->getName())
						{
							$found = true;
							break;
						}
					}
						if(!$found)
							array_push($arrayOfValues, $postObject);
				}
			}			
		}						
		if($currentFormMethod == 'get')
		{
			//Build query string and submit it at end of URL
			if($urlOfSite[strlen($urlOfSite)-1] == '/')
				$actionUrl = $urlOfSite . $currentFormAction;
			else
				$actionUrl = $urlOfSite . '/' . $currentFormAction;
			
			$totalTestStr = '';//Make a string to show the user how the vulnerability was tested for i.e. the data submitted to exploit the vulnerability
			for($p=0; $p<sizeof($arrayOfValues); $p++)
			{
				$currentPostValue = $arrayOfValues[$p];
				$currentPostValueName = $currentPostValue->getName();
				$currentPostValueValue = $currentPostValue->getValue();
				
				$totalTestStr .= $currentPostValueName;
				$totalTestStr .= '=';
				$totalTestStr .= $currentPostValueValue;
				
				if( $p != (sizeof($arrayOfValues) - 1) )
					$totalTestStr .= '&';
			
			}
			
			$actionUrl .= '?';
			$actionUrl .= $totalTestStr;
			
			$error=$http->GetRequestArguments($actionUrl,$arguments);
			
			$error=$http->Open($arguments);
			
			$log->lwrite("URL to be requested is: $actionUrl");
			
			if($error=="")
			{
				$log->lwrite("Sending HTTP request to $actionUrl");
				$error=$http->SendRequest($arguments);
				
				if($error=="")
				{
					$headers=array();
					$error=$http->ReadReplyHeaders($headers);
					if($error=="")
					{		
						$error = $http->ReadWholeReplyBody($body);
						
						if(strlen($error) == 0)
						{	
							$http->Close();
							$vulnerabilityFound = checkIfVulnerabilityFound($urlToCheck, $pageChanges, $bodyOfUrl, $log, $currentPayload, $http);
							
							if($vulnerabilityFound)
							{
								$totalTestStr = '';//Make a test string to show the user how the vulnerability was tested for
								for($p=0; $p<sizeof($arrayOfValues); $p++)
								{
									$currentPostValue = $arrayOfValues[$p];
									$currentPostValueName = $currentPostValue->getName();
									$currentPostValueValue = $currentPostValue->getValue();
									
									$totalTestStr .= $currentPostValueName;
									$totalTestStr .= '=';
									$totalTestStr .= $currentPostValueValue;
									
									if( $p != (sizeof($arrayOfValues) - 1) )
										$totalTestStr .= '&';
								
								}
								//The echo's below are for testing the function on its own i.e. requesting this script with your browser
								echo 'Broken Authentication Present!<br>Query: ' . HtmlSpecialChars($totalTestStr) . '<br>';
								echo 'Method: ' . $currentFormMethod . '<br>';
								echo 'Url: ' . HtmlSpecialChars($actionUrl) . '<br>';
								echo 'Error: Successfully Logged In with SQL injection';
								$tableName = 'test' . $testId;
							
								//Check if this vulnerability has already been found and added to DB. If it hasn't, add it to DB.
								$query = "SELECT * FROM test_results WHERE test_id = $testId AND type = 'basqli' AND method = '$currentFormMethod' AND url = '" . addslashes($actionUrl) . "' AND attack_str = '" . addslashes($totalTestStr) . "'";
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
										insertTestResult($db, $testId, 'basqli', $currentFormMethod, addslashes($actionUrl), addslashes($totalTestStr));
									}
								}
								
								break;
							}	
						}
					}
				}			
			}
			if(strlen($error))
			{
				echo "<H2 align=\"center\">Error: ",$error,"</H2>\n";
				echo 'Method: ' . $currentFormMethod . '<br>';
				echo 'Url: ' . HtmlSpecialChars($actionUrl) . '<br>';}		
			}
			else if($currentFormMethod == 'post')//Send data in body of request
			{
				//Build query string and submit it at end of URL
				if($urlOfSite[strlen($urlOfSite)-1] == '/')
					$actionUrl = $urlOfSite . $currentFormAction;
				else
					$actionUrl = $urlOfSite . '/' . $currentFormAction;
				
				$error=$http->GetRequestArguments($actionUrl,$arguments);
				
				$arguments["RequestMethod"]="POST";
				$arguments["PostValues"]= array();
				for($p=0; $p<sizeof($arrayOfValues); $p++)
				{
					$currentPostValue = $arrayOfValues[$p];
					$currentPostValueName = $currentPostValue->getName();
					$currentPostValueValue = $currentPostValue->getValue();
					

					$tempArray = array($currentPostValueName=>$currentPostValueValue);
					
					$arguments["PostValues"] = array_merge($arguments["PostValues"], $tempArray);
					
				}
				$error=$http->Open($arguments);
				$log->lwrite("URL to be requested is: $actionUrl");
				
				if($error=="")
				{
					$log->lwrite("Sending HTTP request to $actionUrl");
					$error=$http->SendRequest($arguments);

					if($error=="")
					{
						$headers=array();
						$error=$http->ReadReplyHeaders($headers);
						if($error=="")
						{
							$error = $http->ReadWholeReplyBody($body);
							
							if(strlen($error) == 0)
							{	
								$http->Close();
								$vulnerabilityFound = checkIfVulnerabilityFound($urlToCheck, $pageChanges, $bodyOfUrl, $log, $currentPayload, $http);

								if($vulnerabilityFound)
								{
									$totalTestStr = '';//Compile a test string to show the user how the vulnerability was tested for
									for($p=0; $p<sizeof($arrayOfValues); $p++)
									{
										$currentPostValue = $arrayOfValues[$p];
										$currentPostValueName = $currentPostValue->getName();
										$currentPostValueValue = $currentPostValue->getValue();
										
										$totalTestStr .= $currentPostValueName;
										$totalTestStr .= '=';
										$totalTestStr .= $currentPostValueValue;
										
										if( $p != (sizeof($arrayOfValues) - 1) )
											$totalTestStr .= '&';
									
									}
									//The echo's below are for testing the function on its own i.e. requesting this script with your browser
									echo 'Broken Authentication Present!<br>Query: ' . HtmlSpecialChars($totalTestStr) . '<br>';
									echo 'Method: ' . $currentFormMethod . '<br>';
									echo 'Url: ' . HtmlSpecialChars($actionUrl) . '<br>';
									echo 'Error: Successfully Logged In with SQL injection';

									$tableName = 'test' . $testId;
									//Check if this vulnerability has already been found and added to DB. If it hasn't, add it to DB.
									$query = "SELECT * FROM test_results WHERE test_id = $testId AND type = 'basqli' AND method = '$currentFormMethod' AND url = '" . addslashes($actionUrl) . "' AND attack_str = '" . addslashes($totalTestStr) . "'";
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
											insertTestResult($db, $testId, 'basqli', $currentFormMethod, addslashes($actionUrl), addslashes($totalTestStr));
										}
									}
									
									break;
								}
								
							}						
						}
					}
				}
				if(strlen($error))
				{
					echo "<H2 align=\"center\">Error: ",$error,"</H2>\n";
					echo 'Method: ' . $currentFormMethod . '<br>';
					echo 'Url: ' . HtmlSpecialChars($actionUrl) . '<br>';
				}			
			}
		}			
	}
}

//This function checks if the authentication was bypassed. In other words, the login was successful.
//Pass it the URL that is currently being tested, the boolean specifying if the page displayed by this URL changes at different intervals, 
//the body of the URL being tested, an object of the Logger class, the payload used
function checkIfVulnerabilityFound($urlToCheck, $pageChanges, $bodyOfUrl, $log, $currentPayload, $http)
{
	$newBodyOfUrl = "";
	$http->request_method = "GET";
	$arguments["RequestMethod"]="GET";
	$error=$http->GetRequestArguments($urlToCheck,$arguments);

	$error=$http->Open($arguments);

	if($error=="")
	{
		$log->lwrite("Sending HTTP request to $urlToCheck to check if it is different than before login attempt");
		$error=$http->SendRequest($arguments);
		
		if($error=="")
		{
			$headers=array();
			$error=$http->ReadReplyHeaders($headers);
			if($error=="")
			{
				$error = $http->ReadWholeReplyBody($body);
				
				if(strlen($error) == 0)
				{	
					$newBodyOfUrl = $body;
					echo '<br><br>';
				}
			}
		}
		$http->Close();
	}
	if(strlen($error)){
		echo "<H2 align=\"center\">Error: ",$error,"</H2>\n";
	}
	
	if(!$pageChanges)//The page displayed from this URL does not change, so check if it is changed now after login attempt
	{
		if($bodyOfUrl != $newBodyOfUrl)
		{
			echo "Body of URL $urlToCheck is different than before login attempt<br>";
			$log->lwrite("Found broken authentication vulnerability on $urlToCheck");
			$log->lwrite("Body of URL $urlToCheck is different than before login attempt. Therefore, authentication bypassed");
			return true;
		}
		else
		{
			echo "Body of URL $urlToCheck is not different than before login attempt";
		}
	}
	else //if the page displayed by the URL being tested does change at different levels, a different method must be used to identify if login was successful
	{
		//if the payload was not contained in the page, such as the login page, before but now it is, e.g. Hello 1'or'1'='1', authentication has been bypassed
		if( (!strpos($bodyOfUrl, $currentPayload)) && (strpos($newBodyOfUrl, $currentPayload)) )
		{
			$log->lwrite("Found broken authentication vulnerability on $urlToCheck");
			echo "Payload $currentPayload is now contained in $urlToCheck and was not before login attempt<br>";
			$log->lwrite("Payload $currentPayload is now contained in $urlToCheck and was not before login attempt. Therefore, authentication bypassed");
			return true;
		}
		else
		{
			echo "Payload $currentPayload is not contained in $urlToCheck and was not before login attempt<br>";
			$loggedIn = false;
			$loggedInStrings = array('Hello',
									 'Welcome',
									 'Sign out',
									 'Signout',
									 'Log out',
									 'Logout',
									 'logged in');
			
			foreach($loggedInStrings as $currentStr)
			{
				if( (!stripos($bodyOfUrl, $currentStr)) && (strpos($newBodyOfUrl, $currentStr)) )
				{
					echo "The string $currentStr is now contained in $urlToCheck and was not before login attempt<br>";
					$log->lwrite("Found broken authentication vulnerability on $urlToCheck");
					$log->lwrite("The string $currentStr is now contained in $urlToCheck and was not before login attempt. Therefore, authentication bypassed");
					return true;
				}
				else
					echo "The string $currentStr is now contained in $urlToCheck and was not before login attempt<br>";
			}
			  
		}
	}
	return false;
}

?>
