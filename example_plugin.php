<?php

use RCView;

echo RCView::h4([], "Schedule Generator");


$redcap_data = \REDCap::getData('json');

$module->debug_to_console($redcap_data, "REDCap data");

//try generating random time

$randomTime = $module->generateRandomTime(9, 180);

echo RCView::p([], $randomTime);

// $module->setupProjectPage();

// $fields = [0 => '-- choose a field --'];
// // retrieve a list of field names for this project
// foreach($module->framework->getMetadata(PROJECT_ID) as $field_id => $data) {
//     $fields[$field_id] = $data['field_label'];
// }

// echo RCView::label(['for' => 'field'], "Select a field to change: ", false);
// echo RCView::select(['id' => 'field'], $fields);

// echo RCView::br();

// echo RCView::label(['for' => 'newValue'], "Value to fill in: ", false);
// echo RCView::input(['id' => 'newValue', 'type' => 'text', 'placeholder' => 'new value']);

// echo RCView::br();

// echo RCView::submit(['id' => 'submission', 'disabled' => True]);