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

namespace Gishiki\ORM\ModelBuilding {

    /**
     * This class provides the core Ahead-Of-Time component:
     * the code generator.
     * 
     * This component is used to generate PHP classes out of the given
     * database structure, binding activerecord functionalities to that
     * generated classes.
     *
     * @author Benato Denis <benato.denis96@gmail.com>
     */
    class ModelBuilder {
        //this is the database structure deducted by the analyzer
        private $database_structure;
        
        //this is the list of tables
        private $tables;
        
        //was the error check performed
        private $checked;
        
        /**
         * Create a code generator over the given database structure
         * 
         * @param \Gishiki\ORM\Common\Database $dbStructure the result of the analysis
         */
        public function __construct(\Gishiki\ORM\Common\Database &$dbStructure) {
            //no error check performed (yet)
            $this->checked = FALSE;
                    
            //this is the table 
            $this->tables = array();
            
            if ($dbStructure != NULL) //store a reference to the database structure if it is valid
            {   $this->database_structure = $dbStructure;   }
        }
        
        /**
         * Perform an accurate error check on the result of the analyzer and 
         * prepare the structure for future steps
         * 
         * @throws \Gishiki\ORM\ModelBuilding\ModelBuildingException the caught error
         */
        public function ErrorsCheck() {
            $table_names = array();
            
            //check for the name validity of the database
            if (!$this->database_structure->hasValidName())
            {   throw new \Gishiki\ORM\ModelBuilding\ModelBuildingException("'".$this->database_structure."' is not a valid name for a database", 10); }
                
            //check if each table has a valid name
            foreach ($this->database_structure as $table_index => $current_table) {
                if (!$current_table->hasValidName()) //check for the name validity
                {   throw new \Gishiki\ORM\ModelBuilding\ModelBuildingException("in database '".$this->database_structure."': ".$current_table."' is not a valid name for a database table", 11); }
                else {
                    //check if a table has two field with the same name
                    if (!in_array($current_table, $table_names))
                    { //check if a table with the same name have been already added
                        $table_names[] = $current_table;
                        
                        //check for tables without a primary key
                        if (!$current_table->hasPrimaryKey())
                        {   throw new \Gishiki\ORM\ModelBuilding\ModelBuildingException("in database '".$this->database_structure."': in table ".$current_table."': the table doesn't have a primary key", 12);    }
                    }
                    else
                    {   throw new \Gishiki\ORM\ModelBuilding\ModelBuildingException("in database '".$this->database_structure."': the ".$current_table."' name is used for two or many tables", 13);   }
                }
            }
            
            //errors check performed
            $this->checked = TRUE;
        }
        
        /**
         * Perform the PHP code generation, doesn't prepend '<?php' to the code.
         * 
         * The code is not eval()'d, but can be directly eval()'d: this
         * is usefull when a bad storage slows down the server Gishiki is 
         * running from
         * 
         * @return string the generated PHP code
         */
        public function Compile() {
            //start up the code generation with a code opening tag
            $generated_code = /*"<?php\n".*/ "";
            
            //emplace a notice
            $generated_code .= "/*******************************************".PHP_EOL;
            $generated_code .= " automatically generated by the Gishiki ORM ".PHP_EOL;
            $generated_code .= "   do not edit unless extremely necessary   ".PHP_EOL;
            $generated_code .= "********************************************/".PHP_EOL.PHP_EOL.PHP_EOL;
            
            //generate a class from each table
            foreach ($this->database_structure as $current_table) {
                //generate the base structure of the class
                $generated_code .= "/**".PHP_EOL;
                $generated_code .= " * The model built around the description of the table \"".$current_table."\" ".PHP_EOL;
                $generated_code .= " * of the database named \"".$this->database_structure."\".".PHP_EOL;
                $generated_code .= " *".PHP_EOL;
                $generated_code .= " * @author nobody <auto-generated>".PHP_EOL;
                $generated_code .= " */".PHP_EOL;
                $generated_code .= "class ".$current_table." extends \Gishiki\ORM\Runtime\Gishiki_Model {".PHP_EOL.PHP_EOL;
                $generated_code .= "    /**".PHP_EOL;
                $generated_code .= "     * get the name of the connection to the proper database".PHP_EOL;
                $generated_code .= "     * ".PHP_EOL;
                $generated_code .= "     * @return string the name of the connection to be used".PHP_EOL;
                $generated_code .= "     */".PHP_EOL;
                $generated_code .= "    protected static function Connection_Name() {".PHP_EOL;
                $generated_code .= "        //auto-generated this is the core of the model! !!!BEWARE!!!;".PHP_EOL;
                $generated_code .= "        return \"".$this->database_structure->getConnection()."\";".PHP_EOL;
                $generated_code .= "    }".PHP_EOL.PHP_EOL;
                $generated_code .= "    /**".PHP_EOL;
                $generated_code .= "     * get the name of the primary key for the current representation of a table".PHP_EOL;
                $generated_code .= "     * ".PHP_EOL;
                $generated_code .= "     * @return string the name of the primary key column".PHP_EOL;
                $generated_code .= "     */".PHP_EOL;
                $generated_code .= "    protected static function PrimaryKey_Name() {".PHP_EOL;
                $generated_code .= "        //auto-generated this is the core of the model! !!!BEWARE!!!;".PHP_EOL;
                $generated_code .= "        return \"".$current_table->getPrimaryKey()."\";".PHP_EOL;
                $generated_code .= "    }".PHP_EOL.PHP_EOL;
                $generated_code .= "    /**".PHP_EOL;
                $generated_code .= "     * Perform basic initialization on the model and flag".PHP_EOL;
                $generated_code .= "     * the object as to be automatically mapped to the database".PHP_EOL;
                $generated_code .= "     * using the connection named \"".$this->database_structure->getConnection()."\".".PHP_EOL;
                $generated_code .= "     */".PHP_EOL;
                $generated_code .= "    public function __construct() {".PHP_EOL;
                $generated_code .= "        //perform basic setup operations".PHP_EOL;
                $generated_code .= "        parent::__construct();".PHP_EOL.PHP_EOL;
                $generated_code .= "        //initialize a new model/object".PHP_EOL;
                $generated_code .= "        \$this->array = array(".PHP_EOL;
                foreach ($current_table as $current_field) {
                    $generated_code .= "            \"".$current_field."\" => NULL,".PHP_EOL;
                }
                $generated_code .= "        );".PHP_EOL;
                $generated_code .= "    }".PHP_EOL.PHP_EOL;
                $generated_code .= "    /**".PHP_EOL;
                $generated_code .= "     * Release every used resource and save the model to".PHP_EOL;
                $generated_code .= "     * the database if the operation was not marked as illegal".PHP_EOL;
                $generated_code .= "     * by using the illegalAutoUpdate() function.".PHP_EOL;
                $generated_code .= "     */".PHP_EOL;
                $generated_code .= "    public function __destruct() {".PHP_EOL;
                $generated_code .= "        //auto-update the database and shutdown operations".PHP_EOL;
                $generated_code .= "        parent::__destruct();".PHP_EOL;
                $generated_code .= "    }".PHP_EOL.PHP_EOL;
                
                //create getters and setters
                foreach ($current_table as $current_field) {
                    //this is the field name in the database
                    $field_name = "".$current_field;
                    
                    //this is the field name for comments
                    $comment_field_name = str_replace("_", " ", $field_name);
                    
                    //this is the field name for function names
                    $field_upchar_name = ucfirst($field_name);
                    
                    //type subsystem
                    $type_name = "";
                    $type_filter = "";
                    switch ($current_field->getDataType()) {
                        case \Gishiki\ORM\Common\DataType::BOOLEAN:
                            $type_name = "boolean";
                            $type_filter = "boolval";
                            break;
                        case \Gishiki\ORM\Common\DataType::FLOAT:
                            $type_name = "float";
                            $type_filter = "floatval";
                            break;
                        case \Gishiki\ORM\Common\DataType::INTEGER:
                            $type_name = "integer";
                            $type_filter = "intval";
                            break;
                        case \Gishiki\ORM\Common\DataType::STRING:
                            $type_name = "string";
                            $type_filter = "strval";
                            break;
                        default:
                            $type_name = "mixed";
                            $type_filter = "";
                            break;
                    }
                    
                    $generated_code .= "    /**".PHP_EOL;
                    $generated_code .= "     * Get the ".$comment_field_name." of the current ".$current_table."".PHP_EOL;
                    $generated_code .= "     *".PHP_EOL;
                    $generated_code .= "     * @return ".$type_name." the ".$comment_field_name." of the ".$current_table.PHP_EOL;
                    $generated_code .= "     */".PHP_EOL;
                    $generated_code .= "    public function get".$field_upchar_name."() {".PHP_EOL;
                    $generated_code .= "        return \$this->get(\"".$field_name."\");".PHP_EOL;
                    $generated_code .= "    }".PHP_EOL.PHP_EOL;
                    $generated_code .= "    /**".PHP_EOL;
                    $generated_code .= "     * Set the ".$comment_field_name." of the current ".$current_table."".PHP_EOL;
                    $generated_code .= "     *".PHP_EOL;
                    $generated_code .= "     * @param ".$type_name." \$val the new ".$comment_field_name." of the ".$current_table.PHP_EOL;
                    $generated_code .= "     * @return ".$type_name." the new ".$comment_field_name." of the ".$current_table.PHP_EOL;
                    $generated_code .= "     */".PHP_EOL;
                    $generated_code .= "    public function set".$field_upchar_name."(\$val) {".PHP_EOL;
                    $generated_code .= "        return \$this->set(\"".$field_name."\", \$val);".PHP_EOL;
                    $generated_code .= "    }".PHP_EOL.PHP_EOL;
                }
                
                
                $generated_code .= "}".PHP_EOL.PHP_EOL.PHP_EOL;
            }
            
            //return the generated php code
            return $generated_code;
        }
        
    }
}
