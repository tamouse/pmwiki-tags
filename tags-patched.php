<?php if (!defined('PmWiki')) exit();
/**
	*   Copyright 2005 Michael  Vonrueden (mail@michael-vonrueden.de)
	* 	This file is tags.php; you can redistribute it and/or modify
	* 	it under the terms of the GNU General Public License as published
	* 	by the Free Software Foundation; either version 2 of the License, or
	* 	(at your option) any later version.  
	* 
	* 	This script enables tagged sites like in flickr. Insert tags into the wikis 
	* 	with this markup:
* 		(:tags keyword, Keyword, etc. :)
	*     Insert tags on a page without showing them:
* 		(:tags-hide keyword keyword :)
	* 	Retrieve all Tags with the markup:
* 		(:listtags:)
	* 	Retrieve tags for the current group only:
* 		(:listgrouptags:)
	* 
	* 	The function HandleTags will generate Temporary Sites in the style of 
	* 	Tag.Keyword
	* 
	* 	To use this script, simply copy it into the cookbook/ directory
* 	and add the following line to config.php (or a per-page/per-group
	* 	customization file). include_once('cookbook/tags.php');
* 
	* Changes 
	* 
	* 	2012-03-03 V1.1.1 [Tamara Temple (tpt) <tamara@tamaratemple.com>]
	* 	* clean up output of tag listing
	* 
	* 	May, 21th 2011 V1.1 [Daniel Kasmeroglu, Angela Kille, Kaushik Sridharan, Joshua Hall-Bachner]
	* 	* Applied public patches from http://www.pmwiki.org/wiki/Cookbook/Tags .
* 	* Removal of unnecessary comma separators.
	* 	* Correct selection of tagged pages if one tag is part of another one.
	* 	* In addition to 'listtags' there's now a 'listgrouptags' available.
	* 	* The font size of the cloud can be limited now through the parameter
	* 	'$tags_size_limit'.
	* 	* Added colored highlighting of page related tags for 'listtags' if the
	* 	  option 'showselected' is supplied.
	* 	* Tags can be declared using 'tags-hide' so they won't be displayed on
	* 	  the page (useful in conjunction with (:listtags showselected:).
	* 	* Prefix with the tag listing is configurable now ($tags_title_prefix).
	* 	  Aug, 31th 2005 V1.0 * Initial Development
	* 
	*/

$HandleActions+=array('tags'=>"HandleTags");

/* Can be overridden in 'local/conf.php' to provide a different title. */
$tags_title_prefix="Sites that are tagged with: ";

$tags_prefix="Tags";

/* If not 0 this value declares the maximum allowed font size within the tag cloud. */
$tags_size_limit=0;

/* All tags currently declared on this page. */
$tags=array();

Markup("tags", "directives", '/\\(:tags\\s(.*?):\\)/ei', "Tagger('$1',false)");
Markup("tags-hide", "directives", '/\\(:tags-hide\\s(.*?):\\)/ei', "Tagger('$1',true)");
Markup("listtags", "directives", '/\\(:listtags(.*?):\\)/ei', "ListTags('$1')");
Markup("listgrouptags", "directives", '/\\(:listgrouptags\\s(.*?):\\)/ei', "ListGroupTags('$1')");


function Tagger($i,$hide) {
	global $action;
	global $tags;
	$currenttags = explode(",",$i);
	$tags = array_merge($tags, $currenttags);
	if ($hide) 
	{
		return '';
	} 
	else 
	{
		$output = "<div class='tags'>";
		$first  = true;
		foreach ($currenttags as $tag)
		{
			$tag=trim($tag);
			if ($first===false) 
			{
				$output = $output.', ';
			}
			$first = false;
			$output=$output.'<a href="'.$ScriptUrl.'?action=tags&amp;tag='.$tag.'">'.$tag.'</a>';
		}
		return $output."</div>";
	}
}

function HandleTags()
{
	global $tags_prefix;
	global $tags_title_prefix;
	$taggedPages;
	$tag = $_GET["tag"];
	$pagelist = ListPages();
	foreach ($pagelist as $pagename)
	{
		$page=ReadPage($pagename, READPAGE_CURRENT);
		if (preg_match('/\\(:tags\\s.*?\b'.$tag.'\b.*?:\\)/i',$page['text']) || 
			preg_match('/\\(:tags-hide\\s.*?\b'.$tag.'\b.*?:\\)/i',$page['text'])) 
		{
			$name=explode(".",$page['name']);
			$taggedPages=$taggedPages.'*[['.$name[1].'->'.$pagename.']] ';
			$taggedPages=$taggedPages." \n";
		}
	}
	$text = $tags_title_prefix." @@".$tag."@@ \n\n";
	$page = array("text"=>$text.$taggedPages);
	$sitename=$tags_prefix.".".ucfirst(str_replace(" ","",$tag));
	WritePage($sitename,$page);
	Redirect($sitename);
}

function __LoadTags($text)
{
	$count1 = preg_match('/\\(:tags\\s(.*?):\\)/ei',$text, $matches1);
	$count2 = preg_match('/\\(:tags-hide\\s(.*?):\\)/ei',$text, $matches2);
	$result = array();
	if($count1 > 0)
	{
		$result = array_merge($result, explode(",", substr($matches1[0], 6, -2)));
	}
	if($count2 > 0)
	{
		$result = array_merge($result, explode(",", substr($matches2[0], 11, -2)));
	}
	return $result;
}

function __GenerateTagList($pagelist, $showselected)
{
	global $tags;
	global $tags_size_limit;
	$tagcount=array();
	$tagselection=array();
	$output=''; // DO NOT START WITH ANY WHITE SPACE -- this will trigger a <pre></pre> environment around your tag cloud
	foreach ($pagelist as $pagename)
	{
		$page=ReadPage($pagename, READPAGE_CURRENT);
		$matched_tags = __LoadTags($page['text']);
		foreach($matched_tags as $value)
		{
			// CHANGED: 2012-03-03 tpt -- test if the value looks like a page variable, such as: {*$:Tags}
			if (preg_match('/{\*?\$:?[[:alnum:]]+}/',$value))
			{
				continue; // don't tag page variables
			}
			$key=ucfirst(trim($value));
			if (empty($key)) {
				continue; // don't save empty tags
			}
			$tagcount[$key]+=1;
			$tagselection[$key] = in_array($value, $tags);
		}
	}
	ksort($tagcount);
	foreach ($tagcount as $tag=>$value)
	{
		$size = $value + 10;
		if ($tags_size_limit > 0)
		{
			if ($size > $tags_size_limit)
			{
				$size = $tags_size_limit;
			}
		}
		// CHANGED: 2012-03-03 tpt -- reduce branching by setting a variable instead of repeating the code
		$tagclasslabel = ($tagselection[$tag] && $showselected) ? 'listtagavailable' : 'listtag';
		// CHANGED: 2012-03-03 tpt -- output each tag on a single line, no break in the middle
		$output=$output.'<span class="'.$tagclasslabel.'" style="font-size:'.$size.'px;font-weight:'.($value+500).'">' .
			'<a href="'.$ScriptUrl.'?action=tags&amp;tag='.$tag.'">'.$tag.'</a></span> '.PHP_EOL;
	}
	return $output;
}

function ListTags($i)
{
	$showselected = preg_match('/\\s\bshowselected\b/ei',$i) > 0;
	$pagelist = ListPages();
	return __GenerateTagList($pagelist, $showselected);
}

function ListGroupTags($i)
{
	global $tags;
	$showselected = false;
	$parameters = explode(" ", $i);
	foreach ($parameters as $parameter)
	{
		$parameter = trim($parameter);
		if ($parameter === "showselected")
		{
			$showselected = true;
		}
		else 
		{
			$group = $parameter;
		}
	}
	$pagelist = ListPages("/^$group.*/");
	return __GenerateTagList($pagelist, $showselected);
}