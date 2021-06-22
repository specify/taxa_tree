# Taxa Tree Generator (Catalogue)

This website generates a taxa tree compatible with Workbench and Wizard
from [Specify 6](https://github.com/specify/specify6) and [Specify
7](https://github.com/specify/specify7).

## Data credit

The data used by this tool comes from [Catalogue of Life](http://catalogueoflife.org/).

## Requirements

1. PHP 7.2+ (older versions may work)
1. [PHP Zip](https://stackoverflow.com/questions/18774568/installing-php-zip-extension)
1. Any Webserver

## Installation

Rename `./front_end/config/required.php.example` to
`./front_end/config/optional.php`. This file contains main configuration
options.

1. Set `LINK` to an address the website would be served on.
1. Set `WORKING_LOCATION` to an empty folder. This would be the
   destination for all the files created in the process. Make sure the
   webserver and the user that would execute `back_end/refresh_data.py`
   has **READ** and **WRITE** permissions to this folder.

   **Warning!** Files present in this directory may be deleted.
1. Run `back_end/refresh_data.py`. This will automatically check for
   new versions of the taxa tree and download it. **You should also
   setup a daily or monthly CRON job for this file to keep your data
   up to date**
1. Configure your webserver to point to `front_end` directory.


### Optional settings

You can go over the other settings in the
`./front_end/config/optional.php` file and see if there is anything you
would like to adjust.

For example:
1. You can configure stats reporting by making `STATS_URL` point to a
location of `collect/` script from [Taxa Tree Stats
reporter](https://github.com/specify/taxa_tree_stats)

## Credit for used resources

There were snippets of code/files from the following resources used:

- [Bootstrap 4.5.0](https://github.com/twbs/bootstrap)
- [jQuery 3.5.1](https://github.com/jquery/jquery)
- [Specify 7 icon](https://sp7demofish.specifycloud.org/static/img/fav_icon.png)


