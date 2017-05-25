<?php
/**
 * GeoCode
 * 
 * A fast, hybrid offline/online, single country reverse geocoder in PHP with easy one-shot static function
 * Inspired by {@link https://github.com/lucaspiller/offline-geocoder}
 *
 * @package    GeoCode
 * @license    MIT License
 * @author     Chris Watson
 */

/*
 * Copyright 2017 Chris Watson
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * GeoCode
 *
 * Static Class for grabbing some reverse geocode information from sqlite
 *
 * @package    GeoCode
 * @author     Chris Watson
 */
Class GeoCode {
    
    /**
     * Finds and returns information about a pair of coordinates.
     * Tries offline using DB first, online using Google second
     *
     * @param mixed $lat latitude in format like -41.123456
     * @param mixed $lng longitude in format like -41.123456
     * @param PDO $db A valid PDO database object
     * @return array Placename, District and Region
     */
    public static function get($lat, $lng, $db) {

        $r = self::getOffline($lat, $lng, $db);
        
        if (empty($r[0])) { // search failed, go to online mode
            //return GeoCode::getOSM($lat, $lng);
            return self::getGoogle($lat, $lng);
        } else { // search succeeded, just return data
            return array('name' => $r[0]['name'], 'admin2_name' => $r[0]['admin2_name'], 'admin1_name' => $r[0]['admin1_name']);
        }
        
    }
    
     /**
     * Finds and returns information about a pair of coordinates from sqlite
     *
     * @param mixed $lat latitude in format like -41.123456
     * @param mixed $lng longitude in format like -41.123456
     * @param PDO $db A valid PDO database object
     * @return array Placename, District and Region
     */
    public static function getOffline($lat, $lng, $db) {
        $scale = pow(cos($lat * pi() / 180), 2);
        
        
        $sql = 'SELECT name, admin2_name, admin1_name FROM everything WHERE id IN (
            SELECT feature_id
            FROM coordinates
            WHERE latitude BETWEEN :lat - 1.5 AND :lat + 1.5
            AND longitude BETWEEN :lng - 1.5 AND :lng + 1.5
            ORDER BY (
                (:lat - latitude) * (:lat - latitude) +
                (:lng - longitude) * (:lng - longitude) * :scale
                ) ASC
                LIMIT 1
                );';
        
        $sth = $db->prepare($sql);
        $sth->execute(array(':lat' => $lat, ':lng' => $lng, ':scale' => $scale));
        // $r will contain information about the place, if in our DB
        $r = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        return $r;
        
    }
    
    /**
     * Finds and returns information about a pair of coordinates using Google reverse Geocoder
     *
     * @param mixed $lat latitude in format like -41.123456
     * @param mixed $lng longitude in format like -41.123456
     * @return array Placename, District and Region
     */
    public static function getGoogle($lat, $lng) {
        
        $d = new DOMDocument();
        $d->load('https://maps.googleapis.com/maps/api/geocode/xml?latlng='.$lat.','.$lng, LIBXML_NOBLANKS);
        $xp = new DOMXPATH($d);
        
        $admin1= $xp->query('/GeocodeResponse/result/type[text()="administrative_area_level_1"]/../address_component/long_name')[0];
        if ($admin1) { // it's something like a town
            $admin1 = $admin1->nodeValue;
            $admin2= $xp->query('/GeocodeResponse/result/type[text()="administrative_area_level_2"]/../address_component/long_name')[0]->nodeValue;
            $place = $xp->query('/GeocodeResponse/result/type[text()="political"]/../address_component/long_name')[0]->nodeValue;
        } else { // it's something else altogether, like a track or hill or path. bah, look at DOM again
            $place = $xp->query('/GeocodeResponse/result/address_component/long_name')[0]->nodeValue;
            $admin1= $xp->query('/GeocodeResponse/result/address_component/type[text()="administrative_area_level_1"]/../long_name')[0]->nodeValue;
            $admin2= $xp->query('/GeocodeResponse/result/address_component/type[text()="locality"]/../long_name')[0]->nodeValue;
        }
        return array('name' => $place, 'admin2_name' => $admin2, 'admin1_name' => $admin1);
    }
    
    /**
     * Finds and returns information about a pair of coordinates using OSM reverse Geocoder.
     * Not currently complete and working code! You need to also grab the attribution information before going live!
     * However, the method is left in the class (albeit unworking) to illustrate that you can use OSM, Bing or whatever service
     * you like as a fallback.
     *
     * @param mixed $lat latitude in format like -41.123456
     * @param mixed $lng longitude in format like -41.123456
     * @return array Placename, District and Region
     */
    public static function getOSM($lat, $lng) {
        // DOMDocument::load(): failed to open stream: HTTP request failed! HTTP/1.1 429 Too Many Requests
        // come back with api key some day
        $d = new DOMDocument();
        $d->load('https://nominatim.openstreetmap.org/reverse?format=xml&lat='.$lat.'&lon='.$lng, LIBXML_NOBLANKS);
        $xp = new DOMXPATH($d);
        //$place = $xp->query('/reversegeocode/addressparts/type[text()="political"]/../address_component/long_name')[0]->nodeValue;
        $place = 'wut'; // depending on node type, this information varies. incomplete implementation here
        $admin1= $xp->query('/reversegeocode/addressparts/state')[0]->nodeValue;
        $admin2= $xp->query('/reversegeocode/addressparts/county')[0]->nodeValue;
        
        return array('name' => $place, 'admin2_name' => $admin2, 'admin1_name' => $admin1);
    }
    
    
    
}


