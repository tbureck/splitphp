# Welcome to SplitPHP

**SplitPHP** is a little program that will split subtrees from your Git repository and push them to a separate repository. The actual splitting is done using the [splitsh-lite tool by Fabien Potencier](https://github.com/splitsh/lite).

## Getting started

**SplitPHP** is likely to be used on a continuous integration environment like Travis, Jenkins or TeamCity. There are 3 main requirements for the tool to work:

* PHP 7 (recommended) or 5.6
* Git
* [splitsh-lite](https://github.com/splitsh/lite)

Make sure that the `splitsh-lite` command is available by putting its path in the PATH environment variable, so that **SplitPHP** can find it. The same goes for Git, obviously.

## Installation

Installation is easiest done by using Composer. Add `tbureck/splitphp` to your `require-dev` configuration. The `splitphp` program will then be available in your configured `bin` directory.

## Usage

In order to use **SplitPHP**, you need to create a configuration file, which defaults to `splitsh.json`. It defines the subtrees that should be extracted to the other repositories.

### Configuration

Example:

```json
{
    "common-library": {
        "prefixes": [
            {"key": "src/Library/Common", "value": ""}
        ],
        "target": "git@bitbucket.org:my-organization/my-repository.git",
        "branches": ["master", "dev-1.x"]
    }
}
```

The key of an object is a simple name for easy identification. The object defines 3 properties:

**prefixes** is a list of key-value pairs that map the monolith path to the standalone repository path (empty path means root directory)

**target** specifies the target standalone repository. Make sure that your CI server has write access to this repository.

**branches** is a list of branches that the split should be done for.

You can specify as many subtrees as you like, they will be processed in that order.

### Running the split

In order to run the split, you simply need to call the binary and pass the current branch:

```
bin/splitphp master
```

In TeamCity, you can dynamically pass the current branch by using a specific variable. Check your CI server's documentation for how to do this, if you're using a different system:

```
bin/splitphp %vcsroot.branch%
```

**SplitPHP** will be checking for the configuration file `splitsh.json` in the current working directory by default. You can specify a different path by using the `-c` option:

```
bin/splitphp -c my/path/to/splitconfiguration.json
```