# Contributing

We welcome bug fixes and patches from 3rd party contributors. Please see the [Contributor Agreement](https://www.arangodb.com/documents/cla.pdf) for details.
Please follow these guidelines if you want to contribute to ArangoDB-PHP:


## Reporting Bugs

When reporting bugs, please use our issue tracker on GitHub.
Please make sure to include the version number of ArangoDB and ArangoDB-PHP in your bug report, along with the platform you are using (e.g. `Linux OpenSuSE x86_64`, `PHP 5.4.15`).
Please also include any special configuration.
This will help us reproducing and finding bugs.

Please also take the time to check there are no similar/identical issues open yet.



## Contributing features, documentation, tests

* Create a new branch in your fork, based on the devel branch
* Develop and test your modifications there
* Commit as you like, but preferably in logical chunks. Use meaningful commit messages and make sure you do not commit unnecessary files. It is normally a good idea to reference the issue number from the commit message so the issues will get updated automatically with comments
* Make sure the ArangoDB version is the correct one to use with your client version. The client version follows the ArangoDB version. [More info on this, here.](https://github.com/arangodb/ArangoDB-PHP/wiki/Important-versioning-information-on-ArangoDB-PHP)
* Make sure the unmodified clone works locally before making any code changes. You can do so by running the included test suite (phpunit --testsuite ArangoDB-PHP)
* If the modifications change any documented behavior or add new features, document the changes. The documentation can be found in the docs directory. To recreate the documentation locally, follow the steps in the next paragraph "Generating Documentation". This will re-create all documentation files in the docs directory in your repository. You can inspect the documentation in this folder using a browser. We recently agreed that future documentation should be written in American English (AE).
* When done, run the complete test suite and make sure all tests pass
* When finished, push the changes to your GitHub repository and send a pull request from your fork to the ArangoDB-PHP repository. Please make sure to select the appropriate branches there. This will most likely be **devel**.
* You must use the Apache License for your changes and have signed our [CLA](https://www.arangodb.com/documents/cla.pdf). We cannot accept pull requests from contributors that did not sign the CLA.
* Please let us know if you plan to work an a ticket. This way we can make sure we avoid redundant work

* For feature requests: please clearly describe the proposed feature, additional configuration options, and side effects


## Generating documentation

Documentation is generated with the apigen generator with the following parameters (beside the source and destination definition):

```
--template-theme bootstrap --title "ArangoDB-PHP API Documentation" --deprecated
```


Example:
```
php -f apigen.phar generate -s ./lib/triagens/ArangoDb -d ./docs --template-theme bootstrap --title "ArangoDB-PHP API Documentation" --deprecated
```


## Additional Resources

* [ArangoDB website](https://www.arangodb.com/)
* [ArangoDB on Twitter](https://twitter.com/arangodb)
* [ArangoDB-PHP on Twitter](https://twitter.com/arangodbphp)
* [General GitHub documentation](https://help.github.com/)
* [GitHub pull request documentation](https://help.github.com/send-pull-requests)
