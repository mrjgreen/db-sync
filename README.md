DbSync
=======

[![Build Status](https://travis-ci.org/mrjgreen/db-sync.svg?branch=v3)](https://travis-ci.org/mrjgreen/db-sync)
[![Coverage Status](https://coveralls.io/repos/mrjgreen/db-sync/badge.svg?branch=v3&service=github)](https://coveralls.io/github/mrjgreen/db-sync?branch=v3)
[![Latest Stable Version](https://poser.pugx.org/mrjgreen/db-sync/v/stable)](https://packagist.org/packages/mrjgreen/db-sync)
[![License](https://poser.pugx.org/mrjgreen/db-sync/license)](https://packagist.org/packages/mrjgreen/db-sync)
[![Total Downloads](https://poser.pugx.org/mrjgreen/db-sync/downloads)](https://packagist.org/packages/mrjgreen/db-sync)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/34585f74-7436-47c0-8b40-41265ef5a1ba/mini.png)](https://insight.sensiolabs.com/projects/34585f74-7436-47c0-8b40-41265ef5a1ba)

### WARNING - This package modifies database tables. Use with extreme caution and back up databases before running.

#### Always perform a dry run (this is the default action) first before specifying the `--execute (-e)` option.

What is it?
-----------

DbSync is a tool for efficiently comparing and synchronising two or more remote MySQL database tables.

In order to do this without comparing every byte of data, the tool preforms a checksum (MD5, SHA1, CRC32) 
over a range of rows on both the source and destination tables, and compares only the hash. If a block is found to have 
an inconsistency in a block, the tool performs a recursive checksum on each half of the block (down to a minimum 
block transfer size) until it finds the inconsistency.


Notes About Deletion
--------------------
DbSync will only delete rows from the destination that no longer exist on the source when the `--delete` option is specified. 
Use this option with extreme caution. Always perform a dry run first.

If you use DbSync to synchronise a table which has row deletions on the source without using the `--delete` option, 
DbSync will find inconsistencies in any block with a deleted row on every run but will not be able to remove the rows from the target.


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
db-sync [options] <source> <target> <table>

Sync a mysql database table from one host to another using an efficient checksum algorithm to find differences.

Arguments:
  source                                     The source host ip to use.
  target                                     The target host ip to use.
  table                                      The fully qualified database table to sync.

Options:
  -b, --block-size=BLOCK-SIZE                The maximum block to use for when comparing. [default: 1024]
      --charset=CHARSET                      The charset to use for database connections. [default: "utf8"]
  -c, --columns=COLUMNS                      Columns to sync - all columns not "ignored" will be included....
  -C, --comparison=COMPARISON                Columns from the list of synced columns to use to create the...
  -f, --config-file=CONFIG-FILE              A path to a config.ini file from which to read values. [default: "dbsync.ini"]
      --delete                               Remove rows from the target table that do not exist in the source.
  -e, --execute                              Perform the data write on non-matching blocks.
  -h, --help                                 Show this usage information.
  -H, --hash                                 Specify the hash algorithm used to generate the comparison hash. [default: "md5"]
  -i, --ignore-columns=IGNORE-COLUMNS        Columns to ignore. Will not be copied or used to create the hash....
  -I, --ignore-comparison=IGNORE-COMPARISON  Columns to ignore from the hash. Columns will still be copied....
  -p, --password[=PASSWORD]                  The password for the specified user. Will be solicited on the tty if...
  -u, --user=USER                            The name of the user to connect with. [default: "USER"]
  -s, --transfer-size=TRANSFER-SIZE          The maximum copy size to use for when comparing. [default: 8]
      --target.user=TARGET.USER              The name of the user to connect to the target host with if different...
      --target.table=TARGET.TABLE            The name of the table on the target host if different to the source.
      --target.password=TARGET.PASSWORD      The password for the target host if the target user is specified....
      --where=WHERE                          A where clause to apply to the tables.
  -v, --verbose                              Enable verbose output.
  -q, --quiet                                Disable output, overrides "verbose" option.
~~~

### Examples

*Note - All of these commands will perform a "dry-run" only. To execute the insert/update statement against the target
database, you must specify the --execute (-e) option*

##### Example 1

Sync the table `web.customers` from one host to another (non-standard port on target):

~~~~
db-sync --user root --password mypass 127.0.0.1 111.222.3.44:13306 web.customers
~~~~

##### Example 2

Sync the table `web.customers` from one host to another, deleting rows from the target that no longer exist on the source:
Use the SHA1 hash.
~~~~
db-sync --user root --password mypass --hash sha1 --delete 127.0.0.1 111.222.3.44 web.customers
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

 > Inconsistencies in other fields will not be detected. In the event of a hash inconsistency in fields which are 
 included, the excluded fields will still be copied to the target host.

~~~~
db-sync --user root --password mypass 127.0.0.1 111.222.3.44 web.customers -C updated_at
~~~~

##### Example 7

Sync every column from the table `web.customers` and use all fields except for the `notes` or `info` fields when calculating the hash:

 > Inconsistencies in excluded fields will not be detected. In the event of a hash inconsistency in fields which are included, 
 the excluded fields will still be copied to the target host.

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
target.password=myadminpass
~~~

Use library within project (non-commandline)
--------------------------------------------

You can include the library within your project and use the components directly:

~~~PHP
use \DbSync\DbSync;
use \DbSync\Transfer;
use \DbSync\Hash\ShaHash;
use \DbSync\Table;
use \DbSync\ColumnConfiguration;

$sync = new DbSync(new Transfer(new ShaHash(), $blockSize, $transferSize));

$sync->setLogger(new YourPsrLogger());

$sync->dryRun(false);

$sync->delete(true);

$sourceTable = new Table($sourceConnection, $sourceDb, $sourceTable);
$targetTable = new Table($targetConnection, $targetDb, $targetTable);

// if you only want specific columns 
$columnConfig = new ColumnConfiguration($syncColumns, $ignoreColumns);

// optionally apply a where clause
$sourceTable->setWhereClause(new WhereClause("column_name = ?", ['value']));
$targetTable->setWhereClause(new WhereClause("column_name > ?", ['value']));

$sync->sync($sourceTable, $targetTable, $columnConfig);
~~~

Roadmap
-------

 * [x] 100% test coverage via full stack integration tests
 * [x] Allow option to delete data from target where not present on the source
 * [x] Use symfony console command for sync
 * [ ] Option to re-try with back-off on lock wait timeouts
 * [ ] Option to create missing tables on target
 * [ ] Option to skip duplicate key errors
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
