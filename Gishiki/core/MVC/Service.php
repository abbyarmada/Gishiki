<?php
/**************************************************************************
Copyright 2015 Benato Denis

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*****************************************************************************/    

namespace Gishiki\Core\MVC {
    
    /**
     * The Gishiki base controller for web services.
     * 
     * Every service offered by the application should
     * a function inside a class that inherits from
     * this class.
     * 
     * @author Benato Denis <benato.denis96@gmail.com>
     */
    class Service extends Controller {

        /**
         * Initialize the services container instance.
         * 
         * Each interface controller should call this constructor.
         */
        public function __construct() {
            //call the parent constructor
            parent::__construct();
        }

        /**
         * Dispose the service container instance.
         * 
         * Each interface controller should call this destructor.
         */
        public function __destruct() {
            //call the parent destructor
            parent::__destruct();
        }
        
        
        /**
         * Perform a call to the specified Service over the HTTP or HTTPS protocol.
         * 
         * @param string $service_URL the URL used to reach the needed service
         * @param array  $service_details additionals details that are required and used by the given subroutine
         * @param function $error_callback this is the function called if the API call encoutered an error
         * 
         * @return array the result of the Service execution
         */
        static function API_Call($service_URL, $service_details, $error_callback) /*: array */ {
            if (!array_key_exists("TIMESTAMP", $service_details)) //add the timestamp to the request
            {   $service_details["TIMESTAMP"] = time(); }

            //pass request details as a json
            $request_details = json_encode($service_details);
            
            // create a new cURL resource
            $api_call = curl_init();

            //build the cURL request to be performed
            curl_setopt_array($api_call, [ 
                CURLOPT_POST => true, 
                CURLOPT_HEADER => false, 
                CURLOPT_URL => $service_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POSTFIELDS => http_build_query(array("data" => $request_details)) 
            ]);
            
            // grab URL and pass it to the browser
            $result_details = curl_exec($api_call);
            if ($result_details === false) {
                $error_callback(/*curl_error($api_call), curl_errno($api_call)*/);
                $result_details = "{ }";
            }
            
            // close cURL resource, and free up system resources
            curl_close($api_call);
            
            //return the API call result
            return json_decode($result_details);
        }
    }
}