<?php

use RCView;

echo RCView::h4([], "Schedule Generator");

$module->getFieldNames($module->project_id);

$records = $module->getRecordsToSchedule($module->project_id, 
                                          $module->setupCompletionField, 
                                          $module->scheduleCompletionField, 
                                          $module->surveyStatusField);


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

?>

<button type="button" class="btn btn-primary">Primary</button>