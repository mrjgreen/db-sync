DbSync
=======

[![Build Status](https://travis-ci.org/mrjgreen/db-sync.svg?branch=v3)](https://travis-ci.org/mrjgreen/db-sync)
[![Coverage Status](https://coveralls.io/repos/mrjgreen/db-sync/badge.svg?branch=v3&service=github)](https://coveralls.io/github/mrjgreen/db-sync?branch=v3)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/34585f74-7436-47c0-8b40-41265ef5a1ba/mini.png)](https://insight.sensiolabs.com/projects/34585f74-7436-47c0-8b40-41265ef5a1ba)

### WARNING - This package modifies database tables. Use with extreme caution and back up databases before running.

#### Always perform a dry run first before specifying the `--execute (-e)` option.

What is it?
-----------

DbSync is a tool for efficiently comparing and synchronising two or more remote MySQL database tables. 

In order to do this without comparing every byte of data, the tool preforms a checksum (MD5, SHA1, CRC32) over a range of rows on both the source and destination tables, and compares only the hash. If a block is found to have an inconsistency in a block, the tool performs a recursive checksum on each half of the block (down to a minumum block transfer size) until it finds the inconsistency.


Notes About Deletion
--------------------
DbSync will only delete rows from the destination that no longer exist on the source when the `--delete` option is specified. Use this option with extreme caution. Always perform a dry run first.

If you use DbSync to synchronise a table which has row deletions on the source without using the `--delete` option, DbSync will find inconsistencies in any block with a deleted row on every run but will not be able to remove the rows from the target.


Installation
------------

Via composer - run the following command in your project directory:

    composer require mrjgreen/db-sync

Or use the packaged archive directly

    wget https://github.com/mrjgreen/db-sync/raw/v3/db-sync.phar -O db-sync.phar
    chmod a+x db-sync.phar
    
Optionally make the command available globally

    sudo mv db-sync.phar /usr/bin/db-sync

~~~
Usage:
  db-sync [options] [--] <source> <target> <table>

Sync a mysql database table from one host to another using an efficient checksum algorithm to find differences

Arguments:
  source                                     The source host ip to use.
  target                                     The target host ip to use.
  table                                      The fully qualified database table to sync.

Options:
  -b, --block-size=BLOCK-SIZE                The maximum block to use for when comparing [default: 1024]
      --charset=CHARSET                      The charset to use for database connections [default: "utf8"]
  -c, --columns=COLUMNS                      Columns to sync - all columns not "ignored" will be included by default (multiple values allowed)
  -C, --config=CONFIG                        A path to a config.ini file from which to read values [default: "config.ini"]
      --delete                               Remove rows from the target table that do not exist in the source
  -e, --execute                              Perform the data write on non-matching blocks
  -h, --help                                 Show this usage information
  -i, --ignore-columns=IGNORE-COLUMNS        Columns to ignore. Will not be copied or used to create the hash. (multiple values allowed)
  -x, --ignore-comparison=IGNORE-COMPARISON  Columns to ignore from the hash. Columns will still be copied. (multiple values allowed)
  -p, --password=PASSWORD                    The password for the specified user. Will be solicited on the tty if not given.
  -u, --user=USER                            The name of the user to connect with. [default: currentuser]
  -s, --transfer-size=TRANSFER-SIZE          The maximum copy size to use for when comparing [default: 8]
      --target.user=TARGET.USER              The name of the user to connect to the target host with if different to the source.
      --target.table=TARGET.TABLE            The name of the table on the target host if different to the source.
      --target.password=TARGET.PASSWORD      The password for the target host if the target user is specified. Will be solicited on the tty if not given.
      --where=WHERE                          A where clause to apply to the tables
~~~


##### Example 1

Sync the table `web.customers` from one host to another:

~~~~
db-sync --user root --password mypass 127.0.0.1 111.222.3.44 web.customers
~~~~

##### Example 2

Sync the table `web.customers` from one host to another, deleting rows from the target that no longer exist on the source:

~~~~
db-sync --user root --password mypass --delete 127.0.0.1 111.222.3.44 web.customers
~~~~

##### Example 3

Sync the table `web.customers` from one host to another using different credentials:

~~~~
db-sync --user root --password mypass --target.user admin --target.password password 127.0.0.1 111.222.3.44 web.customers:
~~~~

##### Example 4

Sync only the `email` and `name` fields from the table `web.customers`:

 > NB. The primary key will automatically be included in the column set

~~~~
db-sync --user root --password mypass 127.0.0.1 111.222.3.44 web.customers -c email -c name
~~~~

##### Example 5

Sync every column except for the `updated_at` fields from the table `web.customers`:

~~~~
db-sync --user root --password mypass 127.0.0.1 111.222.3.44 web.customers -i updated_at
~~~~

##### Example 6

Sync every column from the table `web.customers` but only use the `updated_at` fields when calculating the hash:

 > Inconsistencies in other fields will not be detected. In the event of a hash inconsistency in fields which are included, the excluded fields will still be copied to the target host.

~~~~
db-sync --user root --password mypass 127.0.0.1 111.222.3.44 web.customers -C updated_at
~~~~

##### Example 7

Sync every column from the table `web.customers` and use all fields except for the `notes` or `info` fields when calculating the hash:

 > Inconsistencies in excluded fields will not be detected. In the event of a hash inconsistency in fields which are included, the excluded fields will still be copied to the target host.
 
 > This is especially useful for tables with long text fields that don't change after initial insert, or which are associated
 with an `on update CURRENT_TIMESTAMP` field. For large tables this can offer a big performance boost.

~~~~
db-sync --user root --password mypass 127.0.0.1 111.222.3.44 web.customers -I notes -I info
~~~~

##### Example 8

Sync the table `web.customers` to a table under a different name in a different database `web_backup.customers_2`:

~~~~
db-sync --user root --password mypass --target.table web_backup.customers_2 127.0.0.1 111.222.3.44 web.customers
~~~~

Config File
-----------

To avoid having to specify options repeatedly, and to avoid exposing your password on the tty you can specify a config file.
By default DbSync will look for a file named `dbsync.ini` in the current working directory.

Example:

~~~ini
user=root
password=mypass
target.user=admin
target.pass=myadminpass
~~~

Roadmap
-------

 * [x] 100% test coverage via full stack integration tests
 * [x] Allow option to delete data from target where not present on the source
 * [x] Use symfony console command for sync
 * [ ] Allow option to skip duplicate key errors
 * [ ] Speed up initial sync of empty table - Maybe offer combination with other tool for full fast outfile based replacement

Requirements
------------

PHP 5.4 or above
PDO MySQL Extension

License
-------

DbSync is licensed under the MIT License - see the LICENSE file for details

Acknowledgments
---------------

- Inspiration for this project came from the Percona Tools `pt-table-sync`.
