<?php

namespace Utah\EMA;

class EMA extends \ExternalModules\AbstractExternalModule
{
  
  public $project_id = null;
  public $username = USERID; // a REDCap constant; see redcap_info() output on the dev doc page

  // variables to store settings
  // this is where slider variables and settings are configured
  public $configuredVariables = [];

  public function __construct() {

    parent::__construct(); // call parent (AbstractExternalModule) constructor

    $this->project_id = $this->getProjectId(); // defined in AbstractExternalModule; will return project_id or null

    // access EM project settings and return array of variables set
    $this->configuredVariables = $this->getProjectSettings();

  }

  function generateRandomTime($startTime, $timeRange)
  {
    $todaysDateMDY = date("m/d/Y");

    $objDateTime = \DateTime::createFromFormat('m/d/Y', $todaysDateMDY);
    $objDateTime->setTime($startTime, 0);

    //generate random minutes, 0 - 180, 3 hour time block
    $intRangeMin = 0;
    $intRangeMax = $timeRange - 1;
    $randomNumber  = mt_rand($intRangeMin, $intRangeMax);

    $objDateTime->modify( "+{$randomNumber} minutes" );

    $randomTime  = $objDateTime->format('H:i'); 

    return $randomTime;
    
  } //end function: generateRandomTime

  function debug_to_console($data, $text='Debug Object',) {
    $output = json_encode($data);
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('" . $text . ": " . $output . "' );</script>";
  }
}