# Contributing

We welcome bug fixes and patches from 3rd party contributors.
Please follow these guidelines if you want to contribute to ArangoDB-PHP:

## Getting started

* Please make sure you have a GitHub account
* Please look into the ArangoDB-PHP issue tracker on GitHub for similar/identical issues
* For bugs: if the bug you found is not yet described in an existing issue, please file a new one. The new issue should include a clear description of the bug and how to reproduce it (including your environment)
* For feature requests: please clearly describe the proposed feature, additional configuration options, and side effects
* Please let us know if you plan to work an a ticket. This way we can make sure we avoid redundant work

* Create a fork of our repository. You can use GitHub to do this
* Clone the fork to your development box and pull the latest changes from the ArangoDB-PHP repository. Please make sure to use the appropriate branch:
  * the "devel" branch is normally used for new features
  * bug fixes should be done in the "devel" first, before being applied to master or other branches
* Make sure the ArangoDB version is the correct one to use with your client version. The client version follows the ArangoDB version. [More info on this, here.](https://github.com/triAGENS/ArangoDB-PHP/wiki/Important-versioning-information-on-ArangoDB-PHP)
* Make sure the unmodified clone works locally before making any code changes. You can do so by running the included test suite (phpunit --testsuite ArangoDB-PHP)
* If you intend to do documentation changes, you also must install PHPDocumentor in the most recent version (Currently version 2).

## Making Changes

* Create a new branch in your fork
* Develop and test your modifications there
* Commit as you like, but preferably in logical chunks. Use meaningful commit messages and make sure you do not commit unnecessary files. It is normally a good idea to reference the issue number from the commit message so the issues will get updated automatically with comments
* If the modifications change any documented behavior or add new features, document the changes. The documentation can be found in the 'docs' directory. To recreate the documentation locally, run 'phpdoc --force --title "ArangoDB PHP client API" -d ArangoDb/lib -t ArangoDb/docs'. This will re-create all documentation files in the docs directory in your repository. You can inspect the documentation in this folder using a text editor or a browser
* When done, run the complete test suite and make sure all tests pass
* When finished, push the changes to your GitHub repository and send a pull request from your fork to the ArangoDB-PHP repository. Please make sure to select the appropriate branches there
* You must use the Apache License for your changes

## Reporting Bugs

When reporting bugs, please use our issue tracker on GitHub.
Please make sure to include the version number of ArangoDB and ArangoDB-PHP in your bug report, along with the platform you are using (e.g. `Linux OpenSuSE x86_64`, `PHP 5.4.15`).
Please also include any special configuration.
This will help us reproducing and finding bugs.

## Additional Resources

* [ArangoDB website](https://www.arangodb.org/)
* [ArangoDB on Twitter](https://twitter.com/arangodb)
* [ArangoDB-PHP on Twitter](https://twitter.com/arangodbphp)
* [General GitHub documentation](https://help.github.com/)
* [GitHub pull request documentation](https://help.github.com/send-pull-requests)