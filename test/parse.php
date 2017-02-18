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
// Some basic tests of the parsing functions.  Running this file should
// produce empty output.
//
// ===========================================================================

include 'exceptions.php';
include 'parse.php';

// ---------------------------------------------------------------------------
assert(parse::optional([], 'a') === null);
assert(parse::optional(['a' => 1], 'a') === 1);
assert(parse::optional(['a' => 1], 'b') === null);
assert(parse::optional(['a' => ['b' => 'c']], 'a') === ['b' => 'c']);

// ---------------------------------------------------------------------------
try { parse::required([], 'a'); assert(false); }catch(Missing $ex){}
assert(parse::required(['a' => 1], 'a') === 1);
try { parse::required(['a' => 1], 'b'); assert(false); }catch(Missing $ex){}
assert(parse::required(['a' => ['b' => 'c']], 'a') === ['b' => 'c']);

// ---------------------------------------------------------------------------
assert(parse::as_boolean(true)); // returns true
assert(parse::as_boolean(false) === false); // returns false
assert(parse::as_boolean(null, parse::NullOk) === null); // returns null
try { parse::as_boolean(123); assert(false); }catch(InvalidValue $ex){}
try { parse::as_boolean(null); assert(false); }catch(InvalidValue $ex){}
try { parse::as_boolean('hello'); assert(false); }catch(InvalidValue $ex){}
assert(parse::get_boolean([ 'a' => true ], 'a')); // returns true
assert(parse::get_boolean([ 'a' => false ], 'a') === false); // returns false
assert(parse::optional_boolean([ 'a' => true ], 'a'));
assert(parse::optional_boolean([ 'a' => false ], 'b') === null);

// ---------------------------------------------------------------------------
assert(parse::as_string('hello') === 'hello');
assert(parse::as_string(null, parse::NullOk) === null);
try { parse::as_string(123); assert(false); }catch(InvalidValue $ex){}
try { parse::as_string(null); assert(false); }catch(InvalidValue $ex){}
try { parse::as_string(true); assert(false); }catch(InvalidValue $ex){}
assert(parse::get_string([ 'a' => 'x' ], 'a') === 'x');
assert(parse::get_string([ 'a' => 'y' ], 'a') === 'y');
assert(parse::optional_string([ 'a' => 'x' ], 'a') === 'x');
assert(parse::optional_string([ 'a' => 'y' ], 'b') === null);

// ---------------------------------------------------------------------------
assert(parse::as_decimal('123') === '123');
assert(parse::as_decimal('123.45') === '123.45');
assert(parse::as_decimal(null, parse::NullOk) === null);
try { parse::as_decimal(123); assert(false); }catch(InvalidValue $ex){}
try { parse::as_decimal(123.45); assert(false); }catch(InvalidValue $ex){}
try { parse::as_decimal(null); assert(false); }catch(InvalidValue $ex){}
try { parse::as_decimal(true); assert(false); }catch(InvalidValue $ex){}
try { parse::as_decimal('hello'); assert(false); }catch(InvalidValue $ex){}
assert(parse::get_decimal([ 'a' => '123.45' ], 'a') === '123.45');
assert(parse::get_decimal([ 'a' => '234.56' ], 'a') === '234.56');
assert(parse::optional_decimal([ 'a' => '123.45' ], 'a') === '123.45');
assert(parse::optional_decimal([ 'a' => '234.56' ], 'b') === null);

// ---------------------------------------------------------------------------
assert(parse::as_number('123') === '123');
assert(parse::as_number(null, parse::NullOk) === null);
try { parse::as_number(123); assert(false); }catch(InvalidValue $ex){}
try { parse::as_number(null); assert(false); }catch(InvalidValue $ex){}
try { parse::as_number(true); assert(false); }catch(InvalidValue $ex){}
try { parse::as_number('hello'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_number('123.45'); assert(false); }catch(InvalidValue $ex){}
assert(parse::get_number([ 'a' => '123' ], 'a') === '123');
assert(parse::get_number([ 'a' => '234' ], 'a') === '234');
assert(parse::optional_number([ 'a' => '123' ], 'a') === '123');
assert(parse::optional_number([ 'a' => 'y' ], 'b') === null);

// ---------------------------------------------------------------------------
$url1 = 'http://example.com/';
$url2 = 'http://example.com/another';
assert(parse::as_url($url1) === $url1);
assert(parse::as_url(null, parse::NullOk) === null);
assert(parse::as_url('http://example.com') === 'http://example.com');
assert(parse::as_url('https://example.com') === 'https://example.com');
try { parse::as_url(123); assert(false); }catch(InvalidValue $ex){}
try { parse::as_url(null); assert(false); }catch(InvalidValue $ex){}
try { parse::as_url(true); assert(false); }catch(InvalidValue $ex){}
try { parse::as_url('hello'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_url('123.45'); assert(false); }catch(InvalidValue $ex){}
assert(parse::get_url([ 'a' => $url1 ], 'a') === $url1);
assert(parse::get_url([ 'a' => $url2 ], 'a') === $url2);
assert(parse::optional_url([ 'a' => $url1 ], 'a') === $url1);
assert(parse::optional_url([ 'a' => $url2 ], 'b') === null);

// ---------------------------------------------------------------------------
assert(parse::as_country('CA') === 'CA');
assert(parse::as_country(null, parse::NullOk) === null);
try { parse::as_country(123); assert(false); }catch(InvalidValue $ex){}
try { parse::as_country(null); assert(false); }catch(InvalidValue $ex){}
try { parse::as_country(true); assert(false); }catch(InvalidValue $ex){}
try { parse::as_country('hello'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_country('123.45'); assert(false); }catch(InvalidValue $ex){}
assert(parse::get_country([ 'a' => 'CA' ], 'a') === 'CA');
assert(parse::get_country([ 'a' => 'US' ], 'a') === 'US');
assert(parse::optional_country([ 'a' => 'CA' ], 'a') === 'CA');
assert(parse::optional_country([ 'a' => 'US' ], 'b') === null);

// ---------------------------------------------------------------------------
$uuid1 = '1b3957e8-1c8f-4af5-8517-94bc8cda8595';
$uuid2 = '00000000-DEAD-BEEF-0000-000000000000';
$badUuid = '00000000-WHAT-ELSE-0000-000000000000';
assert(parse::as_uuid($uuid1) === $uuid1);
assert(parse::as_uuid(null, parse::NullOk) === null);
try { parse::as_uuid(123); assert(false); }catch(InvalidValue $ex){}
try { parse::as_uuid(null); assert(false); }catch(InvalidValue $ex){}
try { parse::as_uuid(true); assert(false); }catch(InvalidValue $ex){}
try { parse::as_uuid('hello'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_uuid('123.45'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_uuid($badUuid); assert(false); }catch(InvalidValue $ex){}
assert(parse::get_uuid([ 'a' => $uuid1 ], 'a') === $uuid1);
assert(parse::get_uuid([ 'a' => $uuid2 ], 'a') === $uuid2);
assert(parse::optional_uuid([ 'a' => $uuid1 ], 'a') === $uuid1);
assert(parse::optional_uuid([ 'a' => $uuid2 ], 'b') === null);

// ---------------------------------------------------------------------------
assert(parse::as_date('2016-01-01') === '2016-01-01');
assert(parse::as_date(null, parse::NullOk) === null);
try { parse::as_date(123); assert(false); }catch(InvalidValue $ex){}
try { parse::as_date(null); assert(false); }catch(InvalidValue $ex){}
try { parse::as_date(true); assert(false); }catch(InvalidValue $ex){}
try { parse::as_date('hello'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_date('123.45'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_date('12-31-2016'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_date('2016-13-01'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_date('2016-1-1'); assert(false); }catch(InvalidValue $ex){}
assert(parse::get_date([ 'a' => '2016-01-01' ], 'a') === '2016-01-01');
assert(parse::get_date([ 'a' => '2016-12-31' ], 'a') === '2016-12-31');
assert(parse::optional_date([ 'a' => '2016-01-01' ], 'a') === '2016-01-01');
assert(parse::optional_date([ 'a' => '2016-12-31' ], 'b') === null);

// ---------------------------------------------------------------------------
assert(parse::as_currency('CAD') === 'CAD');
assert(parse::as_currency(null, parse::NullOk) === null);
try { parse::as_currency(123); assert(false); }catch(InvalidValue $ex){}
try { parse::as_currency(null); assert(false); }catch(InvalidValue $ex){}
try { parse::as_currency(true); assert(false); }catch(InvalidValue $ex){}
try { parse::as_currency('hello'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_currency('123.45'); assert(false); }catch(InvalidValue $ex){}
assert(parse::get_currency([ 'a' => 'CAD' ], 'a') === 'CAD');
assert(parse::get_currency([ 'a' => 'USD' ], 'a') === 'USD');
assert(parse::optional_currency([ 'a' => 'CAD' ], 'a') === 'CAD');
assert(parse::optional_currency([ 'a' => 'USD' ], 'b') === null);

// ---------------------------------------------------------------------------
$email1 = 'joe.shopper@example.com';
$email2 = 'test+extension@gmail.com';
assert(parse::as_email($email1) === $email1);
assert(parse::as_email(null, parse::NullOk) === null);
try { parse::as_email(123); assert(false); }catch(InvalidValue $ex){}
try { parse::as_email(null); assert(false); }catch(InvalidValue $ex){}
try { parse::as_email(true); assert(false); }catch(InvalidValue $ex){}
try { parse::as_email('hello'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_email('123.45'); assert(false); }catch(InvalidValue $ex){}
assert(parse::get_email([ 'a' => $email1 ], 'a') === $email1);
assert(parse::get_email([ 'a' => $email2 ], 'a') === $email2);
assert(parse::optional_email([ 'a' => $email1 ], 'a') === $email1);
assert(parse::optional_email([ 'a' => $email2 ], 'b') === null);

// ---------------------------------------------------------------------------
$ip1 = '1.2.3.4';
$ip2 = '2001:4860:4860::8888';
assert(parse::as_ip($ip1) === $ip1);
assert(parse::as_ip(null, parse::NullOk) === null);
try { parse::as_ip(123); assert(false); }catch(InvalidValue $ex){}
try { parse::as_ip(null); assert(false); }catch(InvalidValue $ex){}
try { parse::as_ip(true); assert(false); }catch(InvalidValue $ex){}
try { parse::as_ip('hello'); assert(false); }catch(InvalidValue $ex){}
try { parse::as_ip('123.45'); assert(false); }catch(InvalidValue $ex){}
assert(parse::get_ip([ 'a' => $ip1 ], 'a') === $ip1);
assert(parse::get_ip([ 'a' => $ip2 ], 'a') === $ip2);
assert(parse::optional_ip([ 'a' => $ip1 ], 'a') === $ip1);
assert(parse::optional_ip([ 'a' => $ip2 ], 'b') === null);

// ---------------------------------------------------------------------------

$array = ['a'=>false, 'b'=>true, 'c'=>'0', 'd'=>null];
$filtered = ['a'=>false, 'b'=>true, 'c'=>'0'];
assert(array_filter($array) !== $filtered);
assert(parse::filter($array) === $filtered);

// ==========================================================================
?>
