<?php

namespace BigSheetImporter\Exceptions;

class InvalidSheetFormat extends \Exception
{
    public function __construct($message = "Invalid sheet format", $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
