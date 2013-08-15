<?php

//
// Laguna 2.0
//
// Copyright (C) 2008 Steven Frank <http://stevenf.com/>
// Code may be re-used as long as the above copyright notice is retained.
//
// Written with Coda: <http://panic.com/coda/>
//

require_once('config.php');

//
// Supporting functions
//

include_once "$binDir/markdown.php";

function formatText($bodyText)
{
	// This function takes Markdown formatted text and returns HTML.
	
	global $archiveURL;
	
	// The following regex adds support for [[This Markup Style]]
	//
	// Anything in double-square-brackets will be converted to lowercase,
	// have any non alphanumeric character replaced by a hyphen (-) and
	// linked to the equivalent archive page. (In this example: this-markup-style.php)
	
 	$bodyText = preg_replace("/\[\[(.*?)\]\]/e", "'<a href=\"$archiveURL/' . preg_replace(\"/[^A-Za-z0-9]/\", '-', strtolower('\\1')) . '.php\">\\1</a>'", $bodyText);

	// Get rid of the Title metadata line, if there is one

	$bodyText = preg_replace("/^Title: .*?$/m", "", $bodyText);
	
	// Run the page through Markdown and get HTML back
	
	return Markdown($bodyText);
}

//
// Main code
//

// !Enumerate pages folder

$pages = array();

if ( ($dir = opendir($pagesDir)) === false )	
	die;

while ( ($filename = readdir($dir)) !== false )
{
	if ( $filename{0} == '.' )	// Skip dot files
		continue;

	$moddate = filemtime("$pagesDir/$filename");
	$pages[$filename] = $moddate;	
}

closedir($dir);

// !Sort into reverse chronological order by mod time

arsort($pages);

// !Determine next/prev URLs for each page

$pageArray = array();

$lastFilename = "";
foreach ( $pages as $filename => $moddate )
{
	$pageArray[] = $filename;
	
	if ( $lastFilename != "" )
	{
		$archiveFilename = str_replace($inputFileExtension, $outputFileExtension, $lastFilename);
		$nextURL[$filename] = "$archiveURL/$archiveFilename";
	}
	
	$lastFilename = $filename;
}

$lastFilename = "";
for ( $i = count($pageArray) - 1; $i >= 0; --$i )
{
	if ( $lastFilename != "" )
	{
		$archiveFilename = str_replace($inputFileExtension, $outputFileExtension, $lastFilename);
		$prevURL[$pageArray[$i]] = "$archiveURL/$archiveFilename";
	}

	$lastFilename = $pageArray[$i];
}

//
// !Publish archive pages
//

/* system("/bin/rm -f $archiveDir/*");	// ugh */

$count = 0;

foreach ( $pages as $filename => $moddate )
{
	// Start buffering output 
	
	ob_start();
	
	// Get Markdown formatted text
		
	$bodyText = file_get_contents("$pagesDir/$filename");

	// If the page has 'Title:' metadata, use it.

	preg_match("/^Title: (.*?)$/m", $bodyText, $matches);
	$title = trim($matches[1]);

	if ( $title == "" )
		$title = $defaultPageTitle;

	// Write page header

	require "$templatesDir/header.php";

	// Write permalink/byline
	
	$archiveFilename = str_replace($inputFileExtension, $outputFileExtension, $filename);
	$date = date($bylineDateFormat, $moddate);
	
	require "$templatesDir/archive_byline.php";
	
	// Write HTML formatted page
	
	print formatText($bodyText);

	// Write "previous" link
	
	$prevLink = "<div id=\"previous\">";
	if ( $prevURL[$filename] != "" )
		$prevLink .= "<a href=\"{$prevURL[$filename]}\">&larr; Earlier</a>";
	else
		$prevLink .= "";
	$prevLink .= "</div>";

	// Write "next" link
	
	$nextLink = "<div id=\"next\">";
	if ( $nextURL[$filename] != "" )
		$nextLink .= "<a href=\"{$nextURL[$filename]}\">Later &rarr;</a>";
	else
		$nextLink .= "";
	$nextLink .= "</div>";
		
	// Write footer

	echo $prevLink . $nextLink . "<br clear=\"both\" />";
	require "$templatesDir/footer.php";
	
	// Get buffered output
	
	$output = ob_get_clean();

	// Write buffered output to file

	$errLevel = error_reporting(0);
	if ( file_put_contents("$archiveDir/$archiveFilename", $output) === false )
	{
		print "<p>Could not write to file: $archiveDir/$archiveFilename</p>";
		print "<p>Please make sure this file is writable by the web server process</p>";
		die;
	}
	error_reporting($errLevel);
	
	++$count;
}

//
// !Publish index page
//

// Start buffering output

ob_start();

$count2 = 0;

foreach ( $pages as $filename => $moddate )
{
	// Get Markdown formatted text
	
	$bodyText = file_get_contents("$pagesDir/$filename");

	if ( $count2 == 0 )
	{
		// Write the page header if this is the first time through the loop
		
		$title = $indexPageTitle;
		require "$templatesDir/header.php";
	}

	// Write permalink/byline
	
	$archiveFilename = str_replace($inputFileExtension, $outputFileExtension, $filename);
	$date = date($bylineDateFormat, $moddate);
	
	echo "<h1><a href=\"$archiveURL/$archiveFilename\">$date</a>\n";
	echo "<div class=\"time\"><br />" . date($bylineTimeFormat, $moddate) . "</div>\n";
	echo "</h1>\n";

	// Write HTML formatted text
	
	echo formatText($bodyText);
			
	// Continue until maximum posts have been added, or until running out of posts,
	// whichever comes first
	
	$lastFilename = $filename;	
	$lastDate = $date;

	if ( ++$count2 == $maxIndexPagePosts || $count == $count2 )
		break;
		
	echo "<br /><br />";
}

// Write footer

echo "<div id=\"previous\"><a href=\"{$prevURL[$lastFilename]}\">&larr; Earlier Posts</a></div>";
echo "<div id=\"next\"><a href=\"$archiveURL/\">Archive &rarr;</a></div>";
echo "<br clear=\"all\" />";

require "$templatesDir/footer.php";

// Write out buffered text

$output = ob_get_clean();

$errLevel = error_reporting(0);
if ( file_put_contents("$publishDir/index.php", $output) === false )
{
	print "<p>Could not write to file: $publishDir/index.php</p>";
	print "<p>Please make sure this file is writable by the web server process</p>";
	die;
}
error_reporting($errLevel);

//
// !Publish archive index page
//

ob_start();
	
$title = $archivePageTitle;
require "$templatesDir/header.php";

echo "<h1>All Posts</h1>\n";

foreach ( $pages as $filename => $moddate )
{
	// Attempt to extract title from page
	
	$bodyText = file_get_contents("$pagesDir/$filename");
	preg_match("/^Title: (.*?)$/m", $bodyText, $matches);
	$title = trim($matches[1]);

	if ( $title == "" )
		$title = $defaultPageTitle;

	$html = formatText($bodyText);
	
	// Get formatted date of post
	
	$archiveFilename = str_replace($inputFileExtension, $outputFileExtension, $filename);
	$date = date("Y-m-d", $moddate);

	// Write a line to the archive page
	
	echo "<p>$date&nbsp;&nbsp;&nbsp;";
	echo "<a href=\"$archiveURL/$archiveFilename\">$title</a></p>";
}

require "$templatesDir/footer.php";

$output = ob_get_clean();

$errLevel = error_reporting(0);
if ( file_put_contents("$archiveDir/index.php", $output) === false )
{
	print "<p>Could not write to file: $archiveDir/index.php</p>";
	print "<p>Please make sure this file is writable by the web server process</p>";
	die;
}
error_reporting($errLevel);


//
// !Publish RSS feed
//

// Start buffering output

ob_start();

// Write RSS header

require "$templatesDir/rss_header.php";

$count3 = 0;

foreach ( $pages as $filename => $moddate )
{
	$pubdate = date("D, d M Y H:i:s", $moddate) . $timezone;

	$bodyText = file_get_contents("$pagesDir/$filename");

	// Attempt to extract title from page
	
	preg_match("/^Title: (.*?)$/m", $bodyText, $matches);
	$title = trim($matches[1]);
	$html = formatText($bodyText);
	
	if ( $title == "" )
		$title = $defaultPageTitle;

	// Set up other variables for template

	$archiveFilename = str_replace($inputFileExtension, $outputFileExtension, $filename);
	$permalink = "$archiveURL/$archiveFilename";

	$date = date($bylineDateFormat, $moddate);

	// Write RSS item
	
	require "$templatesDir/rss_item.php";
	
	// Don't exceed maximum items
	
	if ( ++$count3 == $maxRSSItems )
		break;
}

// Write RSS footer

require "$templatesDir/rss_footer.php";

// Write out buffered text

$output = ob_get_clean();

$errLevel = error_reporting(0);
if ( file_put_contents("$publishDir/$rssFilename", $output) === false )
{
	print "<p>Could not write to file: $publishDir/$rssFilename</p>";
	print "<p>Please make sure this file is writable by the web server process</p>";
	die;
}
error_reporting($errLevel);


// Redirect to published site

header("Location: $baseURL");

?>