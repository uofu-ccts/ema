<?php

use RCView;

echo RCView::h4([], "Schedule Generator");

$arrProjectEventNames = REDCap::getEventNames(TRUE);

$module->debug_to_console($arrProjectEventNames);

$module->debug_show($arrProjectEventNames);