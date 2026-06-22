<?php

namespace App\Services\Sample;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\Auth;

class SampleService extends CoreService
{

    public $transaction = false;
    public $task = null;

    public function prepare($input)
    {
        return $input;
    }

    public function process($input, $originalInput)
    {
        return [
            "me" => Auth::id()
        ];
    }

    protected function validation()
    {
        return [
            "aaa" => "integer"
        ];
    }
}
