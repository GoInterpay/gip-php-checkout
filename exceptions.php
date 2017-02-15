<?php namespace GoInterpay;
// ===========================================================================
// Copyright 2016-2017 GoInterpay
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//    http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
// ===========================================================================
//
// ---------------------------------------------------------------------------
// A simple base exception for anything thrown directly by the GoInterpay
// SDK.  This exception may be used to catch all errors from this SDK.
//
class Exception extends \Exception {}

// ---------------------------------------------------------------------------
// A simple exception class for invalid values used in API calls.  This will
// be thrown from any API call if the specified value is syntactically
// invalid.
//
class InvalidValue extends Exception {
  function __construct($value, $text){
    parent::__construct('Invalid Value: [' .
                        (is_null($value) ? '<null>' : $value) .
                        '] is not a valid ' . $text);
  }
}

// ---------------------------------------------------------------------------
// A simple exception class for missing values used in API calls.  This will
// be thrown from any API call if a required value is missing.
//
class Missing extends Exception {
  function __construct($text){
    parent::__construct('Missing Value: ' . $text . ' is required');
  }
}

// ==========================================================================
?>
