How to Deploy the Web Application


This software requires the following are installed:
•	A web server capable of running PHP web applications such as Apache.
•	MySQL.
•	PHP.

If you run into any problems during the instructions, please refer to the discussion group (http://groups.google.com/group/webvulscan) or you can email webvulscan@gmail.com 

1. 	Place the folder containing the source code into the folder that your web server looks for to serve on your domain. In Apache this is the “htdocs” folder.
	a.	Using your browser, request “localhost/webvulscan_vx.xx”, where “webvulscan_vx.xx” is the folder containing the source code, and you will be brought to the homepage of the web application vulnerability scanner.
	b.	Import the database named “webvulscan.sql”, which is contained in the source code folder into your MySQL database.
	c.	The database credentials that the scanner is using are the “root” user with no password. If you want to change this it can be edited in “webvulscan_vx.xx/scanner/functions/databaseFunctions.php” in the connectToDb() function. The second and third parameter passed into the mysqli constructor are the username and password of a MySQL database user.  e.g. “root” and “”.
	d.	For whatever user you are using in the connectToDb() function, you must ensure there is a corresponding database user in the database and they have sufficient privileges to read/write from/to the webvulscan database.
	e.	If you are running this on Linux, you must ensure the application has permissions to write to the logs folders and the reports folder.
		i.	This can be done using the “chmod” command.
		ii.	Using the terminal, cd (change directory) to the “crawler” folder and enter “sudo chmod -R 777 logs/”.
		iii.	Then cd to the “scanner” folder and enter “sudo chmod -R 777 logs/”.
		iv.	Also, when in the scanner folder, enter “sudo chmod -R 777 reports/”.

2.	If users are to receive PDF reports by email, PHP’s mail() function must be able to send emails. If you do not have email functionality setup on your web server, this step will guide you on how to route the emails through a Gmail account. This is not an essential requirement as users can view and download PDF reports using the scan history feature.
	a.	Setting up an email server can be quite complex and time consuming so a simpler solution is to use Gmail. A Gmail account can be used by the web application to send emails from. 
	b.	Visit gmail.com and create an account. Users of the web application will then receive scan reports from this email address. Take note of your email address and password.
	c.	Now the application “sendmail”, with TLS support, must be installed and configured to route outgoing emails through the Gmail account. The sendmail zip file can be downloaded here: http://www.glob.com.au/sendmail/sendmail.zip 
	d.	Once sendmail is installed, open the sendmail.ini file. You need to change the settings to the following:
		i.	smtp_server=smtp.gmail.com
		ii.	smtp_port=587 
		iii.	smtp_ssl=auto
		iv.	error_logfile=error.log
		v.	auth_username=youremail@gmail.com
		vi.	auth_password=yourpassword
		vii.	pop3_server=
		viii.	pop3_username=
		ix.	pop3_password=
		x.	force_sender= youremail@gmail.com
		xi.	force_recipient=
		xii.	hostname=
	e.	All other settings should be commented by default with a semi colon.
	f.	Now open the file your “php.ini" file with a text editor and edit the following:
		i.	Under the “[mail function]” section, comment everything out in that section, using a semi colon, apart from “sendmail_path” and “mail.add_x_header”. 
		ii.	Therefore you should probably have to comment out "SMTP = ..." and "smtp_port = ..." and you should have to uncomment "sendmail_path = ...".
		iii.	Set "sendmail_path" equal to the location of your sendmail.exe file (e.g. "\"C:\xampp\sendmail\sendmail.exe\" -t") if it is not already set to that. 
		iv.	Set "mail.add_x_header" equal to Off if it is not already set to Off.
		v.	Save php.ini
	g.	Restart the web server.
	h.	You should now be able to send emails using PHP’s mail function. 

3.	Other PHP settings also need to be configured by editing the php.ini file.
	a.	The following settings need to be changed:
		i.	memory_limit is set equal to 128M, you may need to change this to a higher value if you are running multiple scans simultaneously.
		ii.	You need to enable the “curl” and “openssl” extensions. Under the Extensions section, ensure “extension=php_curl.dll” and "extension=php_openssl.dll" are there and are not commented out. If they are not there, add them. If they are there and are commented out by a semi colon in front of them, remove the semi colon to uncomment them.
		iii.	Restart the web server.

4.	The scanner should now be ready for use. How to use it:
	a.	Access the scanner and register a user by selecting the Register tab and entering a user’s details.
	b.	Login as the user by selecting the Login tab and entering an email address and password.
	c.	To crawl a website and display all URLs belonging to the website, select the Crawler tab.
		i.	Enter a URL to crawl and click “Start Crawl”.
	d.	To scan a website, select the Scanner tab.
		i.	Enter a URL to scan and click “Start Scan”.
		ii.	Before starting a scan, if you wish to disable some vulnerability tests, select the Options link and uncheck any vulnerabilities you wish to disable. All vulnerability tests are enabled by default.
	e.	To View the PDF reports of previous scans you performed, select Scan History.
		i.	Select the “View” link beside a scan to display the PDF report that was generated for that scan. PDF reports are not generated 	crawls, only for scans.
