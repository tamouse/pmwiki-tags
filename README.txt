This script enables tagged sites like in flickr. Insert tags into the wikis
with this markup:

    (:tags keyword, Keyword, etc. :)

Insert tags on a page without showing them:

    (:tags-hide keyword keyword :)

Retrieve all Tags with the markup:

    (:listtags:)

Retrieve tags for the current group only:

    (:listgrouptags:)

The function HandleTags will generate Temporary Sites in the style of
Tag.Keyword

To use this script, simply copy it into the cookbook/ directory
and add the following line to config.php (or a per-page/per-group
customization file). include_once('cookbook/tags.php');


Copyright 2005 Michael  Vonrueden (mail@michael-vonrueden.de)
