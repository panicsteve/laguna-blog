<?php

echo '<' . '?xml version="1.0" encoding="UTF-8"?' .'>' . "\n";
echo '<rss version="2.0">' . "\n";
echo '    <channel>' . "\n";
echo '        <title>' . $rssTitle . '</title>' . "\n";
echo '        <link>' . $rssLink . '</link>' . "\n";
echo '        <description>' . $rssDesc . '</description>' . "\n";
echo '        <language>' . $rssLang . '</language>' . "\n";
echo '        <copyright>' . $rssCopyright . '</copyright>' . "\n";
echo '        <lastBuildDate>' . date("D, d M Y H:i:s", time()) . $timezone . '</lastBuildDate>' . "\n";
echo '        <generator>' . $rssGenerator .'</generator>' . "\n";
echo '        <docs>http://www.rssboard.org/rss-specification</docs>' . "\n";

?>