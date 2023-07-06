<?php

use RCView;

echo RCView::h4([], "Cron Tester");

$cronInfo = array( 
  "cron_name" => "surveyScheduleChecker",
  "cron_description" => "EMA survey time checker cron that runs every minute from 8AM to ",
  "method" => "some_other_method_3",
  "cron_hour" => 1,
  "cron_minute" => 15
);

$module->cron($cronInfo);
