<?php

namespace App\CoreService;

use App\CoreService\CoreException;
use App\CoreService\ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

abstract class CoreService implements DefaultService
{

    protected static $instances;

    abstract protected function prepare($input);
    abstract protected function process($input, $originalData);

    public function execute($input)
    {
        $originalInput = $input;
        $result = [];

        try {
            $validation = Validator::make($input, $this->validation());

            if ($validation->fails()) {
                throw new CoreException($validation->errors()->first());
            }

            $inputNew = $this->prepare($input);
            $result =  $this->process(is_array($inputNew) ? $inputNew : $input, $originalInput);
        } catch (CoreException $ex) {

            throw $ex;
        } catch (Exception $ex) {
            Log::debug($ex->getMessage());
            throw new CoreException($ex->getMessage());
        }

        return $result;
    }

    protected function validation()
    {
        return [];
    }
}
