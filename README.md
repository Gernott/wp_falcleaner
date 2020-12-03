# wp_falcleaner

Find duplicate files and clean up database and filesystem.

## What does it do?

wp_falcleaner is a TYPO3 extension to clean up your TYPO3 installation. It finds file duplicates and migrates them to a single file. It respects any content-fields and RTE-fields.

## Next feature ideas

* merge FAL metadata
* find an extension- and backend-module-icon
* write an info after cleanup, to run the refindexer
* create a file-wizard for the rules folder-field
* restore rules after a page reload/change
* show count of references in preview
* limit, if too many files and/or logs
* write a manual
* intercept the server timeout and cancel with a message before it gets stuck somewhere in the middle
* remove unused files

## Found a bug?
* first check out the master branch and verify that the issue is not yet solved
* have a look at the existing [issues](https://github.com/gernott/wp_falcleaner/issues/), to prevent duplicates
* if not found, report the bug in our [issue tracker](https://github.com/gernott/wp_falcleaner/issues/new/)
