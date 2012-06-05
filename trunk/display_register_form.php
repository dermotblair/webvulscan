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
$displayForm = true;

if(isset($_SESSION['username']))
{
	echo 'You are currently logged in. You must be logged out to create an account';
	$displayForm = false;
}
else
{
	if(isset($_POST['regusername']) || isset($_POST['regpassword']) || isset($_POST['regpassword2']) ||
			 isset($_POST['email']))
	{
		if(empty($_POST['regusername']) || empty($_POST['regpassword']) || empty($_POST['regpassword2']) ||
			 empty($_POST['email']))
		{
			echo 'One or more input fields in the form were empty. You must fill in all input fields';	
		}
		else if($_POST['regpassword'] != $_POST['regpassword2'])
		{
			echo 'The confirmation of the password does not match the first password entered';
		}
		else if(!ctype_alnum($_POST['regusername']) || !ctype_alnum($_POST['regpassword']))//only hav to check the first password as the second password entered is equal to this (checked above)
		{
			echo 'Username and passwords must be alphanumeric. Please try again';
		}
		else if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
		{
			echo 'The email address entered does not appear to be a valid email. If it is a valid email address, please contact our administrator';
		}
		else//everything should be ok if we make it to here
		{
			$username = $_POST['regusername'];
			$password = $_POST['regpassword'];
			$email = $_POST['email'];

			if(connectToDb($db))
			{
				$query = "SELECT * FROM users WHERE username = '$username'";
				$result = $db->query($query);
				if($result)
				{
					$numRows = $result->num_rows;
					if($numRows > 0)
						echo 'Sorry, this username already exists. Please try again';
					else
					{
						$query = "SELECT * FROM users WHERE email = '$email'";
						$result = $db->query($query);
						if($result)
						{
							$numRows = $result->num_rows;
							if($numRows > 0)
								echo 'Sorry, an account already exits with this email address. Please try again';
							else
							{
								$query = "INSERT INTO users VALUE('$username',SHA1('$password'),'$email')";
								$result = $db->query($query);
								if($result)
								{
									echo 'Congradulations! You have successfully registered, please login to enjoy our features';
									$displayForm = false;
								}
								else
									echo 'There was a problem connecting to the database. Please contact the administrator if problem persists';
							}
						}
						else
							echo 'There was a problem connecting to the database. Please contact the administrator if problem persists';
					}
				}
				else
					echo 'There was a problem connecting to the database. Please contact the administrator if problem persists';
			
			}
			else
				echo 'There was a problem connecting to the database. Please contact the administrator if problem persists';
	
		}
	}
}
if($displayForm)
	require_once($currentDir . 'register_form.html');
?>
