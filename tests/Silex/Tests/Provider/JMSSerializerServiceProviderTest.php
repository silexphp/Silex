<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Provider;

use DateTime;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Silex\Application;
use Silex\Provider\JMSSerializerServiceProvider;

/**
 * JMSSerializerServiceProvider test cases.
 *
 * @author Marijn Huizendveld <marijn@pink-tie.com>
 */
class JMSSerializerServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    private $cache;

    public function setup()
    {
        $this->cache = sys_get_temp_dir()."/JMSSerializerServiceProviderTest";

        if (file_exists($this->cache)) {
            $this->dropMetaDataCache($this->cache);
        }

        mkdir($this->cache);
    }

    public function teardown()
    {
        if (file_exists($this->cache)) {
            $this->dropMetaDataCache($this->cache);
        }
    }

    public function testRegister()
    {
        $app = new Application();

        $app->register(new JMSSerializerServiceProvider(), array(
            'serializer.cache.directory' => $this->cache
        ));

        $this->assertInstanceOf("JMS\SerializerBundle\Serializer\Serializer", $app['serializer']);

        return $app;
    }

    /**
     * @depends testRegister
     */
    public function testSerialize(Application $app)
    {
        $fabien = new SerializableUser(1, "Fabien Potencier", new DateTime("2005-10-01T00:00:00+0000"));
        $fabienJson = '{"id":1,"name":"Fabien Potencier","created":"2005-10-01T00:00:00+0000"}';
        $fabienXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<result>
  <id>1</id>
  <name><![CDATA[Fabien Potencier]]></name>
  <created>2005-10-01T00:00:00+0000</created>
</result>

XML;

        $this->assertEquals($fabienJson, $app['serializer']->serialize($fabien, "json"));
        $this->assertEquals($fabienXml, $app['serializer']->serialize($fabien, "xml"));
        $this->assertEquals($fabien, $app['serializer']->deserialize($fabienJson, "Silex\Tests\Provider\SerializableUser", "json"));
        $this->assertEquals($fabien, $app['serializer']->deserialize($fabienXml, "Silex\Tests\Provider\SerializableUser", "xml"));
        $this->assertFileExists($this->cache."/Silex-Tests-Provider-SerializableUser.cache.php");
    }

    private function dropMetaDataCache($directory)
    {
        $iterator = new RecursiveDirectoryIterator($directory);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($directory);
    }
}
