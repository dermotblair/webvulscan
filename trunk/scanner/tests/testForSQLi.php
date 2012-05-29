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

//This function checks the URL for forms and attempts to submit SQL strings into each value of the forms.
//It also tries to exploit the vulnerability using the parameters of the URL, if there are any.

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

testForSQLi('http://127.0.0.1/mutillidae/index.php?do=toggle-security&page=user-info.php',''http://127.0.0.1/mutillidae/',500);//Just for testing
*/

function testForSQLi($urlToCheck, $urlOfSite, $testId){

connectToDb($db);
updateStatus($db, "Testing $urlToCheck for SQL Injection...", $testId);

$log = new Logger();
$log->lfile('logs/eventlogs');

$log->lwrite("Starting SQL Injection test function on $urlToCheck");

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

$log->lwrite("Successfully got contents from $urlToCheck");

//Defintion of all payloads used and warnings to examine for
$arrayOfPayloads = array( "'",
						  '"',
						  ';',
						  ')',
						  '(',
						  '.',
						  '--');  //specified in webfuzz library (lib.webfuzz.js) from WebSecurify

//From lib.webfuzz, some added by myself
//The function checks for these errors after a payload is submitted
$arrayOfSQLWarnings = array(		
        "supplied argument is not a valid MySQL", //MySQL
		"mysql_fetch_array\\(\\)",
		"on MySQL result index",
		"You have an error in your SQL syntax;",
		"You have an error in your SQL syntax near",
		"MySQL server version for the right syntax to use",
		"\\[MySQL\\]\\[ODBC",
		"Column count doesn't match",
		"the used select statements have different number of columns",
		"Table '[^']+' doesn't exist",
		"DB Error: unknown error",
		":[\s]*mysql",
		"mysql_fetch",
		"System\\.Data\\.OleDb\\.OleDbException", //MS SQL
		"\\[SQL Server\\]",
		"\\[Microsoft\\]\\[ODBC SQL Server Driver\\]",
		"\\[SQLServer JDBC Driver\\]",
		"\\[SqlException",
		"System.Data.SqlClient.SqlException",
		"Unclosed quotation mark after the character string",
		"'80040e14'",
		"mssql_query\\(\\)",
		"odbc_exec\\(\\)",
		"Microsoft OLE DB Provider for ODBC Drivers",
		"Microsoft OLE DB Provider for SQL Server",
		"Incorrect syntax near",
		"Syntax error in string in query expression",
		"ADODB\\.Field \\(0x800A0BCD\\)<br>",
		"Procedure '[^']+' requires parameter '[^']+'",
		"ADODB\\.Recordset'",
		"Microsoft SQL Native Client error",
		"Unclosed quotation mark after the character string", 
		"SQLCODE", //DB2"
		"DB2 SQL error:",
		"SQLSTATE",
		"Sybase message:", //Sybase
		"Syntax error in query expression", //Access
		"Data type mismatch in criteria expression.",
		"Microsoft JET Database Engine",
		"\\[Microsoft\\]\\[ODBC Microsoft Access Driver\\]",
		"(PLS|ORA)-[0-9][0-9][0-9][0-9]", //Oracle
		"PostgreSQL query failed:", //PostGre
		"supplied argument is not a valid PostgreSQL result",
		"pg_query\\(\\) \\[:",
		"pg_exec\\(\\) \\[:",
		"com\\.informix\\.jdbc", //Informix
		"Dynamic Page Generation Error:",
		"Dynamic SQL Error",
		"\\[DM_QUERY_E_SYNTAX\\]", //DML
		"has occurred in the vicinity of:",
		"A Parser Error \\(syntax error\\)",
		"java\\.sql\\.SQLException",//Java
		"\\[Macromedia\\]\\[SQLServer JDBC Driver\\]" //Coldfusion	
		);
	
//First check does the URL passed into this function contain parameters and submit payloads as those parameters if it does
$parsedUrl = parse_url($urlToCheck);
$log->lwrite("Check if $urlToCheck contains parameters");
if($parsedUrl)
{
	if(isset($parsedUrl['query']))
	{
		$log->lwrite("$urlToCheck does contain parameters");
		
		$scheme = $parsedUrl['scheme'];
		$host = $parsedUrl['host'];
		$path = $parsedUrl['path'];
		
		$query = $parsedUrl['query'];
		parse_str($query,$parameters);
		$originalQuery = $query;
		
		foreach($arrayOfPayloads as $currentPayload)
		{
			
			$http = new http_class;
			$http->timeout=0;
			$http->data_timeout=0;
			//$http->debug=1;
			$http->user_agent="Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
			$http->follow_redirect=1;
			$http->redirection_limit=5;
			$http->setTestId($testId);
		
			foreach($parameters as $para)
			{
				$query = $originalQuery;
				
				$newQuery = str_replace($para, $currentPayload, $query);
				$query = $newQuery;
				
				$testUrl = $scheme . '://' . $host . $path . '?' . $query;
				
				$log->lwrite("URL to be requested is: $testUrl");
			
				$error=$http->GetRequestArguments($testUrl,$arguments);
								
				$error=$http->Open($arguments);
				if($error=="")
				{
					$log->lwrite("Sending HTTP request to $testUrl");
					
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
								$vulnerabilityFound = false;

								for($warningIndex=0; $warningIndex < sizeof($arrayOfSQLWarnings); $warningIndex++)
								{
									$regularExpression = "/$arrayOfSQLWarnings[$warningIndex]/";

									if(preg_match($regularExpression,$body))
									{
										$log->lwrite("Found regular expression: $regularExpression, in body of HTTP response");
										$vulnerabilityFound = true;
										break;
									}
								}
									
								if($vulnerabilityFound)
								{
									
									echo '<br>SQL Injection Present!<br>Query: ' . HtmlSpecialChars($urlToCheck) . '<br>';
									echo 'Method: GET <br>';
									echo 'Url: ' . HtmlSpecialChars($testUrl) . '<br>';
									echo 'Error: ' . $regularExpression . '<br>';
									$tableName = 'test' . $testId;
								
									//Check if this vulnerability has already been found and added to DB. If it hasn't, add it to DB.
									$sql = "SELECT * FROM test_results WHERE test_id = $testId AND type = 'sqli' AND method = 'get' AND url = '" . addslashes($testUrl) . "' AND attack_str = '" . addslashes($query) . "'";
									$result = $db->query($sql);
									if(!$result)
										$log->lwrite("Could not execute query $sql");
									else
									{
										$log->lwrite("Successfully executed query $sql");
										$numRows = $result->num_rows;
										if($numRows == 0)
										{	
											$log->lwrite("Number of rows is $numRows for query: $sql");
											insertTestResult($db, $testId, 'sqli', 'get', addslashes($testUrl), addslashes($query));
										}
										
										$result->free();
									}
									$http->Close();
									break 2;
								}
							}
						}
					}
					$http->Close();
				}
				if(strlen($error))
					echo "<H2 align=\"center\">Error: ",$error,"</H2>\n";
						
			}
		}
	}
}
else 
	$log->lwrite("Could not parse malformed URL: $urlToCheck");

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
		$firstIndexOfSlash = strpos($urlToCheck, '/', $strLengthSite-1);
		$formAction = substr($urlToCheck, $firstIndexOfSlash+1, $strLengthUrl);
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

//Begin testing each of the forms
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
	
	echo sizeof($arrayOfInputFields) . "<br>";
	for($j=0; $j<sizeof($arrayOfInputFields); $j++)
	{
		$currentInput = $arrayOfInputFields[$j];
		$currentInputIdOfForm = $currentInput->getIdOfForm();
		$currentInputNameOfForm = $currentInput->getNameOfForm();
		$currentInputFormNum = $currentInput->getFormNum();
		
		//Check if the current input field belongs to the current form and add to array if it does
		if($currentFormNum == $currentInputFormNum)
		{
			array_push($arrayOfCurrentFormsInputs, $currentInput);
		}
	}
	
	$log->lwrite("Beginning testing input fields of form on $postUrl: $currentFormId $currentFormName $currentFormMethod $currentFormAction");	
	
	for($k=0; $k<sizeof($arrayOfCurrentFormsInputs); $k++)
	{
		echo sizeof($arrayOfCurrentFormsInputs) . '<br>';
		
		for($plIndex=0; $plIndex<sizeof($arrayOfPayloads); $plIndex++)
		{
			$currentFormInput = $arrayOfCurrentFormsInputs[$k];
			$currentFormInputName =  $currentFormInput->getName();
			$currentFormInputType =  $currentFormInput->getType();
			$currentFormInputValue =  $currentFormInput->getValue();
			
			if($currentFormInputType!= 'reset')
			{
				$http = new http_class;
				$http->timeout=0;
				$http->data_timeout=0;
				//$http->debug=1;
				$http->user_agent="Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
				$http->follow_redirect=1;
				$http->redirection_limit=5;
				$http->setTestId($testId);
				
				$defaultStr = 'Abc123';
			
				$arrayOfValues = array(); //Array of PostOrGetObject objects
	
				//Get the other input values and set them equal to the default string
				$otherInputs = array();
				
				for($l=0; $l<sizeof($arrayOfCurrentFormsInputs); $l++)
				{
					if($currentFormInput->getName() != $arrayOfCurrentFormsInputs[$l]->getName())
					{
						array_push($otherInputs, $arrayOfCurrentFormsInputs[$l]);
					}
				}
					
				$postObject = new PostOrGetObject($currentFormInputName, $arrayOfPayloads[$plIndex]);
				$log->lwrite("Submitting payload: $arrayOfPayloads[$plIndex], to input field: $currentFormInputName");
				//Add current input and other to array of post values and set their values
				array_push($arrayOfValues, $postObject);
				
				for($m=0; $m<sizeof($otherInputs); $m++)
				{
					$currentOther = $otherInputs[$m];
					$currentOtherType = $currentOther->getType();
					$currentOtherName = $currentOther->getName();
					$currentOtherValue = $currentOther->getValue();
			
					if($currentOtherType == 'text' || $currentOtherType == 'password')
					{
						$postObject = new PostOrGetObject($currentOtherName, $defaultStr);
						array_push($arrayOfValues, $postObject);
					}
					else if($currentOtherType == 'checkbox' || $currentOtherType == 'submit')
					{
						$postObject  = new PostOrGetObject($currentOtherName, $currentOtherValue);
						array_push($arrayOfValues, $postObject);
					}
					else if($currentOtherType == 'radio')
					{
						$postObject  = new PostOrGetObject($currentOtherName, $currentOtherValue);
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
				echo '<br><br>';
			
				if($currentFormMethod == 'get')
				{
					//Build query string and submit it at end of URL
					if($urlOfSite[strlen($urlOfSite)-1] == '/')
						$actionUrl = $urlOfSite . $currentFormAction;
					else
						$actionUrl = $urlOfSite . '/' . $currentFormAction;
					
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
									
					if(strpos($actionUrl, '?')!==false)//url may be something like domain.com?id=111 so don't want to add another question mark if it is
						$actionUrl .= '&';
					else
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
									$vulnerabilityFound = false;
								
									for($warningIndex=0; $warningIndex < sizeof($arrayOfSQLWarnings); $warningIndex++)
									{
										$regularExpression = "/$arrayOfSQLWarnings[$warningIndex]/";

										if(preg_match($regularExpression,$body))
										{
											$log->lwrite("Found regular expression: $regularExpression, in body of HTTP response");
											$vulnerabilityFound = true;
											break;
										}

									}
									
									if($vulnerabilityFound)
									{
										//If the body returned from the request contains ones of the errors, the
										//SQL Injection vulnerabiltiy is present
										
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
										
										$currentFormMethod = strtolower($currentFormMethod);
										
										echo 'SQL Injection Present!<br>Query: ' . HtmlSpecialChars($totalTestStr) . '<br>';
										echo 'Method: ' . $currentFormMethod . '<br>';
										echo 'Url: ' . HtmlSpecialChars($actionUrl) . '<br>';
										echo 'Error: ' . $regularExpression . '';
										$tableName = 'test' . $testId;
									
										//Check if this vulnerability has already been found and added to DB. If it hasn't, add it to DB.
										$query = "SELECT * FROM test_results WHERE test_id = $testId AND type = 'sqli' AND method = '$currentFormMethod' AND url = '" . addslashes($actionUrl) . "' AND attack_str = '" . addslashes($totalTestStr) . "'";
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
												insertTestResult($db, $testId, 'sqli', $currentFormMethod, addslashes($actionUrl), addslashes($totalTestStr));
											}
											
											$result->free();
										}
										
										$http->Close();
										break;
									}	
								}
							}
						}
						$http->Close();
					}
					if(strlen($error))
						echo "<H2 align=\"center\">Error: ",$error,"</H2>\n";
				
				}
				else if($currentFormMethod == 'post')//Send data in body of request
				{
					//Start sending requests with the values in the post values array
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
									$vulnerabilityFound = false;
									for($warningIndex=0; $warningIndex < sizeof($arrayOfSQLWarnings); $warningIndex++)
									{
										$regularExpression = "/$arrayOfSQLWarnings[$warningIndex]/";
										
										if(preg_match($regularExpression,$body))
										{
											$log->lwrite("Found regular expression: $regularExpression, in body of HTTP response");
											$vulnerabilityFound = true;
											break;
										}
									}
									
									if($vulnerabilityFound)
									{
										//If the body returned from the request contains one of the errors specified, the
										//SQL Injection vulnerabiltiy is present
										
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
										
										$currentFormMethod = strtolower($currentFormMethod);
										
										echo 'SQL Injection Present!<br>Query: ' . HtmlSpecialChars($totalTestStr) . '<br>';
										echo 'Method: ' . $currentFormMethod . '<br>';
										echo 'Url: ' . HtmlSpecialChars($actionUrl) . '<br>';
										echo 'Error: ' . $regularExpression . '';

										$tableName = 'test' . $testId;
										//Check if this vulnerability has already been found and added to DB. If it hasn't, add it to DB.
										$query = "SELECT * FROM test_results WHERE test_id = $testId AND type = 'sqli' AND method = '$currentFormMethod' AND url = '$actionUrl' AND attack_str = '" . addslashes($totalTestStr) . "'";
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
												insertTestResult($db, $testId, 'sqli', $currentFormMethod, $actionUrl, addslashes($totalTestStr));
											}
											
											$result->free();
										}
										
										$http->Close();
										break;
									}	
								}
							}
						}
						$http->Close();
					}
					if(strlen($error))
						echo "<H2 align=\"center\">Error: ",$error,"</H2>\n";
				}
			}
		}
	}		
}
}
?>