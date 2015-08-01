WebVulScan is a web application vulnerability scanner. It is a web application itself written in PHP and can be used to test remote, or local, web applications for security vulnerabilities. As a scan is running, details of the scan are dynamically updated to the user. These details include the status of the scan, the number of URLs found on the web application, the number of vulnerabilities found and details of the vulnerabilities found.

After a scan is complete, a detailed PDF report is emailed to the user. The report includes descriptions of the vulnerabilities found, recommendations and details of where and how each vulnerability was exploited.

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

This software was developed, and should only be used, entirely for ethical purposes. Running security testing tools such as this on a website (web application) could damage it. In order to stay ethical, you must ensure you have permission of the owners before testing a website (web application). Testing the security of a website (web application) without authorisation is unethical and against the law in many countries.