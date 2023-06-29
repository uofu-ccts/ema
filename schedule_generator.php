<?php

use RCView;

echo RCView::h3([], "Schedule Generator");

$module->getFieldNames($module->project_id);

$records = $module->getRecordsToSchedule($module->project_id, 
                                          $module->setupCompletionField, 
                                          $module->scheduleCompletionField, 
                                          $module->surveyStatusField);

$html = "Something went wrong. Please refresh the page.";
if (count($records) > 0) {
  $html = "<h5>List of records that need to be scheduled</h5>";
  $table = '<form method="post">
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">Record ID</th>
                    <th scope="col"></th>
                  </tr>
                <thead>
                <tbody>
            ';
  $record_number = 1;
  foreach ($records as $record) {
    $row = "<tr>
              <th scope='row'>$record_number</th>
              <td>$record</td>
              <td><button type='button' class='btn btn-primary' id=$record>Generate schedule for this record</button></td>
            </tr>
            ";
    $table = $table . $row;
    $record_number += 1;
  }
  $table = $table . 
            "</tbody>
            </table>
            <input type='submit' name='allRecords' class='btn btn-success' value='allRecords' />
              <button type='button' class='btn btn-success' id='allRecords'>Generate schedule for all records</button>
            </input>
            </form>
            ";
  $html = $html . $table;
} else {
  $html = "<p>There are no records that need to be scheduled at this time.<p>";
}


// $dataToSave = $module->generateSchedules($records, 
//                                           $module->project_id, 
//                                           $module->surveyStartField, 
//                                           $module->surveyDurationField, 
//                                           $module->startRangeFields, 
//                                           $module->expireRangeFields, 
//                                           $module->sendDateField, 
//                                           $module->sendTimeFields, 
//                                           $module->sendFlagFields, 
//                                           $module->expireTimeFields, 
//                                           $module->expireFlagFields,
//                                           $module->errorLog
//                                         );

// $response = $module->saveSchedules($dataToSave);

// print_r($response);

print_r($html);

if(array_key_exists('button1', $_POST)) {
  button1();
}
else if(array_key_exists('allRecords', $_POST)) {
  button2();
}
function button1() {
  echo "This is Button1 that is selected";
}
function button2() {
  echo "This is Button2 that is selected";
}