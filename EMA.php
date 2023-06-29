<?php

namespace Utah\EMA;

use ExternalModules\AbstractExternalModule;

class EMA extends AbstractExternalModule
{
  
  public $project_id = null;
  public $username = USERID; // a REDCap constant; see redcap_info() output on the dev doc page
  public $errorLog = [];

  //list of fields, will be populated by getFieldNames
  public $setupCompletionField = "";
  public $surveyStartField = "";
  public $surveyStatusField = "";
  public $surveyDurationField = "";
  public $scheduleCompletionField = "";
  public $sendDateField = "";
  public $sendTimeFields = [];
  public $sendFlagFields = [];
  public $startRangeFields = [];
  public $expireRangeFields = [];
  public $expireTimeFields = [];
  public $expireFlagFields = [];

  public function __construct() {

    parent::__construct(); // call parent (AbstractExternalModule) constructor

    $this->project_id = $this->getProjectId(); // defined in AbstractExternalModule; will return project_id or null

  }

  function getFieldNames($project_id) {
    $this->setupCompletionField = implode($this->getProjectSetting('setup-completion', $project_id));
    $this->surveyStartField = implode($this->getProjectSetting('start-date', $project_id));
    $this->surveyStatusField = implode($this->getProjectSetting('status', $project_id));
    $this->surveyDurationField = implode($this->getProjectSetting('num-days', $project_id));
    $this->scheduleCompletionField = implode($this->getProjectSetting('schedule-completion', $project_id));
    $this->sendDateField = implode($this->getProjectSetting('send-date', $project_id));
    $this->sendTimeFields = $this->getProjectSetting('send-time', $project_id)[0];
    $this->sendFlagFields = $this->getProjectSetting('send-flag', $project_id)[0];
    $this->startRangeFields = $this->getProjectSetting('start-range', $project_id)[0];
    $this->expireRangeFields = $this->getProjectSetting('expire-range', $project_id)[0];
    $this->expireTimeFields = $this->getProjectSetting('expire-time', $project_id)[0];
    $this->expireFlagFields = $this->getProjectSetting('expire-flag', $project_id)[0];
  }

  function generateSchedules($records, $project_id, $surveyStartField, $surveyDurationField, $startRangeFields, $expireRangeFields, $sendDateField, $sendTimeFields, $sendFlagFields, $expireTimeFields, $expireFlagFields, $errorLog) {
    $dataToSave = [];
    foreach ($records as $record) {

      $this->debug_to_console($project_id, "project_id");
      $this->debug_to_console($record, "record");
      $this->debug_to_console($surveyStartField, "surveystartfield");
      $this->debug_to_console($surveyDurationField, "surveydurationfield");

      $dateParams = $this->getDateParams($project_id, $record, $surveyStartField, $surveyDurationField);

      $this->debug_to_console($dateParams, "Date params");

      if (!$dateParams[$surveyDurationField]) {
        array_push($errorLog, "$surveyDurationField is missing for record $record. Moving on to next record...");
        continue;
      }

      if (!$dateParams[$surveyStartField]) {
        array_push($errorLog, "$surveyStartField is missing for record $record. Moving on to next record...");
        continue;
      }

      $startParams = $this->getTimeParams($project_id, $record, $startRangeFields);

      if (count($startParams) != count($startRangeFields)) {
        $errorText = implode($startRangeFields);
        array_push($errorLog, "One of the $errorText is missing for record $record. Moving on to next record...");
        continue;
      }

      $expireParams = $this->getTimeParams($project_id, $record, $expireRangeFields);

      if (count($expireParams) != count($expireRangeFields)) {
        $errorText = implode($expireRangeFields);
        array_push($errorLog, "One of the $errorText is missing for record $record. Moving on to next record...");
        continue;
      }

      $numDays = $dateParams[$surveyDurationField];
      $startDate = new \DateTimeImmutable($dateParams[$surveyStartField]);

      print_r('Current record: ' . $record . '<br>');

      $dataToSave[$record] = [];

      for ( $currentDay=1; $currentDay <= $numDays; $currentDay++ ) {

        $currentRedcapEvent = 'day_' . $currentDay . '_arm_1';
        $currentSurveyDate = $startDate->add(new \DateInterval('P' . $currentDay-1 . 'D'))->format('Y-m-d');

        $unique_event_id = \REDCap::getEventIdFromUniqueEvent($currentRedcapEvent);

        $dataToSave[$record][$unique_event_id] = [];

        $dataToSave[$record][$unique_event_id][$sendDateField] = $currentSurveyDate;

        print_r('Current event: ' . $currentRedcapEvent);
        print_r("<br>");
        print_r('Scheduled survey date: ' . $currentSurveyDate);
        print_r("<br>");

        for ( $currentSurvey=0; $currentSurvey < count($sendTimeFields); $currentSurvey++ ) {
          $startTime = $startParams[$startRangeFields[$currentSurvey]];
          $sendFlag = 0; // 1 = true, 0 = false
          $expireTime = $expireParams[$expireRangeFields[$currentSurvey]];
          $expireFlag = 0; // 1 = true, 0 = false

          $sendTime = $this->generateRandomTime($startTime, $expireTime);

          $dataToSave[$record][$unique_event_id][$sendTimeFields[$currentSurvey]] = $sendTime;
          $dataToSave[$record][$unique_event_id][$sendFlagFields[$currentSurvey]] = $sendFlag;
          $dataToSave[$record][$unique_event_id][$expireTimeFields[$currentSurvey]] = $expireTime;
          $dataToSave[$record][$unique_event_id][$expireFlagFields[$currentSurvey]] = $expireFlag;

          print_r('Time to send survey: ' . $sendTime);
          print_r("<br>");
          print_r('Flag to send survey: ' . $sendFlag);
          print_r("<br>");
          print_r('Time to expire survey: ' . $expireTime);
          print_r("<br>");
          print_r('Flag to expire survey: ' . $expireFlag);
          print_r("<br>");
        }

        print_r("<br>");
      }
    }

    return $dataToSave;
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

  function saveSchedules($dataToSave) {
    $params = array(
      'dataFormat' => 'array',
      'data' => $dataToSave,
      'overwriteBehavior' => 'normal',
      'dateFormat' => 'YMD'
    );
    
    $response = \REDCap::saveData($params);

    return $response;
  }

  /** 
   * @param array $cronAttributes A copy of the cron's configuration block from config.json.
   */
  function cron() {
    foreach($this->getProjectsWithModuleEnabled() as $localProjectId){
      $this->setProjectId($localProjectId);
  
      // Project specific method calls go here.
      $sendDateField = implode($this->getProjectSetting('send-date'));
      $this->scheduleCompletionField = implode($this->getProjectSetting('schedule-completion'));
      $sendDateField = implode($this->getProjectSetting('send-date'));
      $sendTimeFields = $this->getProjectSetting('send-time')[0];
      $sendFlagFields = $this->getProjectSetting('send-flag')[0];
      $expireTimeFields = $this->getProjectSetting('expire-time')[0];
      $expireFlagFields = $this->getProjectSetting('expire-flag')[0];

      $todaysRecords = $this->getTodaysRecords($this->project_id, $sendDateField, $sendTimeFields, $sendFlagFields, $expireTimeFields, $expireFlagFields);

      $this->debug_to_console($todaysRecords);
    }
  
    return "The cron job completed successfully.";
  }

  function getTodaysRecords($project_id, $sendDateField, $sendTimeFields, $sendFlagFields, $expireTimeFields, $expireFlagFields) {
    $todaysDate = date("Y-m-d");

    $filter = "[$sendDateField] = '$todaysDate'";

    $fields = array('record_id');

    array_push($fields, $sendTimeFields, $sendFlagFields, $expireTimeFields, $expireFlagFields);

    $this->debug_to_console($fields);

    $params = array(
      'return_format' => 'array',
      'fields' => array('record_id'),
      'filterLogic' => $filter
    );
    $data = \REDCap::getData($params);

    return $data;
  }

  function debug_to_console($data, $text='Debug Object',) {
    $output = json_encode($data);
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('" . $text . ": " . $output . "' );</script>";
  }
}