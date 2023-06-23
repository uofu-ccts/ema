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

  // Returns an array of records that need a survey schedule generated
  // Uses getRecordsWithSetup and getRecordsWithSchedule, and finds list of records that have a complete Survey Setup instrument, but blank Survey Schedule instruments
  function getRecordsToSchedule($project_id, $setupCompletionField, $scheduleCompletionField) {
    $setup_records = $this->getRecordsWithSetup($project_id, $setupCompletionField);
    $scheduled_records = $this->getRecordsWithSchedule($project_id, $scheduleCompletionField);

    $records = array_diff($setup_records, $scheduled_records);

    return $records;
  }

  // Returns a simple array of records that have a completed Survey Setup instrument
  function getRecordsWithSetup($project_id, $setupCompletionField) {
    $filter = "[event_1_arm_1][$setupCompletionField] = '2'";
    $params = array(
      'return_format' => 'array',
      'fields' => array('record_id'),
      'filterLogic' => $filter
    );
    $data = \REDCap::getData($params);

    $records = [];
    foreach ($data as $record) {
      foreach ($record as $event) {
        $records[] = $event['record_id'];
      }
    }

    return $records;
  }

  // Returns a simple array of records that have a non-blank Survey Schedule instrument for day 1 of surveys
  function getRecordsWithSchedule($project_id, $scheduleCompletionField) {
    $filter = "[day_1_arm_1][$scheduleCompletionField] = '0' OR [day_1_arm_1][$scheduleCompletionField] = '1' OR [day_1_arm_1][$scheduleCompletionField] = '2'";
    $params = array(
      'return_format' => 'array',
      'fields' => array('record_id'),
      'filterLogic' => $filter
    );
    $data = \REDCap::getData($params);

    $records = [];
    foreach ($data as $record) {
      foreach ($record as $event) {
        if ($event['record_id'] != '') {
          $records[] = $event['record_id'];
        }
      }
    }

    return $records;
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