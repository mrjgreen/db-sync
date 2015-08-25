DbSync
=======

[![Build Status](https://travis-ci.org/mrjgreen/db-sync.svg?branch=master)](https://travis-ci.org/mrjgreen/db-sync)
[![Coverage Status](https://img.shields.io/coveralls/mrjgreen/db-sync.svg)](https://coveralls.io/r/mrjgreen/db-sync)

### WARNING - This package overwrites data in database tables. Use with extreme caution and back up databases before running the command against them.

#### Always perform a dry run first before specifying the --execute option.

### What is it?
DbSync is a tool for efficiently comparing and synchronising two or more remote MySQL database tables. 

In order to do this without comparing every byte of data, the tool preforms a hash (CRC, MD5 or SHA1) over a range of rows on both the source and destination tables, and compares only the hash. If a hash block (default 1000) is found to have an inconsistency, the tool starts rolling through in small chunks (default 10) doing the same hash comparison but over this reduced set. If any sub block is found to have an inconsistency, the entire sub block is copied to the destination.

 > NB. CRC is very fast, but hash collisions are very likely. DO NOT use CRC in situations where data integrity is required.
 
### Notes About Deletion
DbSync will NOT delete rows from the destination that no longer exist on the source. This will lead to DbSync always trying to copy blocks which contain deleted rows. I intend to release a version which rectifies this with a --delete option.

### Installation

Via composer - add the package to the require section in your composer.json file:

    "require" : {    
        "mrjgreen/db-sync"   : "2.*"
    }

Or use the phar archive directly

    wget https://github.com/mrjgreen/db-sync/raw/master/build/db-sync.phar -O db-sync.phar
    chmod a+x db-sync.phar
    
Call directly

    ./db-sync.phar source destination --options
    
Optionally make the command available globally

    sudo mv db-sync.phar /usr/bin/db-sync
    #Call from anywhere using:
    db-sync source destination --options

~~~
Usage: bin/sync  source destination

	 --help                 -h              Display help
	 --quiet                -q              Suppress all output
	 --verbose              -v[=""]         Set the verbosity level
	 --charset                              The charset to use with PDO connections
	 --chunk-size           -h=""           The comparion hash block size (number of rows)
	 --columns              -c=""           Specify a subset of the sync columns to use in the block compariosn hash
	 --execute              -e              Perform the data write on non-matching blocks
	 --function                             The hash function to use in the block comparison: CRC32, MD5, SHA1
	 --ignore-columns                       Sync columns to ignore in the block compariosn hash
	 --ignore-sync-columns                  Columns to ignore when syncing and comparing data
	 --ignore-tables                        Tables to ignore when syncing
	 --password             -p=""           The password for the specified user
	 --sync-columns                         The columns to compare and sync
	 --sync-method                          The method used to write rows: replace, update.
	                                        NB replace will fill missing sync columns with defaults. 
	                                        Use update if this is not desired behavior
	 					
	 --tables               -t=""           The tables to sync
	 --transfer-size        -s=""           The number of rows to transfer at once from non-matching blocks
	 --user                 -u=""           The name of the user to connect with
	 --where                -w=""           A WHERE clause to apply against the tables
~~~

Source and Destination DSN should be in the format `user:password@host:database`
 > NB. The user:password section is optional if the user/password credentials are the same on both the source and destination. In this case you can specify the credentials using the --user="" and --password="" options

Example - sync all tables on database1 to database2 on a different host:

~~~~

bin/sync root:password@127.0.0.1:database1  admin:password@111.222.3.44:database2

~~~~

Example - sync only two tables:

~~~~

bin/sync root:password@127.0.0.1:database1  admin:password@111.222.3.44:database2 --tables="users,pages"

~~~~

Example - sync all but one table:

~~~~

bin/sync root:password@127.0.0.1:database1  admin:password@111.222.3.44:database2 --ignore-tables="users"

~~~~

Example - sync one table and only use the `updated_at` to perform the hash checks (NB. The primary key will also be used in all hash checks to determine missing rows):

~~~~

bin/sync root:password@127.0.0.1:database1  admin:password@111.222.3.44:database2 --ignore-tables="users" --columns="updated_at"

~~~~

Example - sync only two columns from one table and only use the `updated_at` to perform the hash checks.

> NB If the `--sync-columns` option is specified, your `--columns` option must only contain columns which are present in the `--sync-columns` option. IE. you must sync all columns you use in the hash check. An exception will be thrown if there are columns present in --columns which are not set to sync.

~~~~

bin/sync root:password@127.0.0.1:database1  admin:password@111.222.3.44:database2 --tables="users" --sync-columns="name,email,password,updated_at" --columns="updated_at"

~~~~



###Roadmap

 * [ ] 100% test coverage via full stack integration tests
 * [ ] Allow option to skip duplicate key errors
 * [ ] Allow option to delete data from target where not present on the source
 * [ ] Use symfony console command fo sync
 * [ ] Offer combination with other tool for full fast outfile based replacement (offer as initial sync?)
