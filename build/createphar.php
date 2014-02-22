#!/usr/bin/env php
<?php

class Compiler
{
    private $directories;
    private $pharName;
    private $entryPoint;
    
    private $whitelist = array(
        '.*\.php'
    );
    
    private $blacklist = array(
        '.*/test(s)?/.*'
    );


    public function __construct($entryPoint, array $directories = array())
    {
        $this->entryPoint = $entryPoint;
        
        $this->directories = $directories;
    }
    
    /**
     * Compiles composer into a single phar file
     *
     * @throws \RuntimeException
     * @param  string            $pharFile The full path to the file to create
     */
    public function compile($pharName)
    {        
        echo "Creating phar archive" . PHP_EOL;
        
        $this->pharName = $pharName;

        $pharFile = __DIR__ . DIRECTORY_SEPARATOR . $pharName;
        
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new \Phar($pharFile, 0, $pharName);
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        foreach($this->directories as $folder){
            $finder = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder,FilesystemIterator::SKIP_DOTS));

            foreach ($finder as $file) {
                if($this->isWhitelisted($file) && !$this->isBlacklisted($file))
                {
                    $this->addFile($phar, $file);
                }
            }
        }
        
        $this->addFile($phar, $this->entryPoint);

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        unset($phar);
    }
    
    private function isWhitelisted($file)
    {
        foreach($this->whitelist as $expr)
        {
            if(preg_match('@'.$expr.'@i', $file))
            {
                return true;
            }
        }
    }
    
    private function isBlacklisted($file)
    {
        foreach($this->blacklist as $expr)
        {
            if(preg_match('@'.$expr.'@i', $file))
            {
                return true;
            }
        }
    }
    
    private function getPath($path)
    {
        return ltrim(strtr(str_replace(dirname(__DIR__), '', realpath($path)), '\\', '/'), '/');
    }

    private function addFile($phar, $file, $strip = true)
    {
        $path = $this->getPath($file);

        $content = file_get_contents($file);
        
        if ($strip) {
            $content = $this->stripWhitespace($content);
        } 
        
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);

        echo $path . PHP_EOL;
        
        $phar->addFromString($path, $content);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param  string $source A PHP string
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    private function getStub()
    {
        
        $entryPath = $this->getPath($this->entryPoint);
        
        return 
"#!/usr/bin/env php
<?php

Phar::mapPhar('{$this->pharName}');

require 'phar://{$this->pharName}/{$entryPath}';

__HALT_COMPILER();";
    }
}

$root = __DIR__ . '/../';

$compile = new Compiler($root . 'bin/sync', array($root . 'bin', $root . 'src', $root . 'vendor'));

$compile->compile('db-sync.phar');

chmod(__DIR__ . '/db-sync.phar', 0777);