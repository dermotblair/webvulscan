<?php
/////////////////////////////////////////////////////////
// PHPCrawl
// - class PHPCrawlerUtils:
// 
// v 0.71
//
// Contains methods needed by the main-class PHPCrawler,
// they also may be useful for applications that use
// the crawler-class.
//
// Sorry, but most functions in here are not comletely compatible anymore
// with the ones from version 0.65, SORRY !
// (only this functions on its own, the crawler and its "public-mthods"
// ARE compatible of course.)
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

class PHPCrawlerUtils {

  // For debugging and stuff ONLY !
  function getmicrotime(){ 
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
  } 
  
  // function splitURL returns the url-parts port, host, protocol, path, file, query, domain
  // from a given URL in an array
  // IMPORTANT: It returns default port 80 and protocol "http://" if it isnt
  // in the url. Also it returns port=443 if protocol is https and port not specified in url.
  // "www.foo.de/test/" -> protocol="http://" and port=80
  // "https://www.foo.de/test/" -> port=443
  
  function splitURL (&$url) {

    // Get the protocol from URL
    preg_match("/^.{0,10}:\/\//", $url, $match); // Everything from the beginning to "..:\\"
    if (isset($match[0])) $protocol=$match[0];
    else $protocol="";
    
    // Get the host from URL
    // (complete, including port and auth login)
    $url_tmp = substr($url, strlen($protocol)); // Cut off the protocol at beginning
    preg_match("/(^[^\/\?#]{1,})/", $url_tmp, $match); // Everything till the first "/", "?" or "#"
    if (isset($match[1])) $host_complete = $match[1];
    else $host_complete = "";
    
    // Get the path
    $url_tmp = substr($url_tmp, strlen($host_complete)); // Cut off the host at beginning
    preg_match("#^[^?\#]{0,}/#", $url_tmp, $match); // Everything till the last "/", but is not allowed to contain "?" and "#"
    if (isset($match[0])) $path = $match[0];
    else $path = "";
    
    // Get the file
    $url_tmp = substr($url_tmp, strlen($path)); // Cut off the path at beginning
    preg_match("#^[^?\#]*#", $url_tmp, $match); // Everything till "?" or "#"
    if (isset($match[0])) $file = $match[0];
    else $file = "";
    
    // Get the query
    $url_tmp = substr($url_tmp, strlen($file)); // Cut off the file at beginning
    preg_match("/^\?[^#]*/", $url_tmp, $match); // Everything from "?" till end or "#"
    if (isset($match[0])) $query = $match[0];
    else $query = "";
    
    // Split the host (complete) into PORT and HOST and UNAME and PASSWD
    // (i.e. host: "uname:passwd@www.foo.com:81)"
      
    // 1. Get uname:passwd
    preg_match("#^.*@#", $host_complete, $match); // Everythig till "@"
    if (isset($match[0])) $auth_login = $match[0];
    
    // 2. Get the clean host
    if (isset($auth_login))
      $host_complete = substr($host_complete, strlen($auth_login)); // Cut off auth_login at the beginning
    preg_match("#[^:]*#", $host_complete, $match); // Everything till ":" or end
    if (isset($match[0])) $host = $match[0];
    else $host = "";
    
    // 3. Get the port
    preg_match("#:([^:]*$)#", $host_complete, $match); // Everything from the last ":"
    if (isset($match[1])) $port = (int)$match[1];
    
    // Now get the DOMAIN from the host
    // Host: www.foo.com -> Domain: foo.com
    $parts=@explode(".", $host);
    if (count($parts)<=2) {
      $domain=$host;
    }
    else {
      $pos=strpos($host, ".");
      $domain=substr($host, $pos+1);
    }
    
    // DEFAULT VALUES for protocol, path, port etc. if not set yet
      
    // if the protocol is emtpy -> set protocol to "http://"
    if ($protocol=="") $protocol="http://";
    
    // if the port is empty -> Set port to 80 or 443
    // depending on the protocol
    if (!isset($port))
    {
      if ($protocol=="http://") $port=80;
      if ($protocol=="https://") $port=443;
    }
    
    // If the path is empty -> path is "/"
    if ($path=="") $path = "/";
    
    // Build return-array
    $url_parts["protocol"] = $protocol;
    $url_parts["host"] = $host;
    $url_parts["path"] = $path;
    $url_parts["file"] = $file;
    $url_parts["query"] = $query;
    $url_parts["domain"] = $domain;
    $url_parts["port"] = $port;
    
    return $url_parts;
  }

  // Function "nomrmalizes" an URL.
  // F.e. if an URL is http://www.foo.com:80/path/, it returns http://www.foo.com/path/,
  // if an URL is http://www.foo.com:100/path/, it returns http://www.foo.com:100/path/
  // if if an URL is https://www.foo.com:443/path/, it returns https://www.foo.com/path/
  // ...
    
  function normalizeURL ($url)
  {
    $url_parts = PHPCrawlerUtils::splitURL($url);
    
    if ($url_parts["protocol"] == "http://" && $url_parts["port"] == 80 ||
        $url_parts["protocol"] == "https://" && $url_parts["port"] == 443)
    {
      $url_rebuild = $url_parts["protocol"].$url_parts["host"].$url_parts["path"].$url_parts["file"].$url_parts["query"];
    }  
    else
    {
      $url_rebuild = $url;
    }
    
    return $url_rebuild;
  }
  
  // Same as findLinks(), see below.
  
  function find_links(&$source, &$target_array, $aggressive_mode, &$tags_to_extract, &$map_array)
  {
    phpcrawlerutils::findLinks($source, $target_array, $aggressive_mode, $tags_to_extract, $map_array);
  }
  
  // function findLinks finds and returns links in a given
  // html-source. It puts the found links into the target_array (reference),
  // The map_array is an array that contaions already found links as keys.
  
  function findLinks(&$source, &$target_array, $aggressive_mode, &$tags_to_extract, &$map_array) {
  
    $match_part="";
    
    $all_links=array(); // will get filled with the found links
    
    // Now check if user added linktags to extract and if so, use them
    // (build expression-part like "href|src|location ...")
    if (count($tags_to_extract)>0)
    {
      for ($x=0; $x<count($tags_to_extract); $x++)
      {
        $match_part.="|".$tags_to_extract[$x];
      }
      $match_part=substr($match_part,1);
    }
    // else we use the default extraction
    else
    {
      $match_part="href|src|url|location|codebase|background|data|profile|action|open";
    }
    
    // 1. <a href="...">LINKTEXT</a> (well formed link with </a> at the end and quotes around the link)
    // Get the link AND the linktext from these tags
    // This has to be done FIRST !!
    preg_match_all("/<\s*a\s[^<>]*\s(?:".$match_part.")\s*=\s*".
                   "([\"|']{0,1})(.*?)\\1[^<>]*>".
                   "((?:(?!<\s*\/a\s*>).){0,500})".
                   "<\s*\/a\s*>/ is", $source, $regs);

    $cnt = count($regs[2]);
    for ($x=0; $x<$cnt; $x++)
    {  
      $tmp_array["link_raw"] = trim($regs[2][$x]);
      $tmp_array["linktext"] = $regs[3][$x];
      $tmp_array["linkcode"] = trim($regs[0][$x]);
      
      // If no link was found -> maybe the link wasn't enclosed by quotes
      if ($tmp_array["link_raw"] == "")
      {
        preg_match("/<\s*a\s[^<>]*\s(?:".$match_part.")\s*=\s*".
                   "([^ ><'\"]+)\s[^<>]*>".
                   "((?:(?!<\s*\/a\s*>).){0,500})".
                   "<\s*\/a\s*>/ is", $tmp_array["linkcode"], $match);
      
        $tmp_array["link_raw"] = trim($match[1]);
        $tmp_array["linktext"] = $match[2];
        $tmp_array["linkcode"] = trim($match[0]);
      }
      
      $map_key = $tmp_array["link_raw"];
      if (!isset($map_array[$map_key]))
      {
        $target_array[]=$tmp_array;
        $map_array[$map_key]=true;
      }
    }
    
    
    
    // Now we "preg" all other matches
    // 2. all like <..href="..."> <..src=".."> and so on
    
    preg_match_all("/<[^<>]*\s(?:".$match_part.")\s*=\s*([\"|']{0,1})(.*?)\\1[^<>]*>/ is", $source, $regs);

    $cnt = count($regs[2]);
    for ($x=0; $x<$cnt; $x++)
    {
      $tmp_array["link_raw"] = trim($regs[2][$x]);
      $tmp_array["linktext"] = "";
      $tmp_array["linkcode"] = trim($regs[0][$x]);
      
      // If no link was found -> maybe the link wasn't enclosed by quotes
      if ($tmp_array["link_raw"] == "")
      {
        preg_match("/<[^<>]*\s(?:".$match_part.")\s*=\s*([^ ><'\"]+)[^<>]*>/ is", $tmp_array["linkcode"], $match);
      
        $tmp_array["link_raw"] = trim($match[1]);
        $tmp_array["linkcode"] = trim($match[0]);
      }
      
      $map_key = $tmp_array["link_raw"];
      if (!isset($map_array[$map_key]))
      {
        $target_array[]=$tmp_array;
        $map_array[$map_key]=true;
      }
    }
    
    // Now, if agressive_mode is set to true, we look for some
    // other things
    $pregs = array();
    if ($aggressive_mode == true)
    {
      // Links like "...:url("animage.gif")..."
      $pregs[]="/[\s\.:;](?:".$match_part.")\s*\(\s*([\"|']{0,1})([^\"'\) ]{1,500})['\"\)]/ is";
      
      // Everything like "...href="bla.html"..." with qoutes
      $pregs[]="/[\s\.:;](?:".$match_part.")\s*=\s*([\"|'])(.{0,500}?)\\1/ is";
      
      // Everything like "...href=bla.html..." without qoutes
      $pregs[]="/[\s\.:;](?:".$match_part.")\s*(=)\s*([^\s\"']{1,500})/ is";
    }
    
    // Now execute the pregs
    for ($x=0; $x<count($pregs); $x++)
    {
      unset($regs);
      preg_match_all($pregs[$x], $source, $regs);
      
      $cnt = count($regs[0]);
      for ($y=0; $y<$cnt; $y++)
      {  
        unset($tmp_array);
        $tmp_array["link_raw"]=trim($regs[2][$y]);
        $tmp_array["linkcode"]=trim($regs[0][$y]);
        $tmp_array["linktext"]="";
        
        $map_key=$tmp_array["link_raw"];
        
        if (!isset($map_array[$map_key])) {
          $target_array[]=$tmp_array;
          $map_array[$map_key]=true;
        }
      }
    }
  }
  
  // function buildURL() "reconstruncts" a full URL from given links relating
  // to the URL they were found in
  
  function buildURL ($link, $actual_url, $url_parts_actual="") {

    // Important: Function has to return a FULL URL, ioncluing
    // the port !!
    
    if ($url_parts_actual=="")
      $url_parts_actual=phpcrawlerutils::splitURL($actual_url);
    
    // Entities-replacements
    $entities= array ("'&(quot|#34);'i",
                        "'&(amp|#38);'i",
                        "'&(lt|#60);'i",
                        "'&(gt|#62);'i",
                        "'&(nbsp|#160);'i",
                        "'&(iexcl|#161);'i",
                        "'&(cent|#162);'i",
                        "'&(pound|#163);'i",
                        "'&(copy|#169);'i");
                        
      $replace=array ("\"",
                      "&",
                      "<",
                      ">",
                      " ",
                      chr(161),
                      chr(162),
                      chr(163),
                      chr(169));
   
   $link = str_replace("\n", "", $link);
   $link = str_replace("\r", "", $link);
   
   // Remove "#..." at end, but ONLY at the end,
   // not if # is at the beginning !
   $link=preg_replace("/^(.{1,})#.{0,}$/", "\\1", $link);

   // Cases
   
   // Strange link like "//foo.htm" -> make it to "http://foo.html"
   if (substr($link,0,2)=="//") {
     $link="http:".$link;
     $link=phpcrawlerutils::rebuildURL(phpcrawlerutils::splitURL($link));
   }
   
   // 1. relative link starts with "/" --> doc_root
   // "/index.html" -> "http://www.foo.com/index.html"    
   elseif (substr($link,0,1)=="/") {
     $link=$url_parts_actual["protocol"].$url_parts_actual["host"].":".$url_parts_actual["port"].$link;
   }
    
    // 2. "./foo.htm" -> "foo.htm"
    elseif (substr($link,0,2)=="./") {
      $link=$url_parts_actual["protocol"].$url_parts_actual["host"].":".$url_parts_actual["port"].$url_parts_actual["path"].substr($link, 2);
    }
    
    // 3. Link is an absolute Link with a given host (f.e. "http://...")
    // DO NOTHING but rebuild the URL again because of the port
    // that is maybe missing in this URL
    elseif (preg_match("/^[^\/]{1,}(:\/\/)/", $link)) {
      if (substr($link, 0, 7) == "http://" || substr($link, 0, 8) == "https://")
        $link=phpcrawlerutils::rebuildURL(phpcrawlerutils::splitURL($link));
      else $link = ""; // Kick out unsupported protocols
    }
    
    // 4. Link is stuff like "javascript: ..." or something
    elseif (preg_match("/^[a-zA-Z]{0,}:[^\/]{0,1}/", $link))
    {
      $link = "";
    }
    
    // 5. "../../foo.html" -> remove the last path from our actual path
    // and remove "../" from link at the same time until there are
    // no more "../" at the beginning of the link
    elseif (substr($link, 0, 3)=="../") {
      $new_path=$url_parts_actual["path"];
      
      while (substr($link, 0, 3)=="../") {
        $new_path=preg_replace('/\/[^\/]{0,}\/$/',"/",$new_path);
        $link=substr($link,3);
      }
      
      $link=$url_parts_actual["protocol"].$url_parts_actual["host"].":".$url_parts_actual["port"].$new_path.$link;
    }
    
    // 6. link starts with #
    // -> leads to the same site as we are on, trash
    elseif (substr($link,0,1)=="#")
    {
      $link="";
    }
    
    elseif ($link == "")
    {
      $link = $actual_url;
    }
    
    // 7. thats it, else the abs_path is simply PATH.LINK ...
    else
    { 
      $link=$url_parts_actual["protocol"].$url_parts_actual["host"].":".$url_parts_actual["port"].$url_parts_actual["path"].$link;
    }
    
    // Now, at least, replace all HTMLENTITIES with normal text !!
    // Ie: HTML-Code of the link is: <a href="index.php?x=1&amp;y=2">
    // -> Link has to be "index.php?x=1&y=2"
    $link = preg_replace ($entities, $replace, $link);
    $link = rawurldecode($link);
    $link = str_replace(" ", "%20", $link);
    
    // "Normalize" URL
    $link = PHPCrawlerUtils::normalizeUrl($link);
    
    return $link;
  }
  
  // function buildURLs() "reconstruncts" full URLs from given links relating
  // to the URL they were found in
  
  function buildURLs($links, $actual_url)
  {  
    $url_parts_actual = phpcrawlerutils::splitURL($actual_url);
    
    @reset($links);                  
    while (list($x)=@each($links))
    {
      $links[$x]["url_rebuild"] = phpcrawlerutils::buildURL($links[$x]["link_raw"], $actual_url, $url_parts_actual);
    }
    
    // Now, $links may contain empty elements (deleted URLs)
    // -> Clean it
    @reset($links);
    
    $links_new = array();                  
    $url_map = array();
    
    while (list($x)=@each($links))
    {
      if ($links[$x]["url_rebuild"]!="" && !isset($url_map[$links[$x]["url_rebuild"]]))
      {
        $links_new[] = $links[$x];
        $url_map[$links[$x]["url_rebuild"]] = true; 
      }
    }
    
    return $links_new;
  }
  
  // function just builds an URL out of the single URL-parts like "protocol", "host", "path" ...
  // given in the array $url_parts
  
  function rebuildURL ($url_parts) {
    if (!isset($url_parts["path"])) $url_parts["path"]="";
    if (!isset($url_parts["file"])) $url_parts["file"]="";
    if (!isset($url_parts["query"])) $url_parts["query"]="";
    
    $url=$url_parts["protocol"].$url_parts["host"].":".$url_parts["port"].$url_parts["path"].$url_parts["file"].$url_parts["query"];
    return $url;
  }
  
  // function removes URLs from a list of URLs that dont have
  // the same host
  
  function removeURLsToOtherHosts(&$links, &$actual_url) {

    $url_parts_actual=phpcrawlerutils::splitURL($actual_url);

    @reset($links);
    while (list($x)=@each($links)) {
      $url_parts_link=phpcrawlerutils::splitURL($links[$x]["url_rebuild"]);
      
      // Ignore "www." at the beginning of the host,
      // because "www.foo.com" is the same host as "foo.com"
      $host_link = preg_replace("/^www\./", "", $url_parts_link["host"]);
      $host_actual = preg_replace("/^www\./", "", $url_parts_actual["host"]);
      
      if ($host_link!=$host_actual) {
        $links[$x]="";
      }
    }
  }
  
  // function removes URLs from a list of URLs that dont have
  // the same domain
  
  function removeURLsToOtherDomains(&$links, &$actual_url) {

    $url_parts_actual=phpcrawlerutils::splitURL($actual_url);

    @reset($links);
    while (list($x)=@each($links)) {
      $url_parts_link=phpcrawlerutils::splitURL($links[$x]["url_rebuild"]);
      
      if ($url_parts_link["domain"]!=$url_parts_actual["domain"]) {
        $links[$x]="";
      }
    }
  }
  
  // This function is important:
  // It adds a bunch of found links (array $elements) to the mean_array.
  // Before, it checks if a link isn't already in there (with map_array)
  // The map-array contains all "cached" urls as key (the md5-hash)
  // The flag extended_linkinfo decides if arrays "linktext" etc. should be
  // kicked out (less memory-usage)
  function addToArray(&$elements, &$mean_array, &$map_array, $extended_linkinfo) {
    
    @reset($elements);
    while(list($x)=@each($elements)) {
      
      if (is_array($elements[$x])) {
        
        $map_key = md5($elements[$x]["url_rebuild"]);
        
        if(!isset($map_array[$map_key]) && $elements[$x]["url_rebuild"]!="") {
          
          // Add default-priority 0 if it isnt set
          if (!isset($elements[$x]["priority_level"]))
            $elements[$x]["priority_level"]=0;
          
          if ($extended_linkinfo==false) {
		          unset($elements[$x]["link_raw"]);
		          unset($elements[$x]["linkcode"]);
		          unset($elements[$x]["linktext"]);
          }
          
          // Add Link to the priority-array
          $mean_array[$elements[$x]["priority_level"]][]=$elements[$x];
          
          // Add hash to the map_array
          $map_array[$map_key]=true; 
        }
      }
    }
    
  }
  
  // function decides if the content of a page/file should be received,
  // depending on the preg_match(es) $follow_content_type
  
  function decideFollow(&$header, &$follow_content_type) {
  
    // if no follow_content_type was set -> follow EVERYTHINNG
    if (count($follow_content_type)==0) {
      return true;
    }
  
    // Get the content-type from header
    $content_type=phpcrawlerutils::getHeaderTag ("content-type", $header);

    // Should it be followed ?
    @reset($follow_content_type);
    while (list($x)=@each($follow_content_type)) {
      if (preg_match($follow_content_type[$x], $content_type)) {
        return true;
        break;
      }
    }
    
    return false;
    
  }
  
  // function decides if the content of a page/file should be received
  // tp memory or not, depending on the matches in match_array
  
  function decideStreamToMemory (&$header, &$match_array) {
  
    // if no match was set -> stream EVERYTHINNG to memory
    if (count($match_array)==0) {
      return true;
    }
    
    // Get the content-type from header
    $content_type=phpcrawlerutils::getHeaderTag ("content-type", $header);
    
    // Should it be received to memory ?
    @reset($match_array);
    while (list($x)=@each($match_array)) {
      if (preg_match($match_array[$x], $content_type)) {
        return true;
        break;
      }
    }
    
    return false;
  }
  
  // function decides if the content of a page/file should be received
  // to tmp-file or not, depending on the matches in match_array
  
  function decideStreamToTmpFile (&$header, &$match_array) {
    
    // Get the content-type from header
    $content_type=phpcrawlerutils::getHeaderTag ("content-type", $header);
    
    // Should it be received to memory ?
    @reset($match_array);
    while (list($x)=@each($match_array)) {
      if (preg_match($match_array[$x], $content_type)) {
        return true;
        break;
      }
    }
    
    return false;
  }
  
  // function simply checks a header for a redirect-location and returns it
  
  function getRedirectLocation(&$header) {
  
    // Get redirect-link from header
    preg_match_all("/((?i)location:|content-location:).{0,}[\n]/", $header,$regs);
    
    if (isset($regs[0][0])) {
      $redirect=preg_replace("/((?i)location:|content-location:)/", "", $regs[0][0]);
      $redirect=trim($redirect);
      return $redirect;
    }
    
    else {
      return false;
    }
    
  }
  
  // function removes all URLs from an array ($links) that lead to a page/file
  // ABOVE the path of the base url
  
  function removePathUpLinks(&$links, &$base_url)
  {
    @reset($links);
    while (list($key) = @each($links))
    {
      if (@substr($links[$key]["url_rebuild"], 0, strlen($base_url)) != $base_url)
      {
        $links[$key] = "";
      }
    }
  }
  
  // function removes URLs from a list of urls that match the
  // expressions in $filter_matches
  
  function removeMatchingLinks(&$links, &$filter_matches)
  {  
    @reset($links);
    while (list($key)=@each($links))
    {
      for ($x=0;$x<count($filter_matches); $x++)
      {
        if (@preg_match($filter_matches[$x], $links[$key]["url_rebuild"]))
        {
          $links[$key]="";
        }
      }
    }
  }
  
  // function removes URLs from a list of urls that DONT match the
  // expressions in $filter_matches (opposite of removeMatchingLinks)
  
  function &removeNotMatchingLinks(&$links, &$filter_matches) 
  {
    @reset($links);
    while (list($key)=@each($links))
    {
      if (is_array($links[$key]))
      {
        for ($x=0; $x<count($filter_matches); $x++) 
        {
          if (preg_match($filter_matches[$x], $links[$key]["url_rebuild"]) &&
              !isset($tmp_key_map[$links[$key]["url_rebuild"]])) 
          {
            $links_new[]=$links[$key];
            $tmp_key_map[$links[$key]["url_rebuild"]]=true;
          }
        }
      }
    }
    
    unset($links);
    return $links_new;
  }
  
  // function checks a header for cookie-data and stores it in $cookie_array
  
  function getCookieData(&$header, &$cookie_array, $host) {
  
    // Get cookie-part from header
    preg_match_all("/((?i)set-cookie:).{0,}[\n]/", $header, $regs);
    
    // Workaround: Strip "www." from host, cause www.foo.com is the
    // same host as "foo.com"
    $host_pure = preg_replace("/^www\./", "", $host);
    
    for ($x=0;$x<count($regs[0]);$x++) {
      
      // Extract cookie-data
      $line=preg_replace("/((?i)set-cookie:)/", "", $regs[0][$x]);
      $parts=explode(";", $line);
      $cookie_data=trim($parts[0]);
      $parts=explode("=", $cookie_data);
      
      $cookie_var = trim($parts[0]); // Cookie var
      $cookie_value = trim($parts[1]); // Cookie value
      
      // Add to cookie array if required
      $cookie_array[$host_pure][$cookie_var] = $cookie_value;
    }
  }
  
  // function searches the cookie_array for cookie_vars that should
  // be send to the current host and returns the "cookie:" header-line to send
  // f.e. "var1=val1; var2=val2"
  // cookie_array structure: $cookie_array[host][cookie_var] = cookie_value
  
  function &buildHeaderCookieString (&$cookie_array, $current_host) {
    
    // Workaround: Strip "www." from host, cause www.foo.com is the
    // same host as "foo.com"
    $current_host_pure = preg_replace("/^www\./", "", $current_host);
    
    @reset($cookie_array[$current_host_pure]);
    while(list($cookie_var)=@each($cookie_array[$current_host_pure])) {
      if (isset($cookie_string)) $cookie_string.="; ".$cookie_var."=".$cookie_array[$current_host_pure][$cookie_var];
      else $cookie_string="; ".$cookie_var."=".$cookie_array[$current_host_pure][$cookie_var];
    }
    
    if (isset($cookie_string))
      $cookie_string = substr($cookie_string, 2);
    
    return $cookie_string;
  }
  
  // function finds the URL in a <base href... Tag if exists
  
  function getBasePathFromTag (&$source) {
  
    preg_match("/<{1}[ ]{0,}((?i)base){1}[ ]{1,}((?i)href|src)[ ]{0,}=[ ]{0,}(\"|'){0,1}[^\"'><\n ]{0,}(\"|'|>|<|\n| )/ i", $source, $regs);
    if (isset($regs[0])) {
      $regs[0]=preg_replace("/((?i)href)[ ]{0,}=/", "", $regs[0]);
      $regs[0]=preg_replace("/^<{1}[ ]{0,}((?i)base){1}[ ]{0,}/", "", $regs[0]);
      $regs[0]=str_replace("\"", "", $regs[0]);
      $regs[0]=str_replace("'", "", $regs[0]);
      $regs[0]=str_replace(">", "", $regs[0]);
      $regs[0]=str_replace("<", "", $regs[0]);
      
      return $regs[0];
    }
  }
  
  // function xtracts the HTTP-status-code from a header
  
  function getHTTPStatusCode (&$header) {
  
    $first_line=strtok($header, "\n");
    preg_match("/ [0-9]{3}/", $first_line, $match);
    if (isset($match[0]))
      return trim($match[0]);
    else
      return 0;
  }
  
  // function gets a speacial "header-tag" form a header
  // ("Content-Type", "Content-Size" or whatever)
  
  function getHeaderTag ($tag, &$header)
  {
    preg_match_all("/((?i)".$tag.":).{0,}[\n]/", $header, $regs);
    
    if (isset($regs[0][0]))
    {
      $hit = preg_replace("/((?i)".$tag.":)/", "", $regs[0][0]);
      $hit = trim(strtolower($hit));
    }
    else
    {
      $hit = "";
    }
    return $hit;
  }
  
  // function checks if a multidimensional array build like
  // $a[0]["foo"]="bar"; $a[1]["foo"]="ahem"; ...
  // contains a special value for the keys "foo" (mdkey)
  
  function inMDArray($array, $needle, $mdkey) {
    
    $found=false;
    
    @reset($array);
    while (list($x)=@each($array)) {
      if ($array[$x][$mdkey]==$needle) {
        $found=true;
        break;
      }
    }
    
    return $found;
  }
  
  // Function adds the priority_level to a list of URLS
  // depending on the url-matches and levels given in the priority_array
  // WARNING: Matches the complete URL (with port).
  // So if the match is /www.foo.com\/foo/ and the URL is http://www.foo.com:80
  // -> doesnt match
  
  function addURLPriorities (&$url_array, &$priority_array) {
  
    @reset($url_array);
    while (list($key)=@each($url_array)) {
      for ($x=0; $x<count($priority_array); $x++) {
        if (preg_match($priority_array[$x]["match"], $url_array[$key]["url_rebuild"])) {
          $url_array[$key]["priority_level"]=$priority_array[$x]["level"];
        }
      }
      
      // Didnt match at all -> add lowest priority-level
      if (!isset($url_array[$key]["priority_level"])) {
        $url_array[$key]["priority_level"]="0";
      }
    }
    
  }
  
  // function checks if an authentication-login (username, password) should be
  // send with a request for $url depending on the given authentications in
  // $authentication_array
    
  function getAuthenticationForURL (&$authentication_array, $url)
  {
    $auth = array();
    for ($x=0; $x<count($authentication_array); $x++)
    {
      if (preg_match($authentication_array[$x]["match"], $url))
      {
        $auth["username"] = $authentication_array[$x]["username"];
        $auth["password"] = $authentication_array[$x]["password"];
        break;
      }
    }
    
    return $auth;
  }
  
  // Function not used at the moment
  
  function getPriorityLevelOffset ($array, $priority_level) {
    
    $c=count($array);
    $offset_new=ceil($c/2);
    
    while ($found==false) {
      $offset=$offset_new;
      flush();
      if ($offset==$offset_before) {
        return 0;
        $found=true;
      }
      if (($array[$offset]["level"]>=$priority_level && $array[$offset-1]["level"]<=$priority_level) || !$array[$offset]["level"]){
        $found=true;
        return $offset;
      }
      
      elseif ($array[$offset]["level"]<$priority_level && $array[$offset-1]["level"]<$priority_level){
        $offset_new=$offset+abs(ceil(($offset-$offset_before)/2));
      }
      
      elseif ($array[$offset]["level"]>$priority_level && $array[$offset-1]["level"]>$priority_level){
        $offset_new=$offset-abs(ceil(($offset-$offset_before)/2));
      }
      
      $offset_before=$offset;
    }
  
  }
  
  // Function checks if a given preg_pattern is a valid one.
  
  function checkExpressionPattern($pattern)
  {
    $check = @preg_match($pattern, "anything"); // thats the easy way to check a pattern ;)
    if (is_integer($check) == false) return false;
    else return true;
  }
  
  /**
   * Checks whether a given string matches with one of the given regular-expressions.
   *
   * @param &string $string      The string
   * @param array   $regex_array Numerich array containing the regular-expressions to check against.
   *
   * @return bool TRUE if one of the regexes matches the string, otherwise FALSE.
   */
  public function checkStringAgainstRegexArray($string, $regex_array)
  {
    if (count($regex_array) == 0) return true;
    
    for ($x=0; $x<count($regex_array); $x++)
    {
      if (preg_match($regex_array[$x], $string))
      {
        return true;
      }
    }
    
    return false;
  }
  
} 
?>