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
session_start();
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');
?>
<!DOCTYPE html>
<head>
<title>WebVulScan</title>
<meta charset="windows-1252">
<link rel="shortcut icon" href="images/favicon.gif" />
<link rel="stylesheet" type="text/css" href="style.css" />
<script type="text/javascript" src="js/swfobject/swfobject.js"></script>
<script type="text/javascript">
var flashvars = {};
flashvars.xml = "config.xml";
flashvars.font = "font.swf";
var attributes = {};
attributes.wmode = "transparent";
attributes.id = "slider";
swfobject.embedSWF("cu3er.swf", "cu3er-container", "960", "270", "9", "expressInstall.swf", flashvars, attributes);
</script>
<script type="text/javascript" src="jquery-1.6.4.js"></script>
</head>
<body>
<!--Header Begin-->
<div id="header">
  <div class="center">
    <div id="logo"><a href="#">WebVulScan</a></div>
    <!--Menu Begin-->
	<div id="menu">
	<?php require_once($currentDir . 'session_control.php'); ?>
	</div>
    <div id="menu">
      <ul>
        <li><a class="active" href="index.php"><span>Home</span></a></li>
        <li><a href="about.php"><span>About</span></a></li>
		<li><a href="crawler.php"><span>Crawler</span></a></li>
		<li><a href="scanner.php"><span>Scanner</span></a></li>
		<li><a href="history.php"><span>Scan History</span></a></li>
      </ul>
    </div>
    <!--Menu END-->
  </div>
</div>
<!--Header END-->
<!--Toprow Begin-->
<div id="toprow">
  <div class="center">
    <div id="cubershadow">
      <div id="cu3er-container"> <a href="http://www.adobe.com/go/getflashplayer"> <img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="" /> </a> </div>
    </div>
  </div>
</div>
<!--Toprow END-->

<!--BottomRow Begin-->
<div id="bottomrow">
  <div class="textbox">
    <h1>WebVulScan - The Web Application Vulnerability Scanner</h1>
    <p>WebVulScan firstly crawls the target website to identify all URLs belonging to the website. It tests each URL for a number of vulnerabilities and emails you a detailed PDF report once the scan is complete.</p></div>
</div>
<!--BottomRow END-->
<!--Footer Begin-->
<div id="footer">
  <div class="foot"> <span>Calliope</span> by <a href="http://www.towfiqi.com">Towfiq I.</a> is licensed under a <a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0 Unported License.</a> </div>
</div>
<!--Footer END-->
</body>
</html>
