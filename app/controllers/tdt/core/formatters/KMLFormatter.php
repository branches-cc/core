<?php

namespace tdt\core\formatters;

/**
 * KML Formatter
 * @copyright (C) 2011,2013 by OKFN Belgium vzw/asbl
 * @license AGPLv3
 * @author Michiel Vancoillie <michiel@okfn.be>
 * @author Jan Vansteenlandt <jan@okfn.be>
 * @author Pieter Colpaert   <pieter@irail.be>
 */
class KMLFormatter implements IFormatter{

    public static function createResponse($dataObj){

        // Create response
        $response = \Response::make(self::getBody($dataObj), 200);

        // Set headers
        $response->header('Content-Type', 'application/vnd.google-earth.kml+xml;charset=UTF-8');

        return $response;
    }

    public static function getBody($dataObj){

        // Build the body
        // KML header
        $body = '<?xml version="1.0" encoding="UTF-8" ?>';
        $body .= '<kml xmlns="http://www.opengis.net/kml/2.2">';

        // Add the document
        $body .= "<Document>";


        $body .= self::getPlacemarks($dataObj);

        // Close tags
        $body .= "</Document>";
        $body .= "</kml>";

        return $body;
    }

    private static function getPlacemarks($dataObj){

        $data = $dataObj->data;
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        // If no geo property is given, don't bother creating a KML
        if(empty($dataObj->geo)){
            \App::abort(400, "Map formatter not available for this resource.");
        }

        return self::getArray($dataObj, $dataObj->geo);
    }

    private static function xmlgetelement($value){
        // TODO decide what to do with the CDATA element
        $result = "<![CDATA[";
        $result .= "]]>";
        return $result;
    }

    private static function getExtendedDataElement($value){
        // TODO decide what to do with extended data element
        $result = "<ExtendedData>";
        $result .= "</ExtendedData>";
        return $result;
    }

    /**
     * Create the geo graphical placemarks in kml
     * Currently only properties that are not nested are picked up.
     */
    private static function getArray($dataObj, $geo){

        $body = "";

        $data = $dataObj->data;

        foreach($data as $key => $value) {

            if(is_array($value)) {
                $entry = $value;
            }else if (is_object($value)) {
                $entry = get_object_vars($value);
            }

            // We assume that if longitude exists, latitude does as well if the geometry is a single point
            // A point can either be a single column value, or split up in a latitude and longitude
            $geo_type = 'point';
            $is_point = (count($geo) > 1) || !empty($geo['point']);

            if(!$is_point){
                $geo_type = key($geo);
                $column_name = $geo[$geo_type];
            }

            if(!empty($entry)) {

                $name = self::xmlgetelement($entry);
                $extendeddata = self::getExtendedDataElement($entry);

                $body .= "<Placemark><name><![CDATA[<a href='" . \URL::to($dataObj->definition->collection_uri . '/' . $dataObj->definition->resource_name) . '/' .  htmlspecialchars($key)  . ".map'>". \URL::to($dataObj->definition->collection_uri . '/' . $dataObj->definition->resource_name) . '/' .  htmlspecialchars($key) ."</a>]]></name><description>".$name."</description>";
                $body .= $extendeddata;
                if($is_point) {

                    if(count($geo) > 1){
                        $point = $entry[$geo['longitude']] . ',' . $entry[$geo['latitude']];
                    }else{
                        $point = $entry[$geo['point']];
                    }

                    $body .= "<Point><coordinates>" . $point . "</coordinates></Point>";
                }else{
                    if($geo_type == 'polyline'){

                        $body .= "<MultiGeometry>";
                        foreach(explode(';', $entry[$geo['polyline']]) as $coord) {
                            $body .= "<LineString><coordinates>".$coord."</coordinates></LineString>";
                        }
                        $body .= "</MultiGeometry>";

                    }else if($geo_type == 'polygon'){
                        $body .= "<Polygon><outerBoundaryIs><LinearRing><coordinates>". $entry[$geo['polygon']] ."</coordinates></LinearRing></outerBoundaryIs></Polygon>";
                    }else{
                        \App::abort(500, "The geo type, $geo_type, is not supported. Make sure the (combined) geo type is correct. (e.g. latitude and longitude are given).");
                    }
                }
                $body .= "</Placemark>";
            }
        }

        return $body;
    }

    /**
     * Case insensitive version of array_key_exists.
     * Returns the matching key on success, else false.
     *
     * @param string $key
     * @param array $search
     * @return string|false
     */
    private static function array_key_exists_nc($key, $search) {
        if (array_key_exists($key, $search)) {
            return $key;
        }
        if (!(is_string($key) && is_array($search) && count($search))) {
            return false;
        }
        $key = strtolower($key);
        foreach ($search as $k => $v) {
            if (strtolower($k) == $key) {
                return $k;
            }
        }
        return false;
    }


    public static function getDocumentation(){
        return "Returns a KML file with geo properties of the data.";
    }

}
