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

  function generateSchedules($records) {

  }

  function getDateParams($project_id, $record, $surveyStartField, $surveyDurationField) {
    $event_id = \REDCap::getEventIdFromUniqueEvent("event_1_arm_1");
    
    $fields = array($surveyStartField, $surveyDurationField);
    $params = array(
      'records' => $record,
      'events' => 'event_1_arm_1',
      'return_format' => 'array',
      'fields' => $fields
    );
    $data = \REDCap::getData($params);

    return $data[$record][$event_id];
  }

  function getTimeParams($project_id, $record, $rangeFields) {
    $event_id = \REDCap::getEventIdFromUniqueEvent("event_1_arm_1");
    
    $fields = $rangeFields;
    $params = array(
      'records' => $record,
      'events' => 'event_1_arm_1',
      'return_format' => 'array',
      'fields' => $fields
    );
    $data = \REDCap::getData($params);

    return $data[$record][$event_id];
  }

  /* 
    Returns an array of records that need a survey schedule generated
    Uses getRecordsWithSetup and getRecordsWithSchedule, and finds list of records that have a complete Survey Setup instrument, but blank Survey Schedule instruments
  */
  function getRecordsToSchedule($project_id, $setupCompletionField, $scheduleCompletionField, $surveyStatusField) {
    $setup_records = $this->getRecordsWithSetup($project_id, $setupCompletionField, $surveyStatusField);
    $scheduled_records = $this->getRecordsWithSchedule($project_id, $scheduleCompletionField);

    $records = array_diff($setup_records, $scheduled_records);

    return $records;
  }

  /*
    Returns a simple array of records that have a completed Survey Setup instrument
  */
  function getRecordsWithSetup($project_id, $setupCompletionField, $surveyStatusField) {
    $filter = "[event_1_arm_1][$setupCompletionField] = '2' AND [event_1_arm_1][$surveyStatusField] = '1'";
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

  /*
    Returns a simple array of records that have a non-blank Survey Schedule instrument for day 1 of surveys
  */
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

  function generateRandomTime($startTime, $endTime) {
    $startTimestamp = strtotime($startTime);
    $endTimestamp = strtotime($endTime);
  
    $randomTimestamp = mt_rand($startTimestamp, $endTimestamp);
  
    return date('H:i', $randomTimestamp);
  }

  function debug_to_console($data, $text='Debug Object',) {
    $output = json_encode($data);
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('" . $text . ": " . $output . "' );</script>";
  }
}