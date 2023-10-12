<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Modellist {

    /**
     * Codeigniter reference 
     */
    private $CI;
    private $EXT;

    /**
     * Array that will hold the controller names and methods
     */
    private $aModels;

    // Construct
    function __construct() {
        // Get Codeigniter instance 
        $this->CI = get_instance();
        $this->CI->EXT = ".php";
        // Get all controllers 
        $this->setModels();
    }

    /**
     * Return all controllers and their methods
     * @return array
     */
    public function getModels() {
        return $this->aModels;
    }

    /**
     * Set the array holding the controller name and methods
     */
    public function setModelMethods($p_sControllerName, $p_aControllerMethods) {
        $this->aModels[$p_sControllerName] = $p_aControllerMethods;
    }

    /**
     * Search and set controller and methods.
     */
    private function setModels() {
        // Loop through the controller directory
        foreach (glob(APPPATH . 'models/*') as $controller) {


            // if the value in the loop is a directory loop through that directory
            if (is_dir($controller)) {
                // Get name of directory
                $dirname = basename($controller, $this->CI->EXT);

                // Loop through the subdirectory
                foreach (glob(APPPATH . 'models/' . $dirname . '/*.php') as $subdircontroller) {
                    $subdircontrollername = basename($subdircontroller, $this->CI->EXT);
                    // Add the controllername to the array with its method; 
                    debug($subdircontrollername);
                    $aMethods = get_class_methods($subdircontrollername);
                    $aUserMethods = array();
                    foreach ($aMethods as $method) {
                        if ($method != '__construct' && $method != 'get_instance' && $method != $subdircontrollername) {
                            $aUserMethods[] = $method;
                        }
                    }
                }
            } else if (pathinfo($controller, PATHINFO_EXTENSION) == "php") {
                // value is no directory get controller name            


                $controllername = basename($controller, $this->CI->EXT);

                // Load the class in memory (if it's not loaded already)
                if (!class_exists($controllername)) {
                    $this->CI->load->file($controller);
                }

                // Add controller and methods to the array
                $aMethods = get_class_methods($controllername);
                $aUserMethods = array();
                if (is_array($aMethods)) {
                    foreach ($aMethods as $method) {
                        if ($method != '__construct' && $method != 'get_instance' && $method != '__get'  && $method != $controllername) {
                            $aUserMethods[] = $method;
                        }
                    }
                }

                $this->setModelMethods($controllername, $aUserMethods);
            }
        }
    }
}
