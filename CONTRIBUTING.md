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

### 📄 License
This iTop module is distributed under the AGPL-3.0 license (see the [license.txt] file),
your code must comply with this license.

If you want to use another license, you may [create another extension][wiki new ext].

[license.txt]: license.txt
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

* stash as much as possible your commits,
* rebase your branch on our repo last commit,
* create a pull request.

Detailed procedure to work on fork and create PR is available [in GitHub help pages](https://help.github.com/articles/creating-a-pull-request-from-a-fork/).
