Preview
================

Wordpress style preview for ExpressionEngine/Structure

Note: requires a core-hack / patch.

## Install

1. Install the extension
2. In your status group add two new statuses: "Preview" (#FFF019) and "Draft" (#A352CC). They need to have those exact names, although not neccessarily those colours.
3. Apply the patch found in libraries/Template.php.patch (it will add a new hook to the fetch_param() method in Template.php)