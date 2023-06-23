<?php

use RCView;

echo RCView::h4([], "Schedule Generator");

$module->getSurveyStartSettings();

$module->debug_to_console($module->surveyStartFields, "Fields to get");

$event_ids = \REDCap::getEventNames(TRUE);

$module->debug_to_console($event_ids, "Event IDs");

$redcap_data = $module->getSurveyStartData();
$redcap_data2 = $module->getSurveyScheduleData();

//try generating random time

$randomTime = $module->generateRandomTime(9, 180);

echo RCView::p([], $randomTime);

$module->debug_to_console($redcap_data, "Start data");
$module->debug_to_console(\REDCap::getData(), "All Data");

$setup_event_id = REDCap::getEventIdFromUniqueEvent("event_1_arm_1");

print_r($setup_event_id);