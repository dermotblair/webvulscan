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
$loginMsg = '';

if(isset($_GET['action']))
{
	$action = $_GET['action'];
	if($action == 'logout')
	{
		if(isset($_SESSION['username']))
		{
			unset($_SESSION['username']);
			$loginMsg = 'You are now logged out';
		}
		else
			$loginMsg = 'You are currently not logged in';
	
	}
}

//Check if user has just made a login attempt
if(isset($_POST['email']) && isset($_POST['password']))
{
	$email = $_POST['email'];
	$password = $_POST['password'];
	
	$continueLogin = true;
	
	if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !ctype_alnum($password))
	{
		$loginMsg = 'Invalid email or password. Please try again';
		$continueLogin = false;
	}
	
	if(connectToDb($db) && $continueLogin)
	{
		$query = "SELECT * FROM users WHERE email = '$email' AND password = SHA1('$password')";
		$result = $db->query($query);
		if($result)
		{
			$numRows = $result->num_rows;
			if($numRows == 0)
				$loginMsg = 'Invalid email or password. Please try again';
			else
			{
				$row = $result->fetch_object();
				$username = $row->username;
				$_SESSION['username'] = $username;
				$_SESSION['email'] = $email;
				$loginMsg = 'You have successfully logged in';
			}
		}
		else
		{
			$loginMsg = 'There was a problem checking your credentials. Please contact administrator if the problem persists';
		}
	}
}


//Check if user is logged
if(isset($_SESSION['username']))
{
	echo '<h5>Welcome ' . $_SESSION['username'] . ' | <a href="logout.php?action=logout">Logout</a></h5>';
	if(!isset($loginMsg))
		$loginMsg = 'You are currently logged in';
}
else
{
	require_once($currentDir . 'login_form.html');
}
