<?php

require_once realpath(dirname(__FILE__) . '/../') . '/geonames.php';

class GeoNamesObjectTests extends PHPUnit_Framework_TestCase
{
    public function testSetParent()
    {
        $obj1 = GeoNamesService::get(1816670);
        $obj2 = GeoNamesService::get(1835848);
        $obj1->setParent($obj2);
        $this->assertTrue($obj1->getParent() === $obj2);
    }

    public function testGetParent()
    {
        $obj = GeoNamesService::get(1816670);
        $parent = $obj->getParent();
        $this->assertTrue($parent instanceof GeoNamesObject);
        $this->assertTrue($parent->geonameId > 0);
    }

    public function testSetChildren()
    {
        $obj1 = GeoNamesService::get(1816670);
        $obj2 = GeoNamesService::get(1835848);
        $obj1->setChildren(array($obj2));
        $children = $obj1->getChildren();
        $this->assertTrue(is_array($children));
        foreach ($children as $child)
        {
            $this->assertTrue($child === $obj2);
        }
    }

    public function testGetChildren()
    {
        $obj = GeoNamesService::get(6295630);
        $children = $obj->getChildren();
        $this->assertTrue(is_array($children));
        foreach ($children as $child)
        {
            $this->assertTrue($child instanceof GeoNamesObject);
            $this->assertTrue($child->geonameId > 0);
            $this->assertTrue($child->getParent() === $obj);
        }
    }
}
