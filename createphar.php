<?php

$phar = new \Phar('db-sync.phar', 0);
$phar->setSignatureAlgorithm(\Phar::SHA1);

$phar->startBuffering();

$folders = array(__DIR__ . '/src', __DIR__ . '/vendor');

foreach($folders as $folder)
{
    foreach(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS) as $file)
    {
        $phar[$file] = file_get_contents($file);
    }
}

$phar[__DIR__ . '/bin/sync'] = file_get_contents($file);
$phar->setStub($phar->createDefaultStub(__DIR__ . '/bin/sync'));

$phar->stopBuffering();