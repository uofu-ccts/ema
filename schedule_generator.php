<?php

use RCView;

echo RCView::h4([], "Schedule Generator");


$redcap_data = \REDCap::getData('json');

$module->debug_to_console($redcap_data, "REDCap data");

//try generating random time

$randomTime = $module->generateRandomTime(9, 180);

echo RCView::p([], $randomTime);