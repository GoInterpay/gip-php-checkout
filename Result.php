<?php namespace GoInterpay;
// ===========================================================================
// Copyright 2016 GoInterpay
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
// A simple class to represent the result of an interaction with the API.  In
// general, an interaction is successful if the httpCode is 200, there is no
// error, and there is a result.
//
class Result {
  function __construct($httpCode, $error, $message, $result = null){
    $this->httpCode = $httpCode;
    $this->error = $error;
    $this->message = $message;
    $this->result = $result;
  }

  // The HTTP status code that was returned, or null if that didn't happen.
  public $httpCode;

  // The API error code returned, or null if none.
  public $error;

  // The API error message returned, or null if none.
  public $message;

  // The API response (with the error extracted), or null.
  public $result;
}

// ==========================================================================
?>
