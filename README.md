Beta Tester
===========

WordPoints module for beta testing the WordPoints plugin.

## Installation ##

*It is highly recommended that you don't use this module on a production site.*

To install the module, download [the latest
release](https://github.com/WordPoints/beta-tester/releases/latest). You can install
it by navigating to the _WordPoints » Modules_ administration screen, clicking the
_Add New_ link, and uploading the zip file. Alternatively, you can just unpack the
archive in the `/wordpoints-modules/` directory. Then you can activate the module.

## Usage ##

There currently no settings for the module; it does most of its work behind the
scenes. It will check if there are any recent commits to the [WordPoints GitHub
repo](https://github.com/WordPoints/wordpoints) where development of the plugin takes
place. By default it checks for changes no more than once in six hours.

When new changes for WordPoints are detected, WordPress will give you a notice that
the plugin needs an upgrade, just as it normally would. You update to the latest
changes in the usual manner for updating a plugin. To see what changes have been
made since you last updated, you can click on the "View changes" link. This will
display a list of the most recent commits since your last update, with links to
GitHub where you can view the code and more info.

Again, let me recommend that you don't use this module on a live site. It is possible
that during the development process WordPoints could break temporarily, and could
even potentially break your entire site. So use this module at your own risk.
