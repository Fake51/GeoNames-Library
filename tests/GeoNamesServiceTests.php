<?php

require_once realpath(dirname(__FILE__) . '/../') . '/geonames.php';

class GeoNamesServiceTests extends PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $obj = GeoNamesService::get('323786');
        $this->assertTrue($obj instanceof GeoNamesObject);
        $this->assertTrue($obj->geonameId == 323786);
    }

    public function testSearch()
    {
        $array = GeoNamesService::search('New York');
        $this->assertTrue(is_array($array));
        $this->assertTrue(!empty($array));
        foreach ($array as $obj)
        {
            $this->assertTrue($obj instanceof GeoNamesObject);
            $this->assertTrue($obj->geonameId > 0);
        }
    }

    public function testHierarchy()
    {
        $array = GeoNamesService::hierarchy('323786');
        $this->assertTrue(is_array($array));
        $this->assertTrue(!empty($array));
        foreach (array_reverse($array) as $obj)
        {
            $this->assertTrue($obj instanceof GeoNamesObject);
            $this->assertTrue($obj->geonameId > 0);
            $this->assertTrue($obj->getParent() instanceof GeoNamesObject || $obj->getParent() === false);
        }
    }

    public function testChildren()
    {
        $array = GeoNamesService::children('6295630');
        $this->assertTrue(is_array($array));
        $this->assertTrue(!empty($array));
        foreach (array_reverse($array) as $obj)
        {
            $this->assertTrue($obj instanceof GeoNamesObject);
            $this->assertTrue($obj->geonameId > 0);
        }
    }
}
