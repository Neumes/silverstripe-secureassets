# Secure Assets Module with Archive Functionality

## Introduction

A modified [module](https://github.com/silverstripe-labs/silverstripe-secureassets) for adding access restrictions to folders
that mirrors the access restrictions of sitetree pages.

This also adds "Archive" functionality which allows for files to remain
on the filesystem and inside the CMS. Trying to access an archived file
will 404 instead of saying that permissions are incorrect.

This is a fork of the community module (Also called Secure Files)
located at https://github.com/hamishcampbell/silverstripe-securefiles.

This should work with IIS 7+, but it has not been extensively tested.

See the [usage documentation](docs/en/index.md) for more information.

## Maintainer Contact

 * Tom Brewer-Vinga `<tom (at) silverstripe (dot) com>`

## Requirements

 * SilverStripe 3.1+

## Installation Instructions

 1. Extract the module to your website directory, or install by manually editing composer:
 	```{
 	...
 	"repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/Neumes/silverstripe-secureassets.git"
		}
	],
	"require": {
		"silverstripe/secureassets": "dev-archivefiles#15444b95c6929e0fdd29d92906e88d1285f7a4e8"
	}
	...
}```
 2. Run /dev/build?flush=1

## Credit

This adds on to the work that the following developers have written.
This allows for folders to have file archive functionality which will
404 instead of doing permission checks. This means that you can "archive"
files and they can no longer be publicly accessed, but they'll still
be retained on the webserver and inside the CMS.

 * Hamish Campbell - [Secure Files](https://github.com/hamishcampbell/silverstripe-securefiles)
 * Hamish Friedlander and Sean Harvey [Secure Assets](https://github.com/silverstripe-labs/silverstripe-secureassets)
