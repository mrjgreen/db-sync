db-sync
=======

### Installation

Via composer - add the package to the require section in your composer.json file:

    "require" : {    
        "joegreen0991/config"   : "dev-master"
    }

Or use the phar archive directly

    wget https://github.com/joegreen0991/db-sync/raw/master/build/db-sync.phar -O db-sync.phar
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
	 --foreign-key-checks                   Enable foreign key checks when writing data (SET FOREIGN_KEY_CHECKS=1)
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
	 --unique-checks                        Enable unique key checks (SET UNIQUE_CHECKS=1).
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

