<?php

namespace Silex\Application;

trait YamlTrait
{
    /**
     * @see Symfony\Component\Yaml\Yaml::parse
     */
    public function yamlParse($input, $exceptionOnInvalidType = false, $objectSupport = false)
    {
        return $this['yaml']::parse($input, $exceptionOnInvalidType = false, $objectSupport = false);
    }

    /**
     * @see Symfony\Component\Yaml\Yaml::dump
     */
    public function yamlDump($array, $inline = 2, $indent = 4, $exceptionOnInvalidType = false, $objectSupport = false)
    {
        return $this['yaml']::dump($array, $inline = 2, $indent = 4, $exceptionOnInvalidType = false, $objectSupport = false);
    }
}
