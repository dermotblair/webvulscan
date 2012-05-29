<?php
/////////////////////////////////////////////////////////
// PHPCrawl
// - class PHPCrawlerRobotsTxtHandler:
// 
// v 0.71
//
// Class for processing/parsing robots.txt-files and storing
// containig directives.
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
  
class PHPCrawlerRobotsTxtHandler
{
  // Array collects all disallowed URL-pathes (as reg_exps)
  // for the different hosts.
  // There's an element for each host, and each element contains
  // an numeric array with all the disallowed path-regexps again.
  // I.e.
  // $disallowedPathRegExps["http://www.foo.com:80"][0] = "#^www\.foo\.com:80/disallowed_path/#"
  // $disallowedPathRegExps["http://www.foo.com:80"][1] = "#^www\.foo\.com:80/another/path/#"
  // $disallowedPathRegExps["http://www.bar.com:80"][1] = "#^www\.bar\.com:80/forbidden/#"
  var $disallowedPathRegExps = array();
  
  // Function checks if the given URL is disallowed by the robots.txt-file
  // of the given host. Returns TRUE if it IS disallowed.
  function checkIfUrlDisallowed($url, $host_url)
  {
    // Iterate through the host-array and compare the url with the
    // disallow-pathes.
    for ($x=0; $x<count($this->disallowedPathRegExps[$host_url]); $x++)
    {
      $regexp = $this->disallowedPathRegExps[$host_url][$x];
      if (preg_match($regexp, $url))
      {
        return true;
      }
    }
    
    return false;
  }
  
  // Function reads the robots.txt-file of the given host and extracts all disallowed
  // pathes adressed to the given user-agent.
  function processRobotsTxt($protocol, $host, $port, $user_agent_string)
  {
    $disallowed_path_regexps = $this->getDisallowedPathRegexps($protocol, $host, $port, $user_agent_string);
    
    // Add the disallow-regexps to the disallowedPathRegExps-array
    $key = $protocol.$host.":".$port;
    $this->disallowedPathRegExps[$key] = $disallowed_path_regexps;
  }
  
  // Function extracts all disallowed pathes applying to the given user-agent from
  // the robots.txt-file of the given host.
  // Afterwards it converts these pathes into valid regual erxpressions that
  // can directly be used to match against URLs.
  function getDisallowedPathRegexps($protocol, $host, $port, $user_agent_string)
  {
    $base_url = $protocol.$host.":".$port;
    
    // Get robots.txt-content
    $robots_txt_content = $this->getRobotsTxtContent($base_url);
    
    // If content was found
    if ($robots_txt_content != false)
    {
      // Get all lines in the robots.txt-content that are adressed directly to our User-agent.
      $applying_lines = $this->getApplyingLines($robots_txt_content, $user_agent_string);
      
      // If no applying lines were found -> look again for general directives (User-agent: *)
      if (count($applying_lines) == 0)
      {
        $applying_lines = $this->getApplyingLines($robots_txt_content, '\\*');
      }
      
      // Get valid reg-expressions for the given disallow-pathes.
      $non_follow_reg_exps = $this->buidlNonFollowMatches($applying_lines, $base_url);
      
      return $non_follow_reg_exps;
    }
  }
  
  // Function returns all RAW lines in the given robots.txt-content that apply to
  // the given Useragent-string.
  function getApplyingLines($robots_txt_content, $user_agent_string)
  {
    // Split the content into its lines
    $robotstxt_lines = explode("\n", $robots_txt_content);
    
    // Flag that will get TRUE if the loop over the lines gets
    // into a section that applies to our user_agent_string 
    $matching_section = false;
    
    // Flag that indicats if the loop is in a "agent-define-section"
    // (the parts/blocks that contain the "User-agent"-lines.)
    $agent_define_section = false;
    
    // Flag that indicates if we have found a section that fits to our
    // User-agent
    $matching_section_found = false;
    
    // Array to collect all the lines that applie to our user_agent
    $applying_lines = array();
    
    // Loop over the lines
    for ($x=0; $x<count($robotstxt_lines); $x++)
    {
      $robotstxt_lines[$x] = trim($robotstxt_lines[$x]);
      
      // Check if a line begins with "User-agent"
      if (preg_match("#^User-agent:# i", $robotstxt_lines[$x]))
      {
        // If a new "user-agent" section begins -> reset matching_section-flag
        if ($agent_define_section == false)
        {
          $matching_section = false;
        }
        
        $agent_define_section = true; // Now we are in an agent-define-section
        
        // The user-agent specified in the "User-agent"-line
        preg_match("#^User-agent:[ ]*(.*)$#", $robotstxt_lines[$x], $match);
        $user_agent_section = trim($match[1]);
        
        // Remove all "*" from the given User-Agent (only if User-Agent istnt simply "*", important)
        // This is just because there are a lot of diretives out there like "User-agent: foobot*",
        // but i cant find this Wildcard-definition in the RFC (am i missing something?)
        if ($user_agent_section != "*")
        {
          $user_agent_section = str_replace("*", "", $user_agent_section);
        }
        
        // if the specified user-agent in the line fits to our user-agent-String (* fits always)
        // -> switch the flag "matching_section" to true
        $user_agent_section = preg_quote($user_agent_section);  
        if (preg_match("#".$user_agent_section."# i", $user_agent_string))
        {
          $matching_section = true;
          $matching_section_found = true;
        }
        
        continue; // Don't do anything else with the "User-agent"-lines, just go on
      }
      else
      {
        // We are not in an agent-define-section (anymore)
        $agent_define_section = false;
      }
      
      // If we are in a section that applies to our user_agent
      // -> store the line.
      if ($matching_section == true)
      {
        $applying_lines[] = $robotstxt_lines[$x];
      }
      
      // If we are NOT in a matching section (anymore) AND we've already found
      // and parsed a matching section -> stop looking further (thats what RFC says)
      if ($matching_section == false && $matching_section_found == true)
      {
        break;
      }
    }
    
    return $applying_lines;
  }
  
  // This function returns an array containig regular-expressions corresponding
  // to the given "Disallow"-lines and the given base-url.
  // Other lines (i.e. "Allow:" or comments) will be ignored here.
  function buidlNonFollowMatches($applying_lines, $base_url)
  { 
    // First, get all "Disallow:"-pathes
    $disallow_pathes = array();
    for ($x=0; $x<count($applying_lines); $x++)
    {
      if (preg_match("#^Disallow:# i", $applying_lines[$x]))
      {
        preg_match("#^Disallow:[ ]*(.*)#", $applying_lines[$x], $match);
        $disallow_pathes[] = trim($match[1]);
      }
    }
    
    // Works like this:
    // The base-url is http://www.foo.com.
    // The driective says: "Disallow: /bla/"
    // This means: The nonFollowMatch is "#^http://www\.foo\.com/bla/#"
    
    $normalized_base_url = PHPCrawlerUtils::normalizeURL($base_url);
    
    $non_follow_expressions = array();
    
    for ($x=0; $x<count($disallow_pathes); $x++)
    {
      // If the disallow-path is empty -> simply ignore it
      if ($disallow_pathes[$x] == "") continue;
      
      $non_follow_path_complpete = $normalized_base_url.substr($disallow_pathes[$x], 1); // "http://www.foo.com/bla/"
      $non_follow_exp = preg_quote($non_follow_path_complpete, "#"); // "http://www\.foo\.com/bla/"
      $non_follow_exp = "#^".$non_follow_exp."#"; // "#^http://www\.foo\.com/bla/#"
        
      $non_follow_expressions[] = $non_follow_exp;
    }
    
    return $non_follow_expressions;
  }
  
  // Function retreives the content of the robots-txt-file of the given
  // base_url.
  // Returns FALSE if no robots.txt-file was found.
  function getRobotsTxtContent($base_url)
  {
    // Robots.txt-URL
    $robotstxt_url = $base_url . "/robots.txt";
    
    // Init a new PageRequest-Object
    $Request = &new PHPCrawlerPageRequest();
    $page_data = $Request->receivePage($robotstxt_url, "");

    // Return content of the robots.txt-file if it was found, otherwie
    // reutrn FALSE
    if ($page_data["http_status_code"] == 200)
    {
      return $page_data["source"];
    }
    else
    {
      return false;
    }
  }
}
  
?>