<?php

/*
 * Builds a complete REDCap UI around the plugin.
 */
$HtmlPage = new HtmlPage();
$HtmlPage->ProjectHeader();

use RCView;

echo RCView::h3([], "Schedule Generator");

$module->getFieldNames($module->project_id);

$records = $module->getRecordsToSchedule($module->project_id, 
                                          $module->setupCompletionField, 
                                          $module->scheduleCompletionField, 
                                          $module->surveyStatusField);

$html = "<h5>Schedules were created for the following records.</h5><br>";

print_r($html);

$dataToSave = $module->generateSchedules($records, 
                                          $module->project_id, 
                                          $module->surveyStartField, 
                                          $module->surveyDurationField, 
                                          $module->startRangeFields, 
                                          $module->expireRangeFields, 
                                          $module->sendDateField, 
                                          $module->sendTimeFields, 
                                          $module->sendFlagFields, 
                                          $module->expireTimeFields, 
                                          $module->expireFlagFields,
                                          $module->errorLog
                                        );

$response = $module->saveSchedules($dataToSave);

print_r($response);


