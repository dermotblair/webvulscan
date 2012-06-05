<?php
/////////////////////////////////////////////////////////
// PHPCrawl
// - class PHPCrawler:
//
// The main-class, version 0.71
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
$currentDir = './';
require_once($currentDir . '../scanner/functions/databaseFunctions.php');

class PHPCrawler
{  
  // Version
  var $class_version = "0.71";
  
  // Base Infos
  var $url_to_crawl = "http://www.foo.com";
  
  // Limits
  var $page_limit_all = 0;
  var $page_limit_count_ct_only = true;  // which pages to count ? Only the
                                         // received pages (matched contend-type)
  // Follow-option-vars
  var $general_follow_mode = 2; // General follow mode
                                // 0: Follow EVERYTHING
                                // 1: Follow only links to the same domain
                                // 2: Follow only links to the exact same HOST
                                // 3: Follow only links to the exact same HOST and path
  
  var $follow_redirects_till_content = true; // Follow redirect till there was found REAL content,
                                             // doesnt matter which follow-mode was set
  
  var $not_follow_matches = array();
  var $follow_matches = array();
  var $follow_redirects = true;
  
  var $link_priorities = array(); // Will contain all link-priority-matches set
                                  // by the user
    
  var $store_extended_linkinfo = true; // Decides if the crawler should store extended-linkinfo like linktext,
                                       // linkcode etc. for the user
                                       
  var $parse_robots_txt = false; // Should the robots.txt-file be parsed?
  
  // INTERN VARS, DONT TOUCH !
    
  var $pageRequest; // An instance of the PHPCrawlerPageRequest-Object
  
  var $base_file;
  var $base_path;  // http://www.foo.com/stuff/index.htm -> /stuff
  var $base_host;  // http://www.foo.com/stuff/index.htm -> www.foo.com
  var $base_domain; //  http://www.foo.com/stuff/index.htm -> foo.com
  var $base_port; // http://www.foo.com:443/stuff/ -> 443
  
    
  var $urls_to_crawl = array(); // Walking array, will contain 
                                // all links that should be followed
                                // its build like that (an example):
                                // $urls_to_crawl[6][2]["urlrebuild"]
                                // This is the second url in the
                                // priority_array number 6
                                // Each element is an array again that contains
                                // the elements ["link_raw"], ["url_rebuild"], ["refering_url"],
                                // ["linktext"] ans ["linkcode"] later on
                                // IMPORTANT: All URLs in here WILL BE CRAWLED,
                                // all "operations" like filtering and manipulating a.s.o. will be
                                // done BEFORE the links/urls will be put in here !!

  var $url_map = array(); // This array will contain all md5-hashes of URLS that were
                        // put into the array $urls_to_crawl, BUT AS KEYS, like
                        // $url_map["http://www.foo.com/bar.html"]=true (md5, not the url itself)
                        // This is for checking if a found URL is already in there
                        // or not, this improves performance A LOT. 
    
  // KICKED OUT
  // now its all in $urls_to_crawl                           
  // var $referers_to_urls_to_crawl=array(); // The referers to the pathes to crawl
  // var $linktexts_of_urls_to_crawl=array();
  
  var $content_found = false; // Just a flag that switches to TRUE if ANY content was found
  
  var $status_return = array(); // Status-array to return after process finished
  
  var $max_priority_level = 0; // Will contain the highest priority_level set by the user
  
  var $benchmark = false; // internal
  
  var $startTime = 0;
  var $endTime = 0;
  
  var $urlsFound = array();
  var $firstCrawl = false;//A second crawl is performed when testing for directory listing enabled
						  //The status of the crawl is only updated if this is set to true
 
  var $testId = 0;

  function setTestId($testId)
  {
    $this->initCrawler();
	$this->testId = $testId;
  }
  
  function setFirstCrawl($firstCrawl)
  {
    $this->initCrawler();
	$this->firstCrawl = $firstCrawl;
  }
  
  // Constructor
  function PHPCrawler()
  {
    $this->initCrawler();
  }
  
  function initCrawler()
  {
    // Include needed class-files
    $classpath = dirname(__FILE__);
    
    // Utils-class
    if (!class_exists("PHPCrawlerUtils"))
    {
      include_once($classpath."/phpcrawlerutils.class.php");
    }
    
    // PageRequest-class
    if (!class_exists("PHPCrawlerPageRequest"))
    {
      include_once($classpath."/phpcrawlerpagerequest.class.php");
    }
    
    // Initiate a new PageRequest
    if ($this->pageRequest == null)
    {
      $this->pageRequest = &new PHPCrawlerPageRequest();
    }
  }
  
  // For debugging and stuff ONLY !
  function getmicrotime()
  { 
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
  } 
  
  function go()
  {  
    connectToDb($db);
	
    $starting_time = $this->getmicrotime();
    
    // Init, split given URL into host, port, path and file a.s.o.
    $url_parts = PHPCrawlerUtils::splitURL($this->url_to_crawl);
    
    // Set base-host and base-path "global" for this class,
    // we need it very often (i guess at this point...)
    $this->base_path = $url_parts["path"];
    $this->base_host = $url_parts["host"];
    $this->base_domain = $url_parts["domain"];
    
    // If the base port wasnt set by the user ->
    // take the one from the given start-URL.
    if ($this->base_port == "") $this->base_port = $url_parts["port"];
    
    // if the base-port WAS set by the user
    $url_parts["port"] = $this->base_port;
    
    // Reset the base_url
    $this->url_to_crawl = PHPCrawlerUtils::rebuildURL($url_parts);
    $this->url_to_crawl = PHPCrawlerUtils::normalizeURL($this->url_to_crawl);
    
    // Init counters
    $links_followed=0;
    $files_received=0;
    
    // Put the first url into our main-array
    $tmp[0]["url_rebuild"] = $this->url_to_crawl;
    PHPCrawlerUtils::removeMatchingLinks($tmp, $this->not_follow_matches);
    
    if (isset($tmp[0]["url_rebuild"]) &&  $tmp[0]["url_rebuild"] != "")
    {
      PHPCrawlerUtils::addToArray($tmp, $this->urls_to_crawl, $this->url_map, $this->store_extended_linkinfo);
    }
    
    // MAIN-LOOP -------------------------------------------------------------------
    
    // It works like this:
    // The first loop looks through all the "Priority"-arrays and checks if any
    // of these arrays is filled with URLS.
    
    for ($pri_level = $this->max_priority_level+1; $pri_level > -1; $pri_level--)
    {
      // Yep. Found a priority-array with at least one URL
      if (isset($this->urls_to_crawl[$pri_level]) && !isset($stop_crawling))
      {   
        // Now "process" all URLS in this priroity-array
        @reset($this->urls_to_crawl[$pri_level]);
        while (list($key) = @each($this->urls_to_crawl[$pri_level]))      
        {
          $all_start = $this->getmicrotime();
          
          $stop_crawling_this_level = false; // init
          
          // Request URL (crawl())
          unset($page_data);
          
          if (!isset($this->urls_to_crawl[$pri_level][$key]["referer_url"])) 
          {
            $this->urls_to_crawl[$pri_level][$key]["referer_url"] = "";
          }
          
		  if($db)
			incrementHttpRequests($db, $this->testId);//Increment number of HTTP requests sent as fsockopen is called next
		  
          $page_data = $this->pageRequest->receivePage($this->urls_to_crawl[$pri_level][$key]["url_rebuild"],
                                                       $this->urls_to_crawl[$pri_level][$key]["referer_url"]);

          // If the request-object just irnored the URL ->
          // -> Stop and remove URL from Array
          if ($page_data == false)
          {
            unset($this->urls_to_crawl[$pri_level][$key]);
            continue; 
          }
          
          $links_followed++;
          
          // Now $page_data["links_found"] contains all found links at this point
          
          // Check if a "<base href.."-tag is given in the source and xtract
          // the base URL
          // !! Doesnt have to be rebuild cause it only can be a full
          // qualified URL !!
          $base_url = PHPCrawlerUtils::getBasePathFromTag($page_data["source"]);
          if ($base_url == "") $actual_url = &$this->urls_to_crawl[$pri_level][$key]["url_rebuild"];
          else $actual_url = $base_url;
          
          // Set flag "content_found" if..content was found
          if (isset($page_data["http_status_code"]) && $page_data["http_status_code"]==200) $content_found = true;
          
          // Check for a REDIRECT-header and if wanted, put it into the array of found links
          $redirect = PHPCrawlerUtils::getRedirectLocation($page_data["header"]);
          if ($redirect && $this->follow_redirects==true)
          {
            $tmp_array["link_raw"] = $redirect;
            $tmp_array["referer_url"] = $this->urls_to_crawl[$pri_level][$key]["url_rebuild"];
            $page_data["links_found"][] = $tmp_array;
          }
          
          // Count files that have been received completly
          if ($page_data["received"] == true) $files_received++;
          
          // If traffic-limit is reached -> stop crawling
          if ($page_data["traffic_limit_reached"] == true) $stop_crawling = true;
          
          // Check if pagelimit is reached if set
          // (and check WHICH page-limit was set)
          if ($this->page_limit_all > 0)
          {
            if ($this->page_limit_count_ct_only==true && $files_received >= $this->page_limit_all)
            {
              $stop_crawling = true;
            }
            elseif ($this->page_limit_count_ct_only==false && $links_followed >= $this->page_limit_all)
            {
              $stop_crawling = true;
            }
          }
          
          // Add the actual referer to the page_data array for the handlePageData-method
          $page_data["refering_linktext"] = &$this->urls_to_crawl[$pri_level][$key]["linktext"];
          $page_data["refering_link_raw"] = &$this->urls_to_crawl[$pri_level][$key]["link_raw"];
          $page_data["refering_linkcode"] = &$this->urls_to_crawl[$pri_level][$key]["linkcode"];
           
          // build new absolute URLs from found links
          $page_data["links_found"] = PHPCrawlerUtils::buildURLs($page_data["links_found"], $actual_url);
          
          // Call the overridable user-function here, but first
          // "save" the found links from user-manipulation
          $links_found = $page_data["links_found"];
          $user_return = $this->handlePageData($page_data);
          
          // Stop crawling if user returned a negative value
          if ($user_return < 0)
          {
            $stop_crawling=true;
            $page_data["user_abort"] = true;
          }
          
          // Compare the found links with link-priorities set by the user
          // and add the priority-level to our array $links_found
          if ($this->benchmark==true) $bm_start = $this->getmicrotime();
          PHPCrawlerUtils::addURLPriorities ($links_found, $this->link_priorities);
          if ($this->benchmark==true) echo "addUrlPriorities(): ".($this->getmicrotime() - $bm_start)."<br>";
          
          // Here we can delete the tmp-file maybe created by the pageRequest-object
          if (file_exists($this->pageRequest->tmp_file)) @unlink($this->pageRequest->tmp_file);
          
          // Stop everything if a limit was reached
          if (isset($stop_crawling))
          {
            break;
            $pri_level=1000;
          }
          
          // Remove links to other hosts if follow_mode is 2 or 3
          if ($this->general_follow_mode == 2 || $this->general_follow_mode == 3)
          {
            PHPCrawlerUtils::removeURLsToOtherHosts($links_found, $this->urls_to_crawl[$pri_level][$key]["url_rebuild"]);
          }
          
          // Remove links to other domains if follow_mode=1
          if ($this->general_follow_mode == 1)
          {
            PHPCrawlerUtils::removeURLsToOtherDomains($links_found, $this->urls_to_crawl[$pri_level][$key]["url_rebuild"]);
          }
       
          // Remove "pathUp"-links if follow_mode=3
          // (fe: base-site: www.foo.com/bar/index.htm -> dont follow: www.foo.com/anotherbar/xyz)
          if ($this->general_follow_mode == 3)
          {
            PHPCrawlerUtils::removePathUpLinks($links_found, $this->url_to_crawl);
          }
          
          // If given, dont follow "not matching"-links
          // (dont follow given preg_matches)
          if (count($this->not_follow_matches) > 0)
          {
            PHPCrawlerUtils::removeMatchingLinks($links_found, $this->not_follow_matches);
          }
          
          // If given, just follow "matching"-links
          // (only follow given preg_matches)
          if (count($this->follow_matches) > 0)
          {
            $links_found=&PHPCrawlerUtils::removeNotMatchingLinks($links_found, $this->follow_matches);
          }
          
          // Add found and filtered links to the main_array urls_to_crawl
          if ($this->benchmark == true) $bm_start = $this->getmicrotime();
          PHPCrawlerUtils::addToArray($links_found, $this->urls_to_crawl, $this->url_map, $this->store_extended_linkinfo);
          if ($this->benchmark == true) echo "addToArray(): ".($this->getmicrotime() - $bm_start)."<br>";
          
          // If there is wasnt any content found so far (code 200) and theres
          // a redirect location
          // -> follow it, doesnt matter what follow-mode was choosen !
          // (put it into the main-array !)
          if (!isset($content_found) && $redirect != "" && $this->follow_redirects_till_content == true)
          {
            $rd[0]["url_rebuild"] = phpcrawlerutils::buildURL($redirect, $actual_url);
            $rd[0]["priority_level"] = 0;
            PHPCrawlerUtils::addToArray($rd, $this->urls_to_crawl, $this->url_map, $this->store_extended_linkinfo);
          }
    
          // Now we remove the actual URL from the priority-array
          unset($this->urls_to_crawl[$pri_level][$key]);
          
          // Now we check if a priority-array with a higher priority
          // contains URLS and if so, stop processing this pri-array and "switch" to the higher
          // one
          for ($pri_level_check = $this->max_priority_level+1; $pri_level_check > $pri_level; $pri_level_check--)
          {
            if (isset($this->urls_to_crawl[$pri_level_check]) && $pri_level_check > $pri_level)
            {
              $stop_crawling_this_level = true;
            }
          }
          
          // Stop crawling this level
          if ($stop_crawling_this_level == true) 
          {
            $pri_level = $this->max_priority_level+1;
            break;
          }
          
          // Unset crawled URL, not nedded anymore
          unset($this->urls_to_crawl[$pri_level][$key]);
          
          // echo "All:".($this->getmicrotime()-$all_start);
          
        } // end of loop over priority-array
        
        // If a priority_level was crawled completely -> unset the whole array
        if ($stop_crawling_this_level == false)
        {
          unset($this->urls_to_crawl[$pri_level]);
        }
        
      } // end if priority-level exists
    
    } // end of main loop
    
    
    // Loop stopped here, build report-array (status_return)
    
    $this->status_return["links_followed"] = $links_followed;
    $this->status_return["files_received"] = $files_received;
    $this->status_return["bytes_received"] = $this->pageRequest->traffic_all;
    
    $this->status_return["traffic_limit_reached"] = $page_data["traffic_limit_reached"];
    
    if (isset($page_data["file_limit_reached"]))
    {
      $this->status_return["file_limit_reached"] = $page_data["file_limit_reached"];
    }
    else $this->status_return["file_limit_reached"] = false;
    
    if (isset($page_data["user_abort"]))
    {
      $this->status_return["user_abort"] = $page_data["user_abort"];
    }
    else $this->status_return["user_abort"] = false;
    
    if (isset($stop_crawling))
    {
      $this->status_return["limit_reached"] = true;
    }
    else {
      $this->status_return["limit_reached"] = false;
    }
    
    // Process-time
    $this->status_return["process_runtime"] = $this->getMicroTime() - $starting_time;
	
    // Average bandwith / throughput
    $this->status_return["data_throughput"] = round($this->status_return["bytes_received"] / $this->status_return["process_runtime"]);
	
	if($this->firstCrawl)
	{
		$query = "UPDATE tests SET status = 'Finished Crawling!' WHERE id = $this->testId;"; 
		if(connectToDb($db))
		{
			$db->query($query); 
			$duration = $this->status_return["process_runtime"];
			$query = "UPDATE tests SET duration = $duration WHERE id = $this->testId;"; 
			$db->query($query);
		}
	}
  }
  
  // Overridable method
  function handlePageData(&$page_data)
  {
    // Any default action here ??
    // NO.
  }
  
  // public methods -------------------------------------------------------------------
  
  // Start-URL
  function setURL($url)
  {
    $this->initCrawler();
    
    $url = trim($url);
    if ($url!="" && is_string($url))
    {
      if (substr($url,0,7) != "http://" && substr($url,0,8) != "https://")
      {
        $url = "http://".$url;
      }
      $this->url_to_crawl = PHPCrawlerUtils::normalizeURL($url);
      return true;
    }
    else return false;
  }
  
  // Set port of base URL
  function setPort($port)
  {
    $this->initCrawler();
    
    // Check argument
    if (preg_match("/^[0-9]{1,5}$/", $port))
    {
      $this->base_port = trim($port);
      return true;
    }
    else return false;
  }
  
  // TMP-file to use
  function setTmpFile($path_to_file)
  {
    $this->initCrawler();
    
    //Check if writable
    $fp = @fopen($path_to_file, "w");
    if (!$fp) return false;
    else
    {
      fclose($fp);
      $this->pageRequest->tmp_file = trim($path_to_file);
      return true;
    }
  }
  
  // Set the follow mode
  function setFollowMode($mode)
  {
    $this->initCrawler();
    
    // Check argument
    if (preg_match("/^[0-3]{1}$/", $mode))
    {
      $this->general_follow_mode=$mode;
      return true;
    }
    else return false;
  }
  
  // How many pages to crawl MAX (limit), and which pages to count
  // til the limit is reached (true=followed pages, false=all pages)
  // limit=0 means NO LIMIT
  function setPageLimit($limit, $mode=false)
  {
    $this->initCrawler();
    
    // Check argument
    if (preg_match("/^[0-9]*$/", $limit))
    {
      $this->page_limit_count_ct_only = $mode;
      $this->page_limit_all = $limit;
      return true;
    }
    else return false;
  }
  
  // How many bytes to crawl MAX
  // limit=0 means NO LIMIT
  function setTrafficLimit($limit, $mode=true)
  {
    $this->initCrawler();
    if (preg_match("/^[0-9]*$/", $limit))
    {
      $this->pageRequest->traffic_limit_all = $limit;
      $this->pageRequest->traffic_limit_complete_page = $mode;
      return true;
    }
    else return false;
  }
  
  // Set the limit of bytes per page/file
  function setContentSizeLimit($limit)
  {
    $this->initCrawler();
    if (preg_match("/^[0-9]*$/", $limit))
    {
      $this->pageRequest->pagesize_limit = $limit;
      return true;
    }
    else return false;
  }
  
  // which contenttype to follow ?
  // if not specified, everything will be followed
  function addReceiveContentType($expression)
  {
    $this->initCrawler();
    $check = PHPCrawlerUtils::checkExpressionPattern($expression); // Check pattern
    if ($check == true)
    {
      $this->pageRequest->follow_content_type[] = trim(strtolower($expression));
    }
    return $check;
  }
  
  // which links to follow ? (preg_match)
  // if not specified, everything will be followed
  function addNonFollowMatch ($expression)
  {
    $this->initCrawler();
    $check = PHPCrawlerUtils::checkExpressionPattern($expression); // Check pattern
    if ($check == true)
    {
      $this->not_follow_matches[]=trim($expression);
    }
    return $check;
  }
  
  // which links to follow ? (preg_match)
  // if not specified, everything will be followed
  function addFollowMatch ($expression)
  {
    $this->initCrawler();
    $check = PHPCrawlerUtils::checkExpressionPattern($expression); // Check pattern
    if ($check == true)
    {
      $this->follow_matches[]=trim($expression);
    }
    return $check;
  }
  
  // which content-types (preg_match) should
  // be streamed to memory directly ?
  function addReceiveToMemoryMatch ($expression)
  {
    $this->initCrawler();
    $check = PHPCrawlerUtils::checkExpressionPattern($expression); // Check pattern
    if ($check == true)
    {
      $this->pageRequest->receive_to_memory_matches[] = trim($expression);
    }
    
    return $check;
  }
  
  // which content-types (preg_match) should
  // be streamed to the tmp-file ?
  function addReceiveToTmpFileMatch ($expression)
  {
    $this->initCrawler();
    
    $check = PHPCrawlerUtils::checkExpressionPattern($expression); // Check pattern
    if ($check == true)
    {
      $this->pageRequest->receive_to_file_matches[] = trim($expression);
    }
    return $check;
  }
  
  // Follow redirects ? (Header)
  function setFollowRedirects ($mode)
  {
    $this->initCrawler();
    
    if (is_bool($mode))
    {
      $this->follow_redirects = $mode;
      return true;
    }
    else return false;
  }
  
  // Follow redirects till some content was found in ANY WAY ?
  function setFollowRedirectsTillContent ($mode)
  {
    $this->initCrawler();
    
    if (is_bool($mode))
    {
      $this->follow_redirects_till_content = $mode;
      return true;
    }
    else return false;
  }
  
  // Enable/disable cookies
  function setCookieHandling ($mode)
  {
    $this->initCrawler();
    if (is_bool($mode))
    {
      $this->pageRequest->handle_cookies = $mode;
      return true;
    }
    else return false;
  }
  
  // Socket-connection-timeout
  function setConnectionTimeout($timeout) 
  {
    $this->initCrawler();
    if (preg_match("/^[0-9]*\.{0,1}[0-9]*$/", $timeout))
    {
      $this->pageRequest->socket_mean_timeout = $timeout;
      return true;
    }
    else return false;
  }
  
  // Stream timeout
  function setStreamTimeout($timeout)
  {
    $this->initCrawler();
    if (preg_match("/^[0-9]*\.{0,1}[0-9]*$/", $timeout))
    {
      $this->pageRequest->socket_read_timeout = $timeout;
      return true;
    }
    else return false;
  }
  
  // Return Status-array after crawling-process
  function getReport()
  {
    return $this->status_return;
  }
  
  // Method adds link-priorities
  function addLinkPriority ($expression, $level)
  {
    $this->initCrawler();
    
    $check = PHPCrawlerUtils::checkExpressionPattern($expression); // Check pattern
    if ($check==true && preg_match("/^[0-9]*$/", $level))
    {
      $c = count($this->link_priorities);
      $this->link_priorities[$c]["match"] = trim($expression);
      $this->link_priorities[$c]["level"] = trim($level);
      
      // Set the maximum-priority-level
      if ($this->max_priority_level < $level)
      {
        $this->max_priority_level=$level;
      }
      return true;
    }
    else return false;
  }
  
  // Method adds an authentication-login for special URLs given in expression
  // (PCRE)
  function addBasicAuthentication($expression, $username, $password)
  {
    $this->initCrawler();
    $check = PHPCrawlerUtils::checkExpressionPattern($expression); // Check pattern
    
    if ($check == true)
    {
      $c = count($this->pageRequest->basic_authentications);
      $this->pageRequest->basic_authentications[$c]["match"] = $expression;
      $this->pageRequest->basic_authentications[$c]["username"] = $username;
      $this->pageRequest->basic_authentications[$c]["password"] = $password;
      return true;
    }
    else return false; 
  }
  
  // Method adds linktags to extract links from
  function setLinkExtractionTags($tag_array)
  { 
    $this->initCrawler();
    
    if (!is_array($tag_array)) return false;
    $this->pageRequest->linktags_to_extract = $tag_array;
    return true;
  }
  
  // Method adds linktags to extract links from
  function addLinkExtractionTags()
  {
    $this->initCrawler();
    $tags = func_get_args();

    $tmp_array = array();
    
    for ($x=0; $x<count($tags); $x++)
    {
      if (trim($tags[$x]) != "" && is_string($tags[$x]))
      {
        if (!in_array($tags[$x], $tmp_array))
        {
          $tmp_array[] = trim($tags[$x]);
        }
      }
      else $error = true;
    }
    
    if (isset($error)) return false;
    else 
    {
      return $this->setLinkExtractionTags($tmp_array);
    }
  }
  
  // Set agressive link-extraction true/false
  function setAggressiveLinkExtraction ($mode)
  {
    $this->initCrawler();
    if (is_bool($mode))
    {
      $this->pageRequest->aggressive_link_extraction = $mode;
      return true;
    }
    else return false;
  }
  
  // Sets the user-agent string
  function setUserAgentString ($string)
  {
    $this->initCrawler();
    $this->pageRequest->user_agent_string = $string;
    return true;
  }
  
  // Method enables/disables storage of extended link-information
  // (reduces memory-usage)
  function disableExtendedLinkInfo ($mode)
  {
    $this->initCrawler();
    if (is_bool($mode)) {
      if ($mode==true) $this->store_extended_linkinfo=false;
      else $this->store_extended_linkinfo=true;
      return true;
    }
    else return false;
  }
  
  // Enables/Disables parsing of robots.txt-file.
  function obeyRobotsTxt($mode)
  {
    if (is_bool($mode))
    {
      $this->pageRequest->use_robots_txt_files = $mode;
      return true;
    }
    else
    {
      return false;
    }
  }
  
  /**
   * Adds a rule to the list of rules that decide in what kind of documents the crawler
   * should search for links in (regarding their content-type)
   *
   * By default the crawler ONLY searches for links in documents of type "text/html".
   * Use this method to add one or more other content-types the crawler should check for links.
   *
   * Example:
   * <code>
   * $crawler->addLinkSearchContentType("#text/css# i");
   * $crawler->addLinkSearchContentType("#text/xml# i");
   * </code>
   * These rules let the crawler search for links in HTML-, CSS- ans XML-documents.
   *
   * <b>Please note:</b> It is NOT recommended to let the crawler checkfor links in EVERY document-
   * type! This could slow down the crawling-process dramatically (e.g. if the crawler receives large
   * binary-files like images and tries to find links in them).
   *
   * @param string $regex Regular-expression defining the rule
   * @return bool         TRUE if the rule was successfully added
   */
  function addLinkSearchContentType($regex)
  {
    $this->initCrawler();
    
    $check = PHPCrawlerUtils::checkExpressionPattern($regex); // Check pattern
    if ($check == true)
    {
      $this->pageRequest->linksearch_content_types[] = trim($regex);
    }
    return $check;
  }
}
?>
