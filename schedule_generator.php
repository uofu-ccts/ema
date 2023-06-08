<?php

use RCView;

echo RCView::h4([], "Schedule Generator");

//try generating random time

$randomTime = $module->generateRandomTime(9, 180);

echo RCView::p([], $randomTime);

$module->debug_to_console($module->configuredVariables, "Project variables");