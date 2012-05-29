<?php
/////////////////////////////////////////////////////////
// PHPCrawl
// - class PHPCrawlerPageRequest:
// 
// v 0.71
//
// Class for requesting pages/files.
//
// Copyright (C) 2003-2011 Uwe Hunfeld (phpcrawl@cuab.de)
//
// This program is free software; you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by the 
// Free Software Foundation; either version 2 of the License, or 
// at your option) any later version. 
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
// FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. 
//
// You should have received a copy of the GNU General Public License along with this
// program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330,
// Boston, MA 02111-1307, USA.
//
/////////////////////////////////////////////////////////

class PHPCrawlerPageRequest
{
  var $host_ip_table = array(); // Array collects all Hosts that were "visited" so far.
                                // The Hosts are the keys, the IPs are the values.
  
  var $basic_authentications = array(); // Contains the logins (username, password) and patterns
                                        // for basic_authentications to send
  
  // Timeouts for socket-connection and read-wait
  var $socket_mean_timeout = 5;
  var $socket_read_timeout = 5;
  
  var $handle_cookies = true; // Cookie handling yes/no
  var $user_agent_string = "PHPCrawl"; // The "user-agent" that will be send with headers
  
  var $tmp_file; // The tmp-file to use for receiving pages/files
  
  var $traffic_limit_all = 0; // Max-bytes this instance of the request-object should
                              // receive alltogether
                                
  var $traffic_limit_complete_page = true; // should the CURRENT file/page be received completly
                                           // even if the traffic_limit was reached ?
                                             
  var $pagesize_limit = 0; // Limit for content-size to receive
  
  var $traffic_all; // Traffic-counter
    
  var $receive_to_memory_matches = array(); // Will contain all matches for content-types
                                            // that should be streamed into memory
                                          
  var $receive_to_file_matches = array(); // Will contain all matches for content-types
                                          // that should be streamed to tmp-file
  
  var $follow_content_type = array();  // Will contain all patterns for content-types that
                                       // should be received
  
  var $linksearch_content_types = array("#text/html# i"); // Contains regexps that decide which
                                                          // cointent-types should get checked for links in.
  
  var $cookies = array(); // Received Cookies
  
  var $aggressive_link_extraction = true; // like it says
  var $linktags_to_extract = array(); // array with all linktags to extract links from, like "href" and "src" a.s.o.
  
  var $robotsTxtHandler; // An instance of the PHPCrawlerRobotsTxtHandler-Object
  var $use_robots_txt_files = false; // Should robots.txt-filed be read and interpreted?
  
  function PHPCrawlerPageRequest()
  {
    // Initiate a RobotsTxtHandler-object
    if (!class_exists("PHPCrawlerRobotsTxtHandler"))
    {
      $classpath = dirname(__FILE__);
      
      include_once($classpath."/phpcrawlerrobotstxthandler.class.php");
      
      // Initiate a new RobotsTxtHandler-Object
      $this->robotsTxtHandler = &new PHPCrawlerRobotsTxtHandler();
    }
  }
                                                                               
  function receivePage($url_to_crawl, $referer_url)
  {
    // Check if tmp-file was set by the user, otherwise set a default one
    if ($this->tmp_file == "") $this->tmp_file = uniqid(time()).".tmp";
    
    // Define some vars
    $source_read = "";
    $bytes_received = 0;
    $stream_to_memory = false;
    $stream_to_file = false;
    
    // Split the url to crawl into its elements (host, path, port and stuff)
    $url_parts = PHPCrawlerUtils::splitURL($url_to_crawl);
    
    $protocol = $url_parts["protocol"];
    $host = $url_parts["host"];
    $path = $url_parts["path"];
    $query = $url_parts["query"];
    $file = $url_parts["file"];
    $port = $url_parts["port"];
    
    // If the host was already visited so far
    // -> get the ip from our host-ip-array, otherwise
    // get the IP and add the entry to the array.
    if (isset($this->host_ip_table[$host]))
    {
      $ip = $this->host_ip_table[$host];
    }
    else
    {
      $ip = $this->host_ip_table[$host] = gethostbyname($host);
      
      // Host switched and wasnt "visited" before.
      // So read the robots.txt-file for this new host (if wanted)
      if ($this->use_robots_txt_files == true)
      {
        $this->robotsTxtHandler->processRobotsTxt($protocol, $host, $port, $this->user_agent_string);
      }
    }
     
    // Is this URL allowed to be requested by the robots.txt-file of this host?
    $url_disallowed = false;
    if ($this->use_robots_txt_files == true)
    {
      $host_url = $protocol.$host.":".$port;
      $url_disallowed = $this->robotsTxtHandler->checkIfUrlDisallowed($url_to_crawl, $host_url);
    }
    
    // Check the protocol (http or https) and build the
    // host-string for fsockopen
    if ($protocol == "https://") $host_str = "ssl://".$ip;  // SSL-connect
    else $host_str = $ip; // normal connect
    
    // Check if an authentication should be send
    $authentication = PHPCrawlerUtils::getAuthenticationForURL($this->basic_authentications, $url_to_crawl);
    
    // Error-codes
    // 0 - couldnt connect to server / page within timeout-time
    // 1 - stopped reading from socket, read-timeout reached BEFORE EOF()
    
    // Open socket-connection
    if ($url_disallowed == false)
    {
	  
      $s = @fsockopen ($host_str, $port, $e, $t, $this->socket_mean_timeout);
    }
    else
    {
      return false; // Return false if the URL was completely ignored
    }
    
    if ($s==false) // Connection-error
    { 
      $error_string = $t;
      $error_code = $e;
	  
      if ($t=="" && $e=="")
      {
        $error_code = 0;
        $error_string = "Couldn't connect to server";
      }
    }
    else
    {
      $header_found = false; // will get true if the header of the page was extracted
      
      // Build header to send
      $headerlines_to_send[] = "GET ".$path.$file.$query." HTTP/1.0\r\n";
      $headerlines_to_send[] = "HOST: ".$host."\r\n";
      
      // Referer
      if ($referer_url!="")
      {
        $headerlines_to_send[] = "Referer: $referer_url\r\n";
      }
      
      // Cookies
      if ($this->handle_cookies == true)
      {
        $cookie_string = PHPCrawlerUtils::buildHeaderCookieString ($this->cookies, $host);
      }

      if (isset($cookie_string))
      {
        $headerlines_to_send[] = "Cookie: ".$cookie_string."\r\n";
      }
      
      // Authentication
      if (count($authentication) > 0)
      {
        $auth_string = base64_encode($authentication["username"].":".$authentication["password"]);
        $headerlines_to_send[] = "Authorization: Basic ".$auth_string."\r\n";
      }
      
      // Rest of header
      $headerlines_to_send[] = "User-Agent: ".str_replace("\n", "", $this->user_agent_string)."\r\n";
      $headerlines_to_send[] = "Connection: close\r\n";
      $headerlines_to_send[] = "\r\n";
      
      // Now send the header
      for ($x=0; $x<count($headerlines_to_send); $x++)
      {
        // Send header-line
        fputs($s, $headerlines_to_send[$x]);
        
        // Put together lines to $header_send
        if (isset($header_send)) $header_send .= $headerlines_to_send[$x];
        else $header_send = $headerlines_to_send[$x];
      }
      
      unset($header_lines);
      
      $status = socket_get_status($s);
      
      // Now read from socket
      // UNTIL timeout reached OR eof() OR content-type shouldnt be followed
      // OR traffic-limit reached or ...
      
      while (!isset($stop))
      {
        socket_set_timeout($s, $this->socket_read_timeout);
        
        // Read from socket
        $line_read = @fgets($s,1024); // The @ is to avoid the strange "SSL fatal protocol error"-warning that
                                      // appears in some environments without any reasons
        $source_read .= $line_read; // do this anyway
        
        // If we want the content in tmp-file -> write line to TMP-file
        if ($header_found == true && $stream_to_file == true && $line_read)
        {
          unset($check);
          $check = @fwrite($fp, $line_read);
          
          if ($check==false)
          {
            $error_code = "2000";
            $error_string = "Couldn't write to TMP-file ".$this->tmp_file;
          }
        }
        
        // Count bytes of the content (not the header)
        if ($header_found == true)
        {
          $bytes_received = $bytes_received + strlen($line_read);
        }
        
        // Check for traffic limit and stop receiving if reached
        if ($this->traffic_limit_complete_page == false && $this->traffic_limit_all > 0)
        {
          if (strlen($source_read) + $this->traffic_all > $this->traffic_limit_all)
          {
            $stop = true;
            $received_completly = false;
            $page_data["traffic_limit_reached"] = true;
          }
        }
        
        // Check for pagesize-limit
        if ($header_found == true && ($bytes_received > $this->pagesize_limit) && $this->pagesize_limit > 0)
        {
          $stop=true;
          $received_completly=false;
        }
        
        // "Cut" Header in seperate var $header and handle it
        if ($header_found == false && substr($source_read, -4, 4) == "\r\n\r\n")
        {
          $header = substr($source_read, 0, strlen($source_read)-2);
          $actual_content_type = PHPCrawlerUtils::getHeaderTag ("content-type", $header);
          $source_read = "";
          $header_found = true;
          
          // Get the http-status-code
          $http_status_code = PHPCrawlerUtils::getHTTPStatusCode($header);
          
          // Should this content-type be streamed into memory (true/false) ?
          $stream_to_memory = PHPCrawlerUtils::decideStreamToMemory ($header, $this->receive_to_memory_matches);
          
          // Should this content-type be streamed into tmp-file (true/false) ?
          $stream_to_file = PHPCrawlerUtils::decideStreamToTmpFile($header, $this->receive_to_file_matches);
          
          // No ? then open TMP-file for the stream
          if ($stream_to_file==true)
          {
            $fp = @fopen($this->tmp_file, "w");
            if ($fp==false)
            {
              $error_code="2000";
              $error_string="Couldn't open TMP-file".$this->tmp_file;
            }
          }
          
          // Header found here -> check if source should be followed (content-type)
          $follow = PHPCrawlerUtils::decideFollow($header, $this->follow_content_type);
          
          // no ?? then stop with this page !
          if ($follow == false)
          {
            $stop = true;
          }
          else
          {
            $received_completly = true; // just init, may switch later on !
          }
    
          // Check if a cookie was send with the header and store it
          // (if wanted)
          if ($this->handle_cookies == true)
          {
            PHPCrawlerUtils::getCookieData($header, $this->cookies, $host);
          }
        } // end cut and handle header
        
        // Get status of socket to check timeout and EOF
        $status = socket_get_status($s);
  
        // Now, if the source-buffer is filled or EOF is reached
        // -> look for links in the buffer, put the found links into
        // array $links_found_in_page and then empty the buffer BUT
        // COPY THE LAST FEW BYTES of the old buffer into the new one !
        // This has to be done because of links that take more than a single
        // line !
        // And yes, only makes sense if we dont want to have the whole content
        // in memory anyway AND if the content-type is text/html!
        
        if ($header_found == true && $stream_to_memory == false)
        {
          if (strlen($source_read) >= 100000 || $status["eof"] == true)
          {
            // If content-types matches with any linkcheck-rule
            if (PHPCrawlerUtils::checkStringAgainstRegexArray($actual_content_type, $this->linksearch_content_types))
            {
              $links_found_in_buffer = PHPCrawlerUtils::findLinks($source_read, $links_found_in_page,
                                                                  $this->aggressive_link_extraction, $this->linktags_to_extract,
                                                                  $page_url_map);
              $source_read = substr($source_read, -1500);
            }
          }
        }
        
        // Check timeout
        if ($status["timed_out"] == true)
        {
          $error_code = 1000; // ahem..which int to give ??
          $error_string = "socketstream timed out";
          $stop = true;
          $received_completly = false;
        }
  
        // Check eof
        if ($status["eof"] == true)
        {
          $stop = true;
        }
        
      }
            
      fclose($s); // close socket
      if (isset($fp) && $fp != false) fclose($fp); // close tmp file if used
      
    }
    
    // echo "Get page:".($this->getmicrotime() - $start);
    
    // Now, HERE, if the whole content/source was received into memory,
    // we are looking for the links in the complete source (faster)
    // it only makes sense if content-type is text/html !
    
    if ($stream_to_memory == true)
    {
      unset($links_found_in_page);
      
      // If content-types matches with any linkcheck-rule
      if (PHPCrawlerUtils::checkStringAgainstRegexArray($actual_content_type, $this->linksearch_content_types))
      {
        // $start = $this->getmicrotime();
        PHPCrawlerUtils::findLinks($source_read, $links_found_in_page, $this->aggressive_link_extraction,
                                   $this->linktags_to_extract, $page_url_map);
        // echo "Find links:".($this->getmicrotime() - $start);
      }
    }
    
    // Add the "refering_url" to the array-elements
    if (isset($links_found_in_page))
    {
      for ($x=0; $x<count($links_found_in_page); $x++)
      {
        $links_found_in_page[$x]["referer_url"] = $url_to_crawl;
      }
    }

    // Page crawled, 
    // return header, source, followed (true/false) and all we got here
    unset($page_data);
    
    if (isset($error_code)) $page_data["error_code"] = $error_code;
    else $page_data["error_code"] = false;
    
    if (isset($error_string)) $page_data["error_string"] = $error_string;
    else $page_data["error_string"] = false;
    
    if (isset($follow)) $page_data["received"] = &$follow;
    else $page_data["received"] = false;
    
    if (isset($received_completly)) $page_data["received_completly"] = &$received_completly;
    else $page_data["received_completly"] = false;
    
    $page_data["received_completely"] = &$page_data["received_completly"]; // Wrote "completely" it wrong in prev. version,
    
    if (isset($bytes_received)) $page_data["bytes_received"] = $bytes_received;
    else $page_data["bytes_received"] = 0;
    
    if (isset($header)) $page_data["header"] = &$header;
    else $page_data["header"] = false;
    
    if (isset($http_status_code)) $page_data["http_status_code"] = &$http_status_code;
    else $page_data["http_status_code"] = false;
    
    if (isset($actual_content_type)) $page_data["content_type"] = $actual_content_type;
    else $page_data["content_type"] = false;
    
    // TMP-file infos and that
    $page_data["content_tmp_file"]=$page_data["received_to_file"] = false;
    $page_data["source"] = $page_data["content"] = $page_data["received_to_memory"] = false;
    
    if (isset($page_data["received"]))
    {
      if ($stream_to_file==true)
      {
        $page_data["content_tmp_file"] = $this->tmp_file;
        $page_data["received_to_file"] = true;
      }
      if ($stream_to_memory==true)
      {
        $page_data["source"] = &$source_read;
        $page_data["content"] = &$source_read;
        $page_data["received_to_memory"] = true;
      }
    }
    
    // Additional infos for the override-function handlePageData()
    $page_data["protocol"] = $protocol;
    $page_data["port"] = $port;
    $page_data["host"] = $host;
    $page_data["path"] = $path;
    $page_data["file"] = $file;
    $page_data["query"] = $query;
    $page_data["header_send"] = &$header_send;
    $page_data["referer_url"] = $referer_url;
    
    // "Normailzed" URL and referer-URL (f.e. without port if port is 80 and protocol is http)
    $page_data["url"] = $url_to_crawl;
    
    // All links found in this page
    $page_data["links_found"] = &$links_found_in_page;
    
    // Increase SUM of traffic alltogether this instance received
    $this->traffic_all = $this->traffic_all + strlen($page_data["header"]) + $page_data["bytes_received"];
    
    // Set flag if traffic-limit is reached
    if ($this->traffic_all > $this->traffic_limit_all && $this->traffic_limit_all != 0)
    {
      $page_data["traffic_limit_reached"] = true;
    }
    
    if (!isset($page_data["traffic_limit_reached"])) $page_data["traffic_limit_reached"] = false;
    
    return($page_data);
    
  } // end function crawl a URL
}
