<?php

$phar = new \Phar('db-sync.phar', 0);
$phar->setSignatureAlgorithm(\Phar::SHA1);

$phar->startBuffering();

$folders = array('src', 'vendor');

foreach($folders as $folder)
{
    foreach(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS) as $file)
    {
        $phar[$file] = file_get_contents($file);
    }
}

$phar['bin/sync'] = file_get_contents($file);
$phar->setStub('bin/sync');

$phar->startBuffering();