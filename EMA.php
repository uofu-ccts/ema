<?php

namespace Utah\EMA;

use ExternalModules\AbstractExternalModule;

class EMA extends AbstractExternalModule
{

  public $project_id = null;
  public $username = USERID; // a REDCap constant; see redcap_info() output on the dev doc page
  public $errorLog = [];

  public $generatorUrl = "";

  //prescribed events
  public $setupEvent = "survey_setup_arm_1";
  public $firstDayEvent = "day_1_arm_1";
  public $testEvent = "";

  //list of fields, will be populated by getFieldNames
  public $setupCompletionField = "ema_survey_setup_complete";
  public $surveyStartField = "ema_survey_start_date";
  public $surveyStatusField = "ema_survey_status";
  public $surveyDurationField = "ema_survey_num_days";
  public $scheduleCompletionField = "ema_survey_schedule_complete";
  public $sendDateField = "ema_survey_send_date";
  public $sendTimeFields = [];
  public $sendFlagFields = [];
  public $startRangeFields = [];
  public $expireRangeFields = [];
  public $expireTimeFields = [];
  public $expireFlagFields = [];

  public $expireBufferList = "";

  public function __construct()
  {

    parent::__construct(); // call parent (AbstractExternalModule) constructor

    $this->project_id = $this->getProjectId(); // defined in AbstractExternalModule; will return project_id or null

    $this->generatorUrl = $this->getUrl("generateSchedule.php?pid={$this->project_id}");
  }

  function getFieldNames($project_id)
  {
    $this->sendTimeFields = $this->getProjectSetting('send-time', $project_id);
    $this->sendFlagFields = $this->getProjectSetting('send-flag', $project_id);
    $this->startRangeFields = $this->getProjectSetting('start-range', $project_id);
    $this->expireRangeFields = $this->getProjectSetting('expire-range', $project_id);
    $this->expireTimeFields = $this->getProjectSetting('expire-time', $project_id);
    $this->expireFlagFields = $this->getProjectSetting('expire-flag', $project_id);
    $this->expireBufferList = $this->getProjectSetting('expire-buffer', $project_id);

    $this->testEvent = $this->getProjectSetting('test-event', $project_id);
  }

  function generateSchedules($records, $project_id, $surveyStartField, $surveyDurationField, $startRangeFields, $expireRangeFields, $sendDateField, $sendTimeFields, $sendFlagFields, $expireTimeFields, $expireFlagFields, $expireBufferList, $errorLog)
  {
    $dataToSave = [];
    foreach ($records as $record) {

      $dateParams = $this->getDateParams($project_id, $record, $surveyStartField, $surveyDurationField);

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

      print_r('<strong>Record ID: ' . $record . '</strong><br>');

      $dataToSave[$record] = [];

      for ($currentDay = 1; $currentDay <= $numDays; $currentDay++) {

        $currentRedcapEvent = 'day_' . $currentDay . '_arm_1';
        $currentSurveyDate = $startDate->add(new \DateInterval('P' . ($currentDay - 1) . 'D'))->format('Y-m-d');

        $unique_event_id = \REDCap::getEventIdFromUniqueEvent($currentRedcapEvent);

        $dataToSave[$record][$unique_event_id] = [];

        $dataToSave[$record][$unique_event_id][$sendDateField] = $currentSurveyDate;

        print_r('Survey event name: ' . $currentRedcapEvent);
        print_r("<br>");
        print_r('Scheduled survey date: ' . $currentSurveyDate);
        print_r("<br>");
        print_r('Scheduled survey times: ');

        for ($currentSurvey = 0; $currentSurvey < count($sendTimeFields); $currentSurvey++) {
          $startTime = $startParams[$startRangeFields[$currentSurvey]];
          $sendFlag = 0; // 1 = true, 0 = false
          $expireTime = $expireParams[$expireRangeFields[$currentSurvey]];
          $expireFlag = 0; // 1 = true, 0 = false
          $expireBuffer = $expireBufferList[$currentSurvey];

          $sendTime = $this->generateRandomTime($startTime, $expireTime, $expireBuffer);

          $dataToSave[$record][$unique_event_id][$sendTimeFields[$currentSurvey]] = $sendTime;
          $dataToSave[$record][$unique_event_id][$sendFlagFields[$currentSurvey]] = $sendFlag;
          $dataToSave[$record][$unique_event_id][$expireTimeFields[$currentSurvey]] = $expireTime;
          $dataToSave[$record][$unique_event_id][$expireFlagFields[$currentSurvey]] = $expireFlag;

          if ($currentSurvey > 0) {
            print_r(', ');
          }
          print_r($sendTime);
        }

        // need to set the schedule completion field for this event
        $dataToSave[$record][$unique_event_id][$this->scheduleCompletionField] = 0; // 0 = incomplete

        print_r("<br>");
      }
    }
    print_r("<br>");
    return $dataToSave;
  }

  function generateTestSchedules($records, $project_id, $testEvent, $surveyStartField, $surveyDurationField, $startRangeFields, $expireRangeFields, $sendDateField, $sendTimeFields, $sendFlagFields, $expireTimeFields, $expireFlagFields, $errorLog)
  {
    $dataToSave = [];
    foreach ($records as $record) {

      $dateParams = $this->getDateParams($project_id, $record, $surveyStartField, $surveyDurationField);

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

      $startDate = new \DateTimeImmutable($dateParams[$surveyStartField]);

      print_r('<strong>Record ID: ' . $record . '</strong><br>');

      $dataToSave[$record] = [];

      $today = date("Y-m-d");

      $unique_event_id = $testEvent;
      $testEventName = \REDCap::getEventNames(true, true, $testEvent);

      $dataToSave[$record][$unique_event_id] = [];

      $dataToSave[$record][$unique_event_id][$sendDateField] = $today;

      print_r('Survey event name: ' . $testEventName);
      print_r("<br>");
      print_r('Scheduled survey date: ' . $today);
      print_r("<br>");
      print_r('Scheduled survey times: ');

      for ($currentSurvey = 0; $currentSurvey < count($sendTimeFields); $currentSurvey++) {
        $startTime = $startParams[$startRangeFields[$currentSurvey]];
        $sendFlag = 1; // 1 = true, 0 = false
        $expireTime = $expireParams[$expireRangeFields[$currentSurvey]];
        $expireFlag = 0; // 1 = true, 0 = false

        $sendTime = date("H:i");

        $dataToSave[$record][$unique_event_id][$sendTimeFields[$currentSurvey]] = $sendTime;
        $dataToSave[$record][$unique_event_id][$sendFlagFields[$currentSurvey]] = $sendFlag;
        $dataToSave[$record][$unique_event_id][$expireTimeFields[$currentSurvey]] = $expireTime;
        $dataToSave[$record][$unique_event_id][$expireFlagFields[$currentSurvey]] = $expireFlag;

        if ($currentSurvey > 0) {
          print_r(', ');
        }
        print_r($sendTime);
      }

      print_r("<br>");
    }
    print_r("<br>");

    return $dataToSave;
  }

  function getDateParams($project_id, $record, $surveyStartField, $surveyDurationField)
  {
    $event_id = \REDCap::getEventIdFromUniqueEvent($this->setupEvent);

    $fields = array($surveyStartField, $surveyDurationField);
    $params = array(
      'project_id' => $project_id,
      'records' => $record,
      'events' => $this->setupEvent,
      'return_format' => 'array',
      'fields' => $fields
    );
    $data = \REDCap::getData($params);

    return $data[$record][$event_id];
  }

  function getTimeParams($project_id, $record, $rangeFields)
  {
    $event_id = \REDCap::getEventIdFromUniqueEvent($this->setupEvent);

    $fields = $rangeFields;
    $params = array(
      'project_id' => $project_id,
      'records' => $record,
      'events' => $this->setupEvent,
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
  function getRecordsToSchedule($project_id, $setupCompletionField, $scheduleCompletionField, $surveyStatusField)
  {
    $setup_records = $this->getRecordsWithSetup($project_id, $setupCompletionField, $surveyStatusField);
    $scheduled_records = $this->getRecordsWithSchedule($project_id, $scheduleCompletionField);

    $records = array_diff($setup_records, $scheduled_records);

    return $records;
  }

  /*
    Returns a simple array of records that have a completed Survey Setup instrument
  */
  function getRecordsWithSetup($project_id, $setupCompletionField, $surveyStatusField)
  {
    $filter = "[$this->setupEvent][$setupCompletionField] = '2' AND [$this->setupEvent][$surveyStatusField] = '1'";
    $params = array(
      'project_id' => $project_id,
      'return_format' => 'array',
      'fields' => array('record_id', $setupCompletionField, $surveyStatusField),
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
  function getRecordsWithSchedule($project_id, $scheduleCompletionField)
  {
    $filter = "[$this->firstDayEvent][$scheduleCompletionField] = '0' OR [$this->firstDayEvent][$scheduleCompletionField] = '1' OR [$this->firstDayEvent][$scheduleCompletionField] = '2'";
    $params = array(
      'project_id' => $project_id,
      'return_format' => 'array',
      'fields' => array('record_id', $scheduleCompletionField),
      'filterLogic' => $filter
    );
    $data = \REDCap::getData($params);

    $this->debug_to_console($data, "getRecordsWithSchedule data");

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

  function generateRandomTime($startTime, $endTime, $endBuffer)
  {

    if (!$endBuffer) {
      $endBuffer = 0;
    }

    $startTimestamp = strtotime($startTime);
    $endTimestamp = strtotime($endTime);
    $actualEndTime = $endTimestamp - ((int)$endBuffer * 60);

    $randomTimestamp = mt_rand($startTimestamp, $actualEndTime);

    return date('H:i', $randomTimestamp);
  }

  function saveToRedcap($project_id, $dataToSave)
  {
    $params = array(
      'project_id' => $project_id,
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
  function cronStarter() //$cronInfo)
  {
    foreach ($this->getProjectsWithModuleEnabled() as $localProjectId) {
      $this->setProjectId($localProjectId);

      // Project specific method calls go here.
      $cronStartTime = strtotime($this->getProjectSetting('cron-start-time', $localProjectId));
      $cronEndTime = strtotime($this->getProjectSetting('cron-end-time', $localProjectId));
      $sendTimeFields = $this->getProjectSetting('send-time', $localProjectId);
      $sendFlagFields = $this->getProjectSetting('send-flag', $localProjectId);
      $expireTimeFields = $this->getProjectSetting('expire-time', $localProjectId);
      $expireFlagFields = $this->getProjectSetting('expire-flag', $localProjectId);
      $surveyCompleteFields = $this->getProjectSetting('survey-complete', $localProjectId);

      // $this->debug_to_console($localProjectId, "cron localProjectId");
      // $this->debug_to_console($surveyCompleteFields, "cron surveyCompleteFields");

      $currentTime = time();
      $log = [];

      // run cron from start time to 5 min after cron end time (+ 300 sec)
      // this will make sure all surveys are expired at the end of the day
      if ($currentTime >= $cronStartTime && $currentTime <= $cronEndTime + 300) {
        $response = $this->surveyScheduleChecker($localProjectId, $sendTimeFields, $sendFlagFields, $expireTimeFields, $expireFlagFields, $surveyCompleteFields);

        array_push($log, "$localProjectId cron ran with response: $response. \n");
      } else {
        array_push($log, "Outside of set hours for $localProjectId. \n");
      }
    }

    $logText = implode($log);

    // return "The \"{$cronInfo['cron_description']}\" cron job completed with the following log: $logText";
    return "The cron job completed with the following log: $logText";
  }

  function surveyScheduleChecker($project_id, $sendTimeFields, $sendFlagFields, $expireTimeFields, $expireFlagFields, $surveyCompleteFields)
  {

    // $this->debug_to_console($project_id, "project_id");
    // $this->debug_to_console($surveyCompleteFields, "surveyCompleteFields");

    $todaysRecords = $this->getTodaysRecords($project_id, $this->sendDateField, $sendTimeFields, $sendFlagFields, $expireTimeFields, $expireFlagFields, $surveyCompleteFields);

    // $this->debug_to_console($todaysRecords, "todaysRecords");

    $dataToSave = [];
    foreach ($todaysRecords as $recordKey => $record) {
      $dataToSave[$recordKey] = [];
      foreach ($record as $eventKey => $event) {
        $dataToSave[$recordKey][$eventKey] = [];
        for ($currentSurvey = 0; $currentSurvey < count($sendTimeFields); $currentSurvey++) {
          $currentSendTime = $event[$sendTimeFields[$currentSurvey]];
          $currentExpireTime = $event[$expireTimeFields[$currentSurvey]];

          $surveyComplete = $this->isSurveyComplete($event, $surveyCompleteFields[$currentSurvey]);

          // $this->debug_to_console($surveyCompleteFields[$currentSurvey], "current_survey");
          // $this->debug_to_console($surveyComplete, "isSurveyComplete");

          // send surveys that have hit time and haven't been sent yet
          if ($this->isBeforeNow($currentSendTime) && $event[$sendTimeFields[$currentSurvey]] != 1) {
            $dataToSave[$recordKey][$eventKey][$sendFlagFields[$currentSurvey]] = 1;
          }

          // has survey been completed before expiration? mark that
          if ($this->isBeforeNow($currentExpireTime) && $surveyComplete) {
            $dataToSave[$recordKey][$eventKey][$expireFlagFields[$currentSurvey]] = 2;
          }

          // has survey reached expiration time before completion? mark that
          if ($this->isBeforeNow($currentExpireTime) && !$surveyComplete) {
            $dataToSave[$recordKey][$eventKey][$expireFlagFields[$currentSurvey]] = 1;
          }

          if ($currentSurvey == count($sendTimeFields) - 1 && $dataToSave[$recordKey][$eventKey][$expireFlagFields[$currentSurvey]] != 0) {
            // last survey has been flagged expired or completed, flag entire instrument for this event complete
            $dataToSave[$recordKey][$eventKey][$this->scheduleCompletionField] = 2;
          }
        }
      }
    }

    $response = $this->saveToRedcap($project_id, $dataToSave);
    $responseText = implode($response);

    return $responseText;
  }

  function getTodaysRecords($project_id, $sendDateField, $sendTimeFields, $sendFlagFields, $expireTimeFields, $expireFlagFields, $surveyCompleteFields)
  {
    $todaysDate = date("Y-m-d");

    $filter = "[$sendDateField] = '$todaysDate'";

    $fields = array('record_id');

    foreach ($sendTimeFields as $currentField) {
      array_push($fields, $currentField);
    }

    foreach ($sendFlagFields as $currentField) {
      array_push($fields, $currentField);
    }

    foreach ($expireTimeFields as $currentField) {
      array_push($fields, $currentField);
    }

    foreach ($expireFlagFields as $currentField) {
      array_push($fields, $currentField);
    }

    // surveyCompleteFields are sometimes array of arrays, as multiple fields can be selected/configured
    foreach ($surveyCompleteFields as $currentArray) {

      // $this->debug_to_console(is_array($currentArray), "is_array");
      // if it is an array of arrays, process accordingly
      if (is_array($currentArray)) {
        foreach ($currentArray as $currentField) {
          array_push($fields, $currentField);
        }
      } else {
        //it's a normal array, no need to go in another level
        array_push($fields, $currentArray);
      }
    }

    $params = array(
      'project_id' => $project_id,
      'return_format' => 'array',
      'fields' => $fields,
      'filterLogic' => $filter
    );
    $data = \REDCap::getData($params);

    return $data;
  }

  function isBeforeNow($inputTime)
  {
    $currentTime = time();
    $inputTime = strtotime($inputTime);

    if ($inputTime <= $currentTime) {
      return true;
    }

    return false;
  }

  function isSurveyComplete($eventData, $currentSurveyCompleteFields)
  {

    // $this->debug_to_console($currentSurveyCompleteFields, "current survey complete fields in isSurveyComplete");
    // $this->debug_to_console(is_array($currentSurveyCompleteFields), "Are there multiple survey sets");

    // if multiple survey completion fields, you get an array of completion fields, so process accordingly
    if (is_array($currentSurveyCompleteFields)) {
      foreach ($currentSurveyCompleteFields as $currentField) {

        // $this->debug_to_console($currentField, "currently checking");
        // $this->debug_to_console($eventData[$currentField], "status");

        if ($eventData[$currentField] == 2) {
          return true;
        }
      }
    } else {
      // only one survey completion field - not an array
      if ($eventData[$currentSurveyCompleteFields] == 2) {
        return true;
      }
    }

    return false;
  }

  function debug_to_console($data, $text = 'Debug Object')
  {
    $output = json_encode($data);
    if (is_array($output))
      $output = implode(',', $output);

    echo "<script>console.log('" . $text . ": " . $output . "' );</script>";
  }
}
