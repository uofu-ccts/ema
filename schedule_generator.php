<?php

use RCView;

echo RCView::h4([], "Schedule Generator");

$setupCompletionField = implode($module->getProjectSetting('setup-completion', $project_id));
$scheduleCompletionField = implode($module->getProjectSetting('schedule-completion', $project_id));
$sendTimeFields = $module->getProjectSetting('send-time', $project_id);
$scheduleFields = $module->getProjectSetting('schedule-field', $project_id);

$module->debug_to_console($setupCompletionField);

$setup_records = $module->getRecordsWithSetup($project_id, $setupCompletionField);
$scheduled_records = $module->getRecordsWithSchedule($project_id, $scheduleCompletionField);

$module->debug_to_console($setup_records, "Setup");
$module->debug_to_console($scheduled_records, "Schedule");

$records = $module->getRecordsToSchedule($project_id, $setupCompletionField, $scheduleCompletionField);

$module->debug_to_console($records, "Records");

foreach ($records as $record) {
  print_r($record);
}

print_r($records);