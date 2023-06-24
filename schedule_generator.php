<?php

use RCView;

echo RCView::h4([], "Schedule Generator");

$setupCompletionField = implode($module->getProjectSetting('setup-completion', $project_id));
$surveyStartField = implode($module->getProjectSetting('start-date', $project_id));
$surveyStatusField = implode($module->getProjectSetting('status', $project_id));
$surveyDurationField = implode($module->getProjectSetting('num-days', $project_id));
$scheduleCompletionField = implode($module->getProjectSetting('schedule-completion', $project_id));
$sendDateField = implode($module->getProjectSetting('send-date', $project_id));
$sendTimeFields = $module->getProjectSetting('send-time', $project_id)[0];
$sendFlagFields = $module->getProjectSetting('send-flag', $project_id)[0];
$expireRangeFields = $module->getProjectSetting('expire-range', $project_id)[0];
$expireTimeFields = $module->getProjectSetting('expire-time', $project_id)[0];
$expireFlagFields = $module->getProjectSetting('expire-flag', $project_id)[0];

$module->debug_to_console($setupCompletionField, "setup-completion");
$module->debug_to_console($surveyStartField, "start-date");
$module->debug_to_console($surveyStatusField, "status");
$module->debug_to_console($surveyDurationField, "num-days");
$module->debug_to_console($scheduleCompletionField, "schedule-completion");
$module->debug_to_console($sendDateField, "send-date");
$module->debug_to_console($sendTimeFields, "send-time");
$module->debug_to_console($sendFlagFields, "send-flag");
$module->debug_to_console($expireRangeFields, "expire-range");
$module->debug_to_console($expireTimeFields, "expire-time");
$module->debug_to_console($expireFlagFields, "expire-flag");

$records = $module->getRecordsToSchedule($project_id, $setupCompletionField, $scheduleCompletionField, $surveyStatusField);

$module->debug_to_console($records, "Records");

foreach ($records as $record) {

  $scheduleParams = $module->getScheduleParams($project_id, $record, $surveyStartField, $surveyDurationField);

  $numDays = $scheduleParams['bi_survey_num_days'];
  $startDate = new DateTimeImmutable($scheduleParams['bi_survey_start_date']);

  print_r($startDate);
  print_r("<br>");

  for ( $currentDay=1; $currentDay<=$numDays; $currentDay++ ) {

    $currentRedcapEvent = 'day_' . $currentDay . '_arm_1';
    $currentSurveyDate = $startDate->add(new DateInterval('P' . $currentDay-1 . 'D'))->format('Y-m-d');

    print_r($currentRedcapEvent);
    print_r("<br>");
    print_r($currentSurveyDate);
    print_r("<br>");
  }
}