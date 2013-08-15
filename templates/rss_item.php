<?php

	echo '        <item>' . "\n";
	echo '            <title>' . $title . '</title>' . "\n";
	echo '            <description><![CDATA[' . $html . ']]></description>' . "\n";
	echo '            <link>' . $permalink . '</link>' . "\n";
	echo '            <guid>' . $permalink . '</guid>' . "\n";
	echo '            <pubDate>' . $pubdate . '</pubDate>' . "\n";
	echo '        </item>' . "\n";

?>