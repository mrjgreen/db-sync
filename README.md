DbSync
=======

[![Build Status](https://travis-ci.org/mrjgreen/db-sync.svg?branch=master)](https://travis-ci.org/mrjgreen/db-sync)
[![Coverage Status](https://img.shields.io/coveralls/mrjgreen/db-sync.svg)](https://coveralls.io/r/mrjgreen/db-sync)

# DO NOT USE THIS BRANCH

It is not unstable and will break your table - you MUST not use this branch.

### WARNING - This package modifies database tables. Use with extreme caution and back up databases before running.

#### Always perform a dry run first before specifying the --execute option.

### What is it?
DbSync is a tool for efficiently comparing and synchronising two or more remote MySQL database tables. 

In order to do this without comparing every byte of data, the tool preforms a checksum (MD5, SHA1, CRC32) over a range of rows on both the source and destination tables, and compares only the hash. If a block is found to have an inconsistency, the tool performs a binary search through the data performing the checksum at each level until it finds the inconsistency.

### Notes About Deletion
DbSync will NOT delete rows from the destination that no longer exist on the source. This will lead to DbSync always trying to copy blocks which contain deleted rows. I intend to release a version which rectifies this with a --delete option.

### Installation

Via composer - add the package to the require section in your composer.json file:

    "require" : {    
        "mrjgreen/db-sync"   : "3.*"
    }

Or use the packaged archive directly

    wget https://github.com/mrjgreen/db-sync/raw/master/build/db-sync.phar -O db-sync.phar
    chmod a+x db-sync.phar
    
Optionally make the command available globally

    sudo mv db-sync.phar /usr/bin/db-sync

~~~
Usage:
  db-sync [options] [--] <source> <target> <table>

Arguments:
  source                                 The source host ip to use.
  target                                 The target host ip to use.
  table                                  The fully qualified database table to sync.

Options:
  -b, --block-size=BLOCK-SIZE            The maximum block to use for when comparing [default: 1024]
      --charset=CHARSET                  The charset to use for database connections [default: "utf8"]
  -c, --columns=COLUMNS                  Columns to sync - all columns not "ignored" will be included by default (multiple values allowed)
  -C, --config=CONFIG                    A path to a config.ini file from which to read values [default: "config.ini"]
  -e, --execute                          Perform the data write on non-matching blocks
  -i, --ignore-columns=IGNORE-COLUMNS    Columns to ignore (multiple values allowed)
  -p, --password=PASSWORD                The password for the specified user. Will be solicited on the tty if not given.
  -u, --user=USER                        The name of the user to connect with. [default: "joegreen"]
  -s, --transfer-size=TRANSFER-SIZE      The maximum copy size to use for when comparing [default: 8]
      --target.user=TARGET.USER          The name of the user to connect to the target host with if different to the source.
      --target.table=TARGET.TABLE        The name of the table on the target host if different to the source.
      --target.password=TARGET.PASSWORD  The password for the target host if the target user is specified. Will be solicited on the tty if not given.
  -h, --help                             Display this help message
  -q, --quiet                            Do not output any message
  -V, --version                          Display this application version
      --ansi                             Force ANSI output
      --no-ansi                          Disable ANSI output
  -n, --no-interaction                   Do not ask any interactive question
  -v|vv|vvv, --verbose                   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
~~~


##### Example 1

Sync the table `web.customers` from one host to another:

~~~~
db-sync --user root --password mypass 127.0.0.1 111.222.3.44 web.customers
~~~~

##### Example 2

Sync the table `web.customers` from one host to another using different credentials:

~~~~
db-sync --user root --password mypass --target.user admin --target.password password 127.0.0.1 111.222.3.44 web.customers:
~~~~

##### Example 3

Sync only the `email` and `name` fields from the table `web.customers`:

 > NB. The primary key will automatically be included in the column set

~~~~
db-sync --user root --password mypass 127.0.0.1 111.222.3.44 web.customers -c email -c name
~~~~

##### Example 4

Sync every column except for the `updated_at` fields from the table `web.customers`:

~~~~
db-sync --user root --password mypass 127.0.0.1 111.222.3.44 web.customers -c email -c name
~~~~



###Roadmap

 * [ ] 100% test coverage via full stack integration tests
 * [ ] Allow option to skip duplicate key errors
 * [ ] Allow option to delete data from target where not present on the source
 * [x] Use symfony console command for sync
 * [ ] Speed up initial sync of empty table - Maybe offer combination with other tool for full fast outfile based replacement
