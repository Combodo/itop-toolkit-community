# Contributing

You want to contribute to this module? Many thanks to you! 🎉 👍

Here are some guidelines that will help us integrate your work!


## Contributions

### Subjects
You are welcome to create pull requests on any of those subjects:

* 🐛 bug fix
* 🌐 translation / i18n / l10n

If you want to implement a **new feature**, please [create a corresponding ticket](https://sourceforge.net/p/itop/tickets/new/) for review.   
If you ever want to begin implementation, do so in a fork, and add a link to the corresponding commits in the ticket. As maintainers of the module we favor generic solutions, i.e. the ones that address most use cases instead of just a very specific need.

For all **security related subjects**, please see our [security policy](SECURITY.md).

### 📄 License and copyright
This iTop module is distributed under the AGPL-3.0 license (see the [license.txt] file),
your code must comply with this license.

Combodo has the copyright on each and every source file in this repository: please do not modify the existing file copyrights.  
Anyhow, you are encouraged to signal your contribution by the mean of `@author` annotations.

If you want to use another license or keep the code ownership (copyright), you may [create another extension][wiki new ext].

[license.txt]: https://github.com/Combodo/iTop/blob/develop/license.txt
[wiki new ext]: https://www.itophub.io/wiki/page?id=latest%3Acustomization%3Astart#by_writing_your_own_extension


## 🔀 Branch model

All developments are done on the master branch. If needed, a support/* branch might be created from a tag for compatibility reasons.

You should always base your developments on the master branch.


## Coding

### 🌐 Translations

A [dedicated page](https://www.itophub.io/wiki/page?id=latest%3Acustomization%3Atranslation) is available in the official wiki.

### Where to start ?

1. Create a fork from our repository (see [Working with forks - GitHub Help](https://help.github.com/en/github/collaborating-with-issues-and-pull-requests/working-with-forks))
2. Create a branch in this fork, based on the develop branch
3. Code !

Do create a dedicated branch for each modification you want to propose : if you don't, it will be very hard for us to merge back your work !


### 🎨 PHP styleguide

Please follow [our guidelines](https://www.itophub.io/wiki/page?id=latest%3Acustomization%3Acoding_standards).

### ✅ Tests

Please create tests that covers as much as possible the code you're submitting.

Our tests are located in the `test/` directory, containing a PHPUnit config file : `phpunit.xml`.

### Git Commit Messages

* Describe the functional change instead of the technical modifications
* Use the present tense ("Add feature" not "Added feature")
* Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
* Limit the first line to 72 characters or less
* Please start the commit message with an applicable emoji code (following the [Gitmoji guide](https://gitmoji.carloscuesta.me/)).  
 Beware to use the code (for example `:bug:`) and not the character (🐛) as Unicode support in git clients is very poor for now...  
 Emoji examples :
    * 🌐 `:globe_with_meridians:` for translations
    * 🎨 `:art:` when improving the format/structure of the code
    * ⚡️ `:zap:` when improving performance
    * 🐛 `:bug:` when fixing a bug
    * 🔥 `:fire:` when removing code or files
    * 💚 `:green_heart:` when fixing the CI build
    * ✅ `:white_check_mark:` when adding tests
    * 🔒 `:lock:` when dealing with security
    * ⬆️ `:arrow_up:` when upgrading dependencies
    * ⬇️ `:arrow_down:` when downgrading dependencies
    * ♻️ `:recycle:` code refactoring
    * 💄 `:lipstick:` Updating the UI and style files.  
  

## 👥 Pull request

When your code is working, please:

* Squash as much as possible your commits,
* Rebase your branch on our repo last commit,
* Create a pull request. _Detailed procedure to work on fork and create PR is available [in GitHub help pages](https://help.github.com/articles/creating-a-pull-request-from-a-fork/)_.
* Pull request description: mind to add all the information useful to understand why you're suggesting this modification and anything necessary to dive into your work. Especially:
  - Bugfixes: exact steps to reproduce the bug (given/when/then), description of the bug cause and what solution is implemented 
  - Enhancements: use cases, implementation details if needed
* Mind to check the "[Allow edits from maintainers](https://docs.github.com/en/github-ae@latest/pull-requests/collaborating-with-pull-requests/working-with-forks/allowing-changes-to-a-pull-request-branch-created-from-a-fork)" option !
