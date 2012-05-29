-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 18, 2012 at 06:08 
-- Server version: 5.5.8
-- PHP Version: 5.3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `webvulscan`
--

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE IF NOT EXISTS `tests` (
  `id` int(11) NOT NULL,
  `status` text NOT NULL,
  `numUrlsFound` int(11) NOT NULL,
  `type` text NOT NULL,
  `num_requests_sent` int(11) NOT NULL,
  `start_timestamp` bigint(20) NOT NULL,
  `finish_timestamp` bigint(20) NOT NULL,
  `scan_finished` bit(1) NOT NULL,
  `url` text NOT NULL,
  `username` text NOT NULL,
  `urls_found` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `id_3` (`id`),
  UNIQUE KEY `start_timestamp_2` (`start_timestamp`),
  UNIQUE KEY `finish_timestamp_2` (`finish_timestamp`),
  UNIQUE KEY `id_5` (`id`),
  UNIQUE KEY `start_timestamp_4` (`start_timestamp`),
  UNIQUE KEY `finish_timestamp_4` (`finish_timestamp`),
  UNIQUE KEY `id_7` (`id`),
  UNIQUE KEY `start_timestamp_6` (`start_timestamp`),
  UNIQUE KEY `finish_timestamp_6` (`finish_timestamp`),
  KEY `id_2` (`id`),
  KEY `numUrlsFound` (`numUrlsFound`),
  KEY `num_requests_sent` (`num_requests_sent`),
  KEY `start_timestamp` (`start_timestamp`),
  KEY `finish_timestamp` (`finish_timestamp`),
  KEY `scan_finished` (`scan_finished`),
  KEY `id_4` (`id`),
  KEY `numUrlsFound_2` (`numUrlsFound`),
  KEY `num_requests_sent_2` (`num_requests_sent`),
  KEY `start_timestamp_3` (`start_timestamp`),
  KEY `finish_timestamp_3` (`finish_timestamp`),
  KEY `scan_finished_2` (`scan_finished`),
  KEY `id_6` (`id`),
  KEY `numUrlsFound_3` (`numUrlsFound`),
  KEY `num_requests_sent_3` (`num_requests_sent`),
  KEY `start_timestamp_5` (`start_timestamp`),
  KEY `finish_timestamp_5` (`finish_timestamp`),
  KEY `scan_finished_3` (`scan_finished`),
  KEY `id_8` (`id`),
  KEY `numUrlsFound_4` (`numUrlsFound`),
  KEY `num_requests_sent_4` (`num_requests_sent`),
  KEY `start_timestamp_7` (`start_timestamp`),
  KEY `finish_timestamp_7` (`finish_timestamp`),
  KEY `scan_finished_4` (`scan_finished`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tests`
--


-- --------------------------------------------------------

--
-- Table structure for table `test_results`
--

CREATE TABLE IF NOT EXISTS `test_results` (
  `test_id` int(11) NOT NULL,
  `type` text NOT NULL,
  `method` text NOT NULL,
  `url` text NOT NULL,
  `attack_str` text NOT NULL,
  KEY `test_id` (`test_id`),
  KEY `test_id_2` (`test_id`),
  KEY `test_id_3` (`test_id`),
  KEY `test_id_4` (`test_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `test_results`
--


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `username` text NOT NULL,
  `password` text NOT NULL,
  `email` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--


-- --------------------------------------------------------

--
-- Table structure for table `vulnerabilities`
--

CREATE TABLE IF NOT EXISTS `vulnerabilities` (
  `id` text NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `solution` text NOT NULL,
  `priority` text NOT NULL,
  `priority_num` int(11) NOT NULL COMMENT 'This number is used for sorting the vulnerabilities for the PDF report',
  PRIMARY KEY (`priority_num`),
  UNIQUE KEY `priority_num` (`priority_num`),
  UNIQUE KEY `priority_num_3` (`priority_num`),
  UNIQUE KEY `priority_num_5` (`priority_num`),
  KEY `priority_num_2` (`priority_num`),
  KEY `priority_num_4` (`priority_num`),
  KEY `priority_num_6` (`priority_num`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `vulnerabilities`
--

INSERT INTO `vulnerabilities` (`id`, `name`, `description`, `solution`, `priority`, `priority_num`) VALUES
('autoc', 'Autocomplete not disabled on sensitive input fields', 'Autocomplete occurs when the browser caches data, such as a user\\''s username and password for an application, so the user will not have to enter them any time they access the application. Forms that process sensitive data such as passwords should always have autocomplete disabled. If an attacker gains access to a user\\''s browser cache, he could easily obtain the sensitive information which may be saved in plaintext.', 'Disable the autocomplete attribute of input fields that hold sensitive data. This can be done by placing autocomplete=\\"off\\" inside the tags of the input field. You can also disable autocomplete for an entire form by placing it inside the tags of the form.', 'Low', 1),
('bannerdis', 'HTTP Banner Disclosure', 'The application discloses information about the technologies used such as the web server, operating system, cryptography tools, or programming languages. An attacker could identify vulnerabilities in these technologies and use them to exploit the server, therefore, potentially exploiting the application.', 'You can disable the server from disclosing this information to users. This is typically done by editing the configuration files of the various technologies and then restarting the system.', 'Medium', 2),
('sslcert', 'SSL certificate is not trusted', 'The web application is using a SSL certificate which has been checked against Mozilla\\''s bundle of X.509 certificates of public Certificate Authorities and cannot be found. Therefore, the certificate cannot be validated and is not trusted.', 'Ensure the SSL certificate has been issued by a trusted authority.', 'Medium', 3),
('idor', '(Potentially Insecure) Direct Object References', 'Exposing a reference to an internal implementation object is known as a direct object reference. An example of an internal implementation object would be a file, directory, or database key. If the reference can be edited by a user and sufficient control is not in place, the user could manipulate the reference and possibly access unauthorized resources. <br><br>\r\nFor example, a URL like the following is exposing a direct object reference: http://www.example.com/displayFile.php?file=stats.txt. However, a malicious user could replace the file name and re-request the URL again in order to try and obtain system passwords. For example: http://www.example.com/displayFile.php?file=/../../../../etc/passwd<br><br>\r\nAutomated tools cannot typically identify such flaws as they cannot recognize what requires protection and what is safe or unsafe. Therefore, this result is only indicating that values which look like direct object references are exposed and they may be insecure.\r\n', 'Use indirect object references. For example, using the above scenario, pass an integer to the URL and this could be mapped to a file name once the request is made. If direct object references must be used, have an access control check in place to ensure the user is authorized to view the requested resource.', 'High', 4),
('unredir', 'Unvalidated Redirects', 'The application is redirecting the user to a page based on user-controllable data that is not validated correctly. An example of this would be a link that requests the following URL: http://www.example.com?redirect.php?redirect=user_can_replace_this.html. This could be edited to http://www.example.com?redirect.php?redirect=http://www.malicous-site.com, and if not validated correctly, it will redirect to the malicious site. Links like the latter could then be emailed to potential victims and they may click on them with no hesitation as www.example.com may be a trusted domain.', 'The target site that the user is being redirected to should not be exposed. If there is no way around this or it is simply too much effort to edit this design, ensure the user-controllable data is validated before redirecting to it. One countermeasure is to maintain a list of safe URLs that can be redirected to and check the user-controllable value against this list before performing the redirect. <br><br>\r\nAnother good countermeasure is to pass an integer to the URL that is redirecting. For example, http://www.example.com?redirect.php?redirect=3. This integer acts as an array index and an array of safe URLs is maintained by the web applications. ', 'High', 5),
('dirlist', 'Directory Listing Enabled', 'The contents of one or more directories can be viewed by web users. Therefore, when a user requests a directory such as http://www.example.com/directoryname/ using their browser, a list of all files and directories contained in the requested directory will be displayed to the user. <br><br>\r\nThis could possibly expose sensitive information such as executables, text files, documentation, and installation and configuration files. An attacker could use these to map out the server\\''s directory structure and identify potentially vulnerable files or applications.', 'This can be a high risk vulnerability. This is typically enabled in the server\\''s configuration file but can sometimes arise from a vulnerability in particular applications. You can eliminate this vulnerability by disabling directory listing in the server\\''s configuration file and restart the server. The location and name of this file differs depending on what web server you are using.', 'High', 6),
('rxss', 'Reflected Cross-Site Scripting', 'Cross-Site Scripting (XSS) is a vulnerability that allows code to be injected into the web application and viewed by users. The code may include HTML, JavaScript, or other client-side languages. Reflected XSS is a variation of XSS where user-controllable data is displayed back to the user, in the HTTP response of the request used for the attack, without being validated correctly.', 'This can be a high risk vulnerability and can be underestimated.  Mitigating this vulnerability uses a two-fold approach. Ensure all user-controllable data is validated after it is inputted and again before it is outputted to users. <br><br>\r\nBlacklisting is an approach which consists of checking the input data for malicious characters but a more effective approach is whitelisting. Whitelisting consists of only allowing certain characters to be submitted. For example checking if data submitted is alphanumeric and rejecting the request if it is not. You can use an approach like this after data is submitted and then perform a similar approach before data is outputted to the user. ', 'High', 8),
('sxss', 'Stored Cross-Site Scripting', 'Cross-Site Scripting (XSS) is a vulnerability that allows code to be injected into the web application and viewed by other users. The code may include HTML, JavaScript, or other client-side languages. Stored XSS is a variation of XSS where user-controllable data is stored and displayed to users at a later stage. For example, product reviews or posts on a forum.', 'This is a high risk vulnerability.  Mitigating this vulnerability uses a two-fold approach. Ensure all user-controllable data is validated before it is stored and again before it is outputted to users. <br><br>\r\nBlacklisting is an approach which consists of checking the input data for malicious characters but a more effective approach is whitelisting. Whitelisting consists of only allowing certain characters to be submitted. For example checking if data submitted is alphanumeric and rejecting the request if it is not. You can use an approach like this after data is submitted and then perform a similar approach before data is outputted to the user. ', 'High', 9),
('sqli', 'SQL-Injection', 'SQL injection is a technique in which a user submits SQL statements to a web application in an attempt to exploit the database layer of the application. This can be performed using a browser and entering the SQL statements in a form and submitting the form.', 'This is a critical vulnerability to have on a web application and should be addressed immediately. User-controllable data should be validated before any queries are performed on the database using the data. <br><br>\r\nBlacklisting is an approach which consists of checking the input data for malicious characters but a more effective approach is whitelisting. Whitelisting consists of only allowing certain characters to be submitted. For example checking if data submitted is alphanumeric and rejecting the request if it is not. Many libraries exist, such as built-in libraries for programming languages and open-source libraries, which can assist you in preventing this vulnerability. ', 'High', 10),
('basqli', 'Broken Authentication using SQL Injection', 'SQL injection is a technique in which a user submits SQL statements to a web application in an attempt to exploit the database layer of the application. This can be performed using a browser and entering the SQL statements in a form and submitting the form.<br><br>\r\nThe login form can be bypassed using a form of SQL Injection that manipulates the SQL query behind the login form so that it will return one or more results. Therefore, a malicious user can login to the web application without the knowledge of valid credentials.', 'This is a critical vulnerability to have on a web application and should be addressed immediately. User-controllable data should be validated before any queries are performed on the database using the data. <br><br>\r\nBlacklisting is an approach which consists of checking the input data for malicious characters but a more effective approach is whitelisting. Whitelisting consists of only allowing certain characters to be submitted. For example checking if data submitted is alphanumeric and rejecting the request if it is not. Many libraries exist, such as built-in libraries for programming languages and open-source libraries, which can assist you in preventing this vulnerability.', 'High', 11);
