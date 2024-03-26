<?php

use RCView;

echo RCView::h3([], "Manually running survey schedule checker...");

$module->getFieldNames($module->project_id);

$module->cronStarter();

$html = "Something went wrong. Please refresh the page.";
