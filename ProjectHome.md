WebVulScan is a web application vulnerability scanner. It is a web application itself written in PHP and can be used to test remote, or local, web applications for security vulnerabilities. As a scan is running, details of the scan are dynamically updated to the user. These details include the status of the scan, the number of URLs found on the web application, the number of vulnerabilities found and details of the vulnerabilities found.

After a scan is complete, a detailed PDF report is emailed to the user. The report includes descriptions of the vulnerabilities found, recommendations and details of where and how each vulnerability was exploited.

<img src='http://webvulscan.googlecode.com/svn-history/r12/wiki/images/Scanner1.JPG'>

The vulnerabilities tested by WebVulScan are:<br>
<ul>
<li>Reflected Cross-Site Scripting</li>
<li>Stored Cross-Site Scripting</li>
<li>Standard SQL Injection</li>
<li>Broken Authentication using SQL Injection</li>
<li>Autocomplete Enabled on Password Fields</li>
<li>Potentially Insecure Direct Object References</li>
<li>Directory Listing Enabled</li>
<li>HTTP Banner Disclosure</li>
<li>SSL Certificate not Trusted</li>
<li>Unvalidated Redirects</li>
</ul>

Features:<br>
<ul>
<li>Crawler: Crawls a website to identify and display all URLs belonging to the website.</li>
<li>Scanner: Crawls a website and scans all URLs found for vulnerabilities.</li>
<li>Scan History: Allows a user to view or download PDF reports of previous scans that they performed.</li>
<li>Register: Allows a user to register with the web application.</li>
<li>Login: Allows a user to login to the web application.</li>
<li>Options: Allows a user to select which vulnerabilities they wish to test for (all are enabled by default).</li>
<li>PDF Generation: Dynamically generates a detailed PDF report.</li>
<li>Report Delivery: The PDF report is emailed to the user as an attachment.</li>
</ul>

<h2>Version History</h2>
<h3>8th June 2012 - Version 0.12</h3>
<ul>
<li> Emailing PDF report is now optional. Therefore, you can just view it in your scan history if you wish instead of having it emailed to you.</li>
<li> Crawling a URL at the start of the scan is now optional. Therefore, you can now test a single webpage for the various vulnerabilties instead of scanning an entire website.</li>
<li> Issues fixed that some users were having when running WebVulScan on Linux (static path references and case sensitivity). Now tested on Windows (XAMPP 1.7.4 running on Vista) and Linux (XAMPP 1.7.4 running on Ubuntu 12.04).</li>
<li> Added information about Linux permissions to instructions.</li>
<li> Instructions now in .docx and .txt format</li>
</ul>

<h3>30th April 2012 - Version 0.11</h3>
<ul>
<li> First release of WebVulScan.</li>
</ul>

<h2>Third Party Software Used</h2>
<a href='http://code.google.com/p/webvulscan/wiki/ThirdPartySoftwareUsed'>Click here</a> to see the other projects incorporated into WebVulScan<br>
<br>
This software was developed, and should only be used, entirely for ethical purposes. Running security testing tools such as this on a website (web application) could damage it. In order to stay ethical, you must ensure you have permission of the owners before testing a website (web application). Testing the security of a website (web application) without authorisation is unethical and against the law in many countries.