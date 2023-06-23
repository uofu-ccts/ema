<?php

namespace Utah\EMA;

class EMA extends \ExternalModules\AbstractExternalModule
{
  
  public $project_id = null;
  public $username = USERID; // a REDCap constant; see redcap_info() output on the dev doc page

  // variables to store settings
  // this is where slider variables and settings are configured
  public $configured_variables = [];

  //list of fields
  public $survey_start_fields = [];
  public $survey_save_fields = [];

  public function __construct() {

    parent::__construct(); // call parent (AbstractExternalModule) constructor

    $this->project_id = $this->getProjectId(); // defined in AbstractExternalModule; will return project_id or null

  }

  

  function getSurveyStartSettings() {

    $configured_variables = $this->getProjectSettings();

    array_push($this->survey_start_fields,
                $configured_variables["start-date"]["value"][0],
                $configured_variables["num-days"]["value"][0],
                $configured_variables["status"]["value"][0],
                $configured_variables["setup-completion"]["value"][0]
                );
    
  }

  function getSurveyStartData() {
    /* $redcap_data will be structured as:
            [
                record_id => [
                    event_id => [
                        field_name => value,
                        ...
                        ],
                    ...
                    ],
                ...
            ]
        */
    
    // last field in array will be part of the filter logic
    // Only get setup variables where Setup instrument is complete
    $filter_logic = '[' . end($this->survey_start_fields) . '] = "2"';
    
    $get_data = [
      'project_id' => $this->project_id,
      'return_format' => 'json',
      'fields' => $this->survey_start_fields,
      'filterLogic' => $filter_logic
      ];
    $redcap_data = \REDCap::getData($get_data);

    return $redcap_data;
  }

  function generateRandomTime($startTime, $timeRange) {
    $todaysDateMDY = date("m/d/Y");

    $objDateTime = \DateTime::createFromFormat('m/d/Y', $todaysDateMDY);
    $objDateTime->setTime($startTime, 0);

    //generate random minutes, 0 - 180, 3 hour time block
    $intRangeMin = 0;
    $intRangeMax = $timeRange - 1;
    $randomNumber  = mt_rand($intRangeMin, $intRangeMax);

    $objDateTime->modify( "+{$randomNumber} minutes" );

    $randomTime  = $objDateTime->format('H:i'); 

    return $randomTime;
    
  } //end function: generateRandomTime

  function debug_to_console($data, $text='Debug Object',) {
    $output = json_encode($data);
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('" . $text . ": " . $output . "' );</script>";
  }
}