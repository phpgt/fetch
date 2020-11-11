<?php
namespace Gt\Fetch;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Creates a new Deferred object to perform the resolution of the request and
 * returns a PSR-7 compatible promise that represents the result of the response
 *
 * @param string|UriInterface|RequestInterface $input
 * @param array $init
 */
function fetch($input, array $init = []):Promise {

}

function fetchAwait():Promise {

}