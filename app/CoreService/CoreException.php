<?php

namespace App\CoreService;

use Exception;

class CoreException extends Exception
{

    private $errorMessage;
    private $errorList;
    private $statusCode;

    public function __construct($errorMessage = "", $statusCode = 422, $errorList = [])
    {
        $this->errorMessage = $errorMessage;
        $this->errorList = $errorList;
        $this->statusCode = $statusCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function getErrorCode()
    {
        return $this->statusCode;
    }

    public function getErrorList()
    {
        return $this->errorList;
    }
}
