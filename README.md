laguna-blog
===========

Laguna is an extremely lightweight blogging engine.

Instructions
------------

Edit the config.php file to reflect the layout of your site. In particular, set a password for the editor (edit.php).

The pages directory contains Markdown formatted text files that will appear as posts. All you do is put files in there, and invoke publish.php. It will generate an index page, individual archive pages, an archive index, and an RSS feed, based on the modification dates of the files in pages. Then it redirects you to the index page.

You don't need to use the editor script at all if you don't want to -- you can just edit the text files in the pages directory and re-run publish.php.

This code is kind of a mess right now, as the publisher and editor were lashed together from different projects. But it basically works, and ran my blog for quite some time.

