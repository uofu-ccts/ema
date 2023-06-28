<?php

use RCView;

echo RCView::h4([], "Schedule Generator");

$errorLog = [];

$setupCompletionField = implode($module->getProjectSetting('setup-completion', $project_id));
$surveyStartField = implode($module->getProjectSetting('start-date', $project_id));
$surveyStatusField = implode($module->getProjectSetting('status', $project_id));
$surveyDurationField = implode($module->getProjectSetting('num-days', $project_id));
$scheduleCompletionField = implode($module->getProjectSetting('schedule-completion', $project_id));
$sendDateField = implode($module->getProjectSetting('send-date', $project_id));
$sendTimeFields = $module->getProjectSetting('send-time', $project_id)[0];
$sendFlagFields = $module->getProjectSetting('send-flag', $project_id)[0];
$startRangeFields = $module->getProjectSetting('start-range', $project_id)[0];
$expireRangeFields = $module->getProjectSetting('expire-range', $project_id)[0];
$expireTimeFields = $module->getProjectSetting('expire-time', $project_id)[0];
$expireFlagFields = $module->getProjectSetting('expire-flag', $project_id)[0];

$records = $module->getRecordsToSchedule($project_id, $setupCompletionField, $scheduleCompletionField, $surveyStatusField);

/* $redcap_data is structured as:
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
$dataToSave = [];
foreach ($records as $record) {

  $dateParams = $module->getDateParams($project_id, $record, $surveyStartField, $surveyDurationField);

  if (!$dateParams[$surveyDurationField]) {
    array_push($errorLog, "$surveyDurationField is missing for record $record. Moving on to next record...");
    continue;
  }

  if (!$dateParams[$surveyStartField]) {
    array_push($errorLog, "$surveyStartField is missing for record $record. Moving on to next record...");
    continue;
  }

  $startParams = $module->getTimeParams($project_id, $record, $startRangeFields);

  if (count($startParams) != count($startRangeFields)) {
    $errorText = implode($startRangeFields);
    array_push($errorLog, "One of the $errorText is missing for record $record. Moving on to next record...");
    continue;
  }

  $expireParams = $module->getTimeParams($project_id, $record, $expireRangeFields);

  if (count($expireParams) != count($expireRangeFields)) {
    $errorText = implode($expireRangeFields);
    array_push($errorLog, "One of the $errorText is missing for record $record. Moving on to next record...");
    continue;
  }

  $numDays = $dateParams[$surveyDurationField];
  $startDate = new DateTimeImmutable($dateParams[$surveyStartField]);

  print_r('Current record: ' . $record . '<br>');

  $dataToSave[$record] = [];

  for ( $currentDay=1; $currentDay <= $numDays; $currentDay++ ) {

    $currentRedcapEvent = 'day_' . $currentDay . '_arm_1';
    $currentSurveyDate = $startDate->add(new DateInterval('P' . $currentDay-1 . 'D'))->format('Y-m-d');

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

      $sendTime = $module->generateRandomTime($startTime, $expireTime);

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

$params = array(
  'dataFormat' => 'array',
  'data' => $dataToSave,
  'overwriteBehavior' => 'normal',
  'dateFormat' => 'YMD'
);

$response = \REDCap::saveData($params);

print_r($response);