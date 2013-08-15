<?php

/*
 * W2 1.0.2
 *
 * Copyright (C) 2007 Steven Frank <http://stevenf.com/>
 * Code may be re-used as long as the above copyright notice is retained.
 * See README.txt for full details.
 *
 * Written with Coda: <http://panic.com/coda/>
 */
 
include_once "markdown.php";

// User configurable options:

include_once "config.php";

ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);

session_set_cookie_params(60 * 60 * 24 * 30);
session_name("W2Laguna");
session_start();

if ( REQUIRE_PASSWORD && !isset($_SESSION['password']) )
{
	if ( !defined('W2_PASSWORD_HASH') )
		define('W2_PASSWORD_HASH', sha1(W2_PASSWORD));
	
	if ( (isset($_POST['p'])) && (sha1($_POST['p']) == W2_PASSWORD_HASH) )
		$_SESSION['password'] = W2_PASSWORD_HASH;
	else
	{
		print "<html><body><form method=\"post\">Password: <input type=\"password\" name=\"p\"></form>";
		print "</body></html>";
		exit;
	}
}

// Support functions

function printToolbar()
{
	global $upage, $page, $action;

	print "<div id=\"tagline\">";
	print "<a href=\"" . SELF . "?action=edit&amp;page=$upage\">Edit This</a> - ";
	print "<a href=\"" . SELF . "?action=new\">New Page</a> - ";

	if ( !DISABLE_UPLOADS )
		print "<a href=\"" . SELF . "?action=upload\">Upload</a> - ";

 	print "<a href=\"" . SELF . "?action=all_name\">All Pages</a> - ";
	print "<a href=\"" . SELF . "?action=all_date\">Recent</a> - ";
	print "<a href=\"publish.php\">Publish</a> ";
 	
	if ( REQUIRE_PASSWORD )
		print ' - <a href="' . SELF . '?action=logout">Logout</a>';
		
	print "</div>\n";
}

function toHTML($inText)
{
	global $page;

 	$inText = preg_replace("/\[\[(.*?)\]\]/", "<a href=\"" . SELF . "/\\1\">\\1</a>", $inText);
	$inText = preg_replace("/\{\{(.*?)\}\}/", "<img src=\"images/\\1\" alt=\"\\1\" />", $inText);
	$inText = preg_replace("/message:(.*?)\s/", "[<a href=\"message:\\1\">email</a>]", $inText);

	$html = Markdown($inText);

	return $html;
}

function sanitizeFilename($inFileName)
{
	return str_replace(array('..', '~', '/', '\\', ':'), '-', $inFileName);
}

function destroy_session()
{
	if ( isset($_COOKIE[session_name()]) )
	{
		setcookie(session_name(), '', time() - 42000, '/');
	}
	session_destroy();
	unset($_SESSION["password"]);
	unset($_SESSION);
}

// Support PHP4 by defining file_put_contents if it doesn't already exist

if ( !function_exists('file_put_contents') )
{
    function file_put_contents($n, $d)
    {
		$f = @fopen($n, "w");
		if ( !$f )
		{
			return false;
		}
		else
		{
			fwrite($f, $d);
			fclose($f);
			return true;
		}
    }
}

// Main code

if ( isset($_REQUEST['action']) )
	$action = $_REQUEST['action'];
else 
	$action = '';

if ( preg_match('@^/@', @$_SERVER["PATH_INFO"]) ) 
	$page = sanitizeFilename(substr($_SERVER["PATH_INFO"], 1));
else 
	$page = sanitizeFilename(@$_REQUEST['page']);

$upage = urlencode($page);

if ( $page == "" )
	$page = DEFAULT_PAGE;

$filename = PAGES_PATH . "/$page.txt";

if ( file_exists($filename) )
{
	$text = file_get_contents($filename);
}
else
{
	if ( $action != "save" && $action != "all_name" && $action != "all_date" && $action != "upload" && $action != "new" && $action != "logout" && $action != "uploaded" )
	{
		$action = "all_date";
	}
}

if ( $action == "edit" || $action == "new" )
{
	$formAction = SELF . (($action == 'edit') ? "/$page" : "");
	$html = "<form id=\"edit\" method=\"post\" action=\"$formAction\">\n";
	
	if ( $action == "edit" )
		$html .= "<input type=\"hidden\" name=\"page\" value=\"$page\" />\n";
	else
		$html .= "<p>Title: <input id=\"title\" type=\"text\" value=\"use-lowercase-and-dashes\" name=\"page\" /></p>\n";

	if ( $action == "new" )
		$text = "Title: ";

	$text = htmlentities($text);
	
	if ( $page != DEFAULT_PAGE || ($page == DEFAULT_PAGE && $action == "new") )
	{
		$html .= "<p><textarea id=\"text\" name=\"newText\" cols=\"60\" rows=\"" . EDIT_ROWS . "\">$text</textarea></p>\n";
		$html .= "<p><input type=\"hidden\" name=\"action\" value=\"save\" />";
		$html .= "<input id=\"save\" type=\"submit\" value=\"Save\" />\n";
		$html .= "<input id=\"cancel\" type=\"button\" onclick=\"history.go(-1);\" value=\"Cancel\" /></p>\n";
	}
	$html .= "</form>\n";
}
else if ( $action == "logout" )
{
	destroy_session();
	header("Location: " . SELF);
	exit;
}
else if ( $action == "upload" )
{
	if ( DISABLE_UPLOADS )
	{
		$html = "<p>Image uploading has been disabled on this installation.</p>";
	}
	else
	{
		$html = "<form id=\"upload\" method=\"post\" action=\"" . SELF . "\" enctype=\"multipart/form-data\"><p>\n";
		$html .= "<input type=\"hidden\" name=\"action\" value=\"uploaded\" />";
		$html .= "<input id=\"file\" type=\"file\" name=\"userfile\" />\n";
		$html .= "<input id=\"upload\" type=\"submit\" value=\"Upload\" />\n";
		$html .= "<input id=\"cancel\" type=\"button\" onclick=\"history.go(-1);\" value=\"Cancel\" />\n";
		$html .= "</p></form>\n";
	}
}
else if ( $action == "uploaded" )
{
	if ( !DISABLE_UPLOADS )
	{
		$dstName = sanitizeFilename($_FILES['userfile']['name']);
		$fileType = $_FILES['userfile']['type'];
		preg_match('/\.([^.]+)$/', $dstName, $matches);
		$fileExt = isset($matches[1]) ? $matches[1] : null;
		
		if (in_array($fileType, explode(',', VALID_UPLOAD_TYPES)) &&
			in_array($fileExt, explode(',', VALID_UPLOAD_EXTS)))
		{
			if ( move_uploaded_file($_FILES['userfile']['tmp_name'], 
				BASE_PATH . "/images/$dstName") === true ) 
			{
				$html = "<p class=\"note\">File '$dstName' uploaded</p>\n";
			}
			else
			{
				$html = "<p class=\"note\">Upload error</p>\n";
			}
		} else {
			$html = "<p class=\"note\">Upload error: invalid file type</p>\n";
		}
	}

	$html .= toHTML($text);
}
else if ( $action == "save" )
{
	$newText = trim(stripslashes($_REQUEST['newText']));

	$errLevel = error_reporting(0);
	$success = file_put_contents($filename, $newText);
 	error_reporting($errLevel);

	if ( $success )	
		$html = "<p class=\"note\">Saved</p>\n";
	else
		$html = "<p class=\"note\">Error saving changes! Make sure your web server has write access to " . PAGES_PATH . "</p>\n";

	$html .= toHTML($newText);
}
else if ( $action == "all" )
{
	$html = "<ul>\n";
	$dir = opendir(PAGES_PATH);
	
	while ( $file = readdir($dir) )
	{
		if ( $file{0} == "." )
			continue;

		$file = preg_replace("/(.*?)\.txt/", "<a href=\"" . SELF . "/\\1\">\\1</a>", $file);
		$html .= "<li>$file</li>\n";
	}

	closedir($dir);
	$html .= "</ul>\n";
}
else if ( $action == "all_name" )
{
	$html = "<ul>\n";
	$dir = opendir(PAGES_PATH);
	$filelist = array();
	while ( $file = readdir($dir) )
	{
		if ( $file{0} == "." )
			continue;

		$file = preg_replace("/(.*?)\.txt/", "<a href=\"" . SELF . "/\\1\">\\1</a>", $file);
		array_push($filelist, $file);
	}

	closedir($dir);

	natcasesort($filelist);
	for ($i = 0; $i < count($filelist); $i++)
	{
		$html .= "<li>" . $filelist[$i] . "</li>\n";
	}

	$html .= "</ul>\n";
}
else if ( $action == "all_date" )
{
	$html = "<ul>\n";
	$dir = opendir(PAGES_PATH);
	$filelist = array();
	while ( $file = readdir($dir) )
	{
		if ( $file{0} == "." )
			continue;
			
		$filelist[preg_replace("/(.*?)\.txt/", "<a href=\"" . SELF . "/\\1\">\\1</a>", $file)] = filemtime(PAGES_PATH . "/$file");
	}

	closedir($dir);

	arsort($filelist, SORT_NUMERIC);
	foreach ($filelist as $key => $value)
	{
		$html .= "<li>$key (" . date(TITLE_DATE, $value) . ")</li>\n";
	}
	$html .= "</ul>\n";
}
else
{
	$html = toHTML($text);
}

$datetime = '';

if (( $action == "all" ) || ( $action == "all_name") || ($action == "all_date"))
	$title = "All Pages";
else if ( $action == "upload" )
	$title = "Upload Image";
else if ( $action == "new" )
	$title = "New";
else
{
	$title = $page;
	if ( TITLE_DATE )
	{
		$datetime = date(TITLE_DATE, @filemtime($filename));
	}
}

// Disable caching on the client (the iPhone is pretty agressive about this
// and it can cause problems with the editing function)

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
print "<html>\n";
print "<head>\n";

// Define a viewport that is 320px wide and starts with a scale of 1:1 and goes up to 2:1

print "<meta name=\"viewport\" content=\"width=320; initial-scale=1.0; maximum-scale=2.0;\" />\n";

print "<link type=\"text/css\" rel=\"stylesheet\" href=\"" . BASE_URI . "/" . CSS_FILE ."\" />\n";
print "<title>$title</title>\n";
print "</head>\n";
print "<body>\n";
print "<div id=\"sitebanner\">Laguna</div>";
print "<div id=\"tagline\"><b>Editor</b></div>";
		
printToolbar();

if ( $datetime != '' )
{
	$bylineDate = date("F j, Y", @filemtime($filename));
	$bylineTime = date("g:i A",	@filemtime($filename));		
	
	print "<h1>$bylineDate<div class=\"time\"><br />$bylineTime</div></h1>\n";
}
else
{
	print "<h1>$title</h1>";
}

print "$html\n";

print "</body>\n";
print "</html>\n";

?>
