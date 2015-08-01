# WebVulScan

## Synopsis

WebVulScan is a web application vulnerability scanner. It is a web application itself written in PHP and can be used to test remote, or local, web applications for security vulnerabilities. As a scan is running, details of the scan are dynamically updated to the user. These details include the status of the scan, the number of URLs found on the web application, the number of vulnerabilities found and details of the vulnerabilities found.

After a scan is complete, a detailed PDF report is emailed to the user. The report includes descriptions of the vulnerabilities found, recommendations and details of where and how each vulnerability was exploited. 

The vulnerabilities tested by WebVulScan are:

- Reflected Cross-Site Scripting
- Stored Cross-Site Scripting
- Standard SQL Injection
- Broken Authentication using SQL Injection
- Autocomplete Enabled on Password Fields
- Potentially Insecure Direct Object References
- Directory Listing Enabled
- HTTP Banner Disclosure
- SSL Certificate not Trusted
- Unvalidated Redirects

Features:

- Crawler: Crawls a website to identify and display all URLs belonging to the website.
- Scanner: Crawls a website and scans all URLs found for vulnerabilities.
- Scan History: Allows a user to view or download PDF reports of previous scans that they performed.
- Register: Allows a user to register with the web application.
- Login: Allows a user to login to the web application.
- Options: Allows a user to select which vulnerabilities they wish to test for (all are enabled by default).
- PDF Generation: Dynamically generates a detailed PDF report.
- Report Delivery: The PDF report is emailed to the user as an attachment.

## Installation

See ReadMe file in txt and docx format for installation instructions.

## Discussion

As this project was exported from Google Code, previously found problems and solutions are available at: 
- https://groups.google.com/forum/#!forum/webvulscan
- https://code.google.com/p/webvulscan/issues/list

For any other issues or feedback, please contact webvulscan@gmail.com

## License

GNU GPL v3
