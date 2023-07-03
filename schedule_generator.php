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
  $table = '
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">Record ID</th>
                    <!--<th scope="col"></th>-->
                  </tr>
                <thead>
                <tbody>
            ';
  $record_number = 1;
  foreach ($records as $record) {
    $row = "<tr>
              <th scope='row'>$record_number</th>
              <td>$record</td>
              <!--<td><button type='input' class='btn btn-primary' id=$record>Generate schedule for this record</button></td>-->
            </tr>
            ";
    $table = $table . $row;
    $record_number += 1;
  }
  $table = $table . 
            "</tbody>
            </table>
            <a href='" . $module->generatorUrl . "'>
              <button type='submit' class='btn btn-success' id='allRecords' onclick='createSchedule()'>Generate schedule for all records</button>
            </a>
            ";
  $html = $html . $table;
} else {
  $html = "<p>There are no records that need to be scheduled at this time.<p>";
}

print_r($html);