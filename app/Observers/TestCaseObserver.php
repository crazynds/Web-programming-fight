<?php

namespace App\Observers;

use App\Enums\TestCaseType;
use App\Models\TestCase;

class TestCaseObserver
{
    
    /**
     * Handle the File "deleting" event.
     */
    public function deleted(TestCase $testCase): void
    {
        if($testCase->type == TestCaseType::FileDiff){
            $testCase->inputfile?->delete();
            $testCase->outputfile?->delete();
        }
    }
}
