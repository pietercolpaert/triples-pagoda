<?php

/**
 * CSV definition model
 * @copyright (C) 2011,2013 by OKFN Belgium vzw/asbl
 * @license AGPLv3
 * @author Jan Vansteenlandt <jan@okfn.be>
 */
class CsvDefinition extends SourceType{

    protected $table = 'csvdefinitions';

    protected $fillable = array('uri', 'delimiter', 'has_header_row', 'start_row', 'description');

    /**
     * Relationship with the TabularColumns model.
     */
    public function tabularColumns(){
        return $this->morphMany('TabularColumns', 'tabular');
    }

    /**
     * Relationship with the Definition model.
     */
    public function definition(){
        return $this->morphOne('Definition', 'source');
    }

    /**
     * Relationship with the Geo properties model.
     */
    public function geoProperties(){
        return $this->morphMany('GeoProperty', 'source');
    }

    /**
     * Hook into the save function of Eloquent by saving the parent
     * and establishing a relation to the TabularColumns model.
     *
     * Pre-requisite: parameters have already been validated.
     */
    public function save(array $options = array()){

        // Check for passed columns
        $columns = @$options['columns'];

        if(empty($columns)){

            // Parse the columns of the csv file
            $columns = $this->parseColumns($options);
        }

        // Unset the pk parameter, serves as a shortcut for the columns configuration
        unset($this->pk);

        // If the columns were parsed correctly, save this definition and use the id to link them to the column objects
        parent::save();

        // If the model has not been saved, abort the workflow and return an error message
        if(empty($this->id)){
            \App::abort(452, "The csv definition could not be saved after validation, check if the provided properties are still correct.");
        }

        foreach($columns as $column){

            $tabular_column = new TabularColumns();
            $tabular_column->index = $column['index'];
            $tabular_column->column_name = $column['column_name'];
            $tabular_column->is_pk = $column['is_pk'];
            $tabular_column->column_name_alias = $column['column_name_alias'];
            $tabular_column->tabular_type = 'CsvDefinition';
            $tabular_column->tabular_id = $this->id;
            $tabular_column->save();
        }

        // Check for passed geo_properties
        $geo_props = @$options['geo'];

        if(!empty($geo_props)){
            foreach($geo_props as $geo_entry){

                $geo_property = new GeoProperty();
                $geo_property->path = $geo_entry['path'];
                $geo_property->property = $geo_entry['property'];
                $geo_property->source_id = $this->id;
                $geo_property->source_type = 'CsvDefinition';
                $geo_property->save();
            }
        }

        return true;
    }


    /**
     * Validate the input for this model and related models.
     */
    public static function validate($params){

        $geo_params = @$params['geo'];
        GeoProperty::validate($geo_params);

        $tabular_params = @$params['columns'];
        TabularColumns::validate($tabular_params);

        $csv_params = array_only($params, array_keys(self::getCreateParameters()));
        return parent::validate($csv_params);
    }

    /**
     * Retrieve the set of create parameters that make up a CSV definition.
     * Include the parameters that make up relationships with this model.
     */
    public static function getAllParameters(){

        $column_params = array('columns' => array('description' => 'Columns must be an array of objects of which the template is described in the parameters section.',
                                                'parameters' => TabularColumns::getCreateParameters(),
                                            )
        );

        $geo_params = array('geo' => array('description' => 'Geo must be an array of objects of which the template is described in the parameters section.',
                                            'parameters' => GeoProperty::getCreateParameters(),
        ));

        return array_merge(self::getCreateParameters(), $column_params, $geo_params);
    }

    /**
     * Retrieve the set of validation rules for every create parameter.
     * If the parameters doesn't have any rules, it's not mentioned in the array.
     */
    public static function getCreateValidators(){
        return array(
            'has_header_row' => 'integer|min:0|max:1',
            'start_row' => 'integer',
            'uri' => 'file|required',
            'description' => 'required',
        );
    }

    /**
     * Retrieve colummn information from the request parameters.
     */
    private function parseColumns($options){

        // Get the columns out of the csv file before saving the csv definition
        // If columns are being passed using the json body or request parameters
        // allow them to function as aliases, aliases have to be passed as index (0:n-1) => alias
        $aliases = @$options['columns'];
        $pk = @$options['pk'];

        if(empty($aliases)){
            $aliases = array();
        }

        $columns = array();

        if(($handle = fopen($this->uri, "r")) !== FALSE) {

            // Throw away the lines untill we hit the start row
            // from then on, process the columns
            $commentlinecounter = 0;

            while ($commentlinecounter < $this->start_row) {
                $line = fgetcsv($handle, 0, $this->delimiter, '"');
                $commentlinecounter++;
            }

            $index = 0;

            if (($line = fgetcsv($handle, 0, $this->delimiter, '"')) !== FALSE) {

                $index++;

                for ($i = 0; $i < sizeof($line); $i++) {

                    // Try to get an alias from the options, if it's empty
                    // then just take the column value as alias
                    $alias = @$aliases[$i];

                    if(empty($alias)){
                        $alias = trim($line[$i]);
                    }

                    array_push($columns, array('index' => $i, 'column_name' => trim($line[$i]), 'column_name_alias' => $alias, 'is_pk' => ($pk == $i)));
                }
            }else{
                \App::abort(452, "The columns could not be retrieved from the csv file on location $uri.");
            }
            fclose($handle);
        } else {
            \App::abort(452, "The columns could not be retrieved from the csv file on location $uri.");
        }

        return $columns;
    }

    /**
     * Return the properties ( = column fields ) for this model.
     */
    public static function getCreateParameters(){
        return array(
                'uri' => array(
                    'required' => true,
                    'description' => 'The location of the CSV file, either a URL or a local file location.',
                ),
                'delimiter' => array(
                    'required' => false,
                    'description' => 'The delimiter of the separated value file.',
                    'default_value' => ',',
                ),
                'has_header_row' => array(
                    'required' => false,
                    'description' => 'Boolean parameter defining if the separated value file contains a header row that contains the column names.',
                    'default_value' => 1,
                ),
                'start_row' => array(
                    'required' => false,
                    'description' => 'Defines the row at which the data (and header row if present) starts in the file.',
                    'default_value' => 0,
                ),
                'description' => array(
                    'required' => true,
                    'description' => 'The descriptive or informational string that provides some context for you published dataset.',
                ),
                'pk' => array(
                    'required' => false,
                    'description' => 'This is a shortcut to define a primary key of this dataset. The value must be the index of the column you want each row to be mapped on. The pk property will never explicitly appear in the definition, but will manifest itself as part of a column property.'
                ),
        );
    }

    /**
     * Because we have related models, and non hard defined foreign key relationships
     * we have to delete our related models ourselves.
     */
    public function delete(){

        // Get the related columns
        $columns = $this->tabularColumns()->getResults();

        foreach($columns as $column){
            $column->delete();
        }

        // Get the related geo properties
        $geo_properties = $this->geoProperties()->getResults();

        foreach($geo_properties as $geo_property){
            $geo_property->delete();
        }

        parent::delete();
    }
}