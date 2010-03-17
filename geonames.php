<?php
    /** 
     * Copyright (C) 2010  Peter Lind
     *
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with this program.  If not, see <http://www.gnu.org/licenses/gpl.html>.
     *
     * PHP version 5
     *
     * this file contains all the classes in the GeoNames library
     *
     * @package   GeoNames
     * @author    Peter Lind <peter.e.lind@gmail.com>
     * @copyright 2010 Peter Lind
     * @license   http://www.gnu.org/licenses/gpl.html GPL 3
     * @link      http://www.github.com/Fake51/Infosys
     */


    /**
     * GeoNames exception class
     * for thrown GeoNames specific exceptions
     *
     * @package GeoNames
     * @author  Peter Lind <peter.e.lind@gmail.com>
     */
class GeoNamesException extends Exception
{
}

    /**
     * GeoNamesObject class
     * contains data from lookups to geonames.org
     *
     * @package GeoNames
     * @author  Peter Lind <peter.e.lind@gmail.com>
     */
class GeoNamesObject
{
    private $_parent;
    private $_children;

    /**
     * pass in a geonameId to construct the hierarchy of data
     *
     * @param SimpleXMLElement $fragment
     *
     * @throws GeoNamesException
     * @access public
     * @return void
     */
    public function __construct(SimpleXMLElement $fragment)
    {
        $this->geonameId = intval($fragment->geonameId);
        $this->name = (string)$fragment->name;
        $this->lat = (float)$fragment->lat;
        $this->lng = (float)$fragment->lng;
        $this->countryCode = (string)$fragment->countryCode;
        $this->countryName = (string)$fragment->countryName;
        $this->fcl = (string)$fragment->fcl;
        $this->fcode = (string)$fragment->fcode;
        $this->fclName = (string)$fragment->fclName;
        $this->fcodeName = (string)$fragment->fcodeName;
        $this->population = intval($fragment->population);
        $this->adminCode1 = (string)$fragment->AdminCode1;
        $this->adminName1 = (string)$fragment->AdminName1;
        $this->adminCode2 = (string)$fragment->AdminCode2;
        $this->adminName2 = (string)$fragment->AdminName2;
        $timezone = $fragment->timezone;
        try
        {
            $timezone_atts = $timezone->attributes();
            $this->timezone = array(
                'name'      => (string) $timezone,
                'dstOffset' => (float) $timezone_atts->dstOffset,
                'gmtOffset' => (float) $timezone_atts->gmtOffset,
            );
        }
        catch (Exception $e)
        {
            $this->timezone = array(
                'name'      => '',
                'dstOffset' => 0,
                'gmtOffset' => 0,
            );
        }
        $this->alternate_names = array();
        try
        {
            foreach ($fragment->alternateName as $altname)
            {
                $atts = $altname->attributes();
                $this->alternate_names[(string) $atts->lang] = (string) $altname;
            }
        }
        catch (Exception $e)
        {
        }
    }

    /**
     * sets a parent GeoNamesObject object
     *
     * @param GeoNamesObject $parent
     *
     * @access public
     * @return $this
     */
    public function setParent(GeoNamesObject $parent)
    {
        $this->_parent = $parent;
        return $this;
    }

    /**
     * fetches a parent GeoNamesObject object
     *
     * @access public
     * @return GeoNamesObject|false
     */
    public function getParent()
    {
        if (!isset($this->_parent))
        {
            if ($this->name == 'Earth' && $this->lat == 0 && $this->lng == 0) return false; 
            $parent = false;
            $hierarchy = GeoNamesService::hierarchy($this->geonameId);
            foreach (array_reverse($hierarchy) as $obj)
            {
                if ($obj->geonameId == $this->geonameId)
                {
                    $parent = $obj->getParent();
                    break;
                }
            }
            $this->_parent = $parent;
        }
        return $this->_parent;
    }

    /**
     * sets children of the GeoNameObject
     *
     * @param array $children
     *
     * @access public
     * @return $this
     */
    public function setChildren(array $children)
    {
        foreach ($children as $child)
        {
            if (!($child instanceof GeoNamesObject)) throw new GeoNamesException("Objects of wrong type supplied to GeoNamesObject::setChildren");
            $child->setParent($this);
        }
        $this->_children = $children;
        return $this;
    }

    /**
     * fetches children of the GeoNamesObject
     *
     * @access public
     * @return array
     */
    public function getChildren()
    {
        if (!isset($this->_children))
        {
            $this->setChildren(GeoNamesService::children($this->geonameId));
        }
        return $this->_children;
    }
}

    /**
     * GeoNamesService class
     * does lookups to geonames.org and generates GeoNamesObject objects
     *
     * @package GeoNames
     * @author  Peter Lind <peter.e.lind@gmail.com>
     */
class GeoNamesService
{

    const URL_BASE = 'http://ws.geonames.org/';

    /**
     * retrieves data from ws.geonames.org and runs through it
     * creating GeoNames objects as needed and setting parents
     *
     * @param int $id
     *
     * @throws GeoNamesException
     * @access public
     * @return array
     */
    public static function hierarchy($id)
    {
        if (!intval($id)) throw new GeoNamesException("Bad id supplied to GeoNamesService::hierarchy");
        $xml = simplexml_load_file(self::URL_BASE . 'hierarchy?geonameId=' . intval($id) . '&style=FULL', null, true);
        $results = array();
        foreach ($xml->children() as $child)
        {
            if ($child->getName() != 'geoname') continue;
            $geoname = new GeoNamesObject($child);
            if (isset($parent)) $geoname->setParent($parent);
            $parent = $geoname;
            $results[$geoname->geonameId] = $geoname;
        }
        return $results;
    }

    /**
     * retrieves data from ws.geonames.org and runs through it
     * creating GeoNames objects as needed and setting children
     *
     * @param int $id
     *
     * @throws GeoNamesException
     * @access public
     * @return array
     */
    public static function children($id)
    {
        if (!intval($id)) throw new GeoNamesException("Bad id supplied to GeoNamesService::children");
        $xml = simplexml_load_file(self::URL_BASE . 'children?geonameId=' . intval($id) . '&style=FULL', null, true);
        $results = array();
        foreach ($xml->children() as $child)
        {
            if ($child->getName() != 'geoname') continue;
            $geoname = new GeoNamesObject($child);
            $results[$geoname->geonameId] = $geoname;
        }
        return $results;
    }

    /**
     * queries the geonames service using a search term
     * and returns all results in an array
     *
     * @param string $term
     * @param int    $rows row count to return, defaults to 100
     *
     * @throws GeoNamesException
     * @access public
     * @return array
     */
    public static function search($term, $rows = 100)
    {
        if (!is_string($term) || strlen($term) == 0) throw new GeoNamesException("Bad search term supplied to GeoNamesService::search");
        $xml = simplexml_load_file(self::URL_BASE . 'search?q=' . urlencode($term) . '&maxRows=' . (intval($rows) ? intval($rows) : 100) . '&style=FULL', null, true);
        $results = array();
        foreach ($xml->children() as $child)
        {
            if ($child->getName() != 'geoname') continue;
            $geoname = new GeoNamesObject($child);
            $results[$geoname->geonameId] = $geoname;
        }
        return $results;
    }

    /**
     * fetches all data for a single geonameId - but not it's hierarchy
     *
     * @param int $id
     *
     * @throws GeoNamesException
     * @access public
     * @return GeoNamesObject
     */
    public static function get($id)
    {
        if (!intval($id)) throw new GeoNamesException("Bad id supplied to GeoNamesService::get");
        $xml = simplexml_load_file(self::URL_BASE . 'get?geonameId=' . intval($id) . '&style=FULL', null, true);
        $geoname = new GeoNamesObject($xml);
        return $geoname;
    }
}
