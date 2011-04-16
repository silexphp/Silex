<?php

namespace Silex;

class Versioning
{
    protected $file;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_file = __DIR__.'/../../version.xml';
    }

    /**
     * Grabs the last checkout hash and timestamp via git.
     */
    protected function checkVersion()
    {
        $baseDir = __DIR__.'/../..';
        if (!is_dir($baseDir.'/.git')) {
            return array('unknown', 'unknown', 'unknown');
        }
        $cmd = sprintf('cd %s; git log --pretty="%%H %%ci" -n 1', $baseDir);
        $output = array();
        exec($cmd, $output);
        $line = $output[0];
        $tokens = explode(' ', $line);
        $hash = array_shift($tokens);
        $time = join(' ', $tokens);

        return array($hash, $time, $line);
    }

    /**
     * Writes the version to an xml file.
     */
    public function putVersion()
    {
        $vers = $this->checkVersion();
        $version = $vers[0];
        $created = $vers[1];

        $template = '<?xml version="1.0" encoding="UTF-8"?>

<silex>
    <version>%s</version>
    <created>%s</created>
</silex>';
        $contents = sprintf($template, $version, $created);
        file_put_contents($this->_file, $contents);
    }

    /**
     * Returns the last commit hash.
     */
    public function getHash()
    {
        $doc = new \DOMDocument();
        $doc->load($this->_file);
        $xp = new \DOMXPath($doc);
        $hash = $xp->query('/silex/version')->item(0)->nodeValue;

        return $hash;
    }

    /**
     * Returns the time of the last commit.
     */
    public function getCreated()
    {
        $doc = new \DOMDocument();
        $doc->load($this->_file);
        $xp = new \DOMXPath($doc);
        $created = $xp->query('/silex/created')->item(0)->nodeValue;

        return $created;
    }
}
