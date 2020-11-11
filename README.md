# Asynchronous HTTP client with promises.

Asynchronous HTTP client, [PSR-7 compatible][psr-7] implementation of the [Fetch Standard][fetch-standard] which defines requests, responses, and the process that binds them: _fetching_.

This repository provides a [_PHP-HTTP Client Implementation_][php-http-client] for standardised HTTP interoperability.

See also, the [JavaScript implementation][fetch-js] that ships as standard in all modern browsers.

***

<a href="https://circleci.com/gh/PhpGt/Fetch" target="_blank">
    <img src="https://badge.status.php.gt/fetch-build.svg" alt="Build status" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Fetch" target="_blank">
    <img src="https://badge.status.php.gt/fetch-quality.svg" alt="Code quality" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Fetch" target="_blank">
    <img src="https://badge.status.php.gt/fetch-coverage.svg" alt="Code coverage" />
</a>
<a href="https://packagist.org/packages/PhpGt/Fetch" target="_blank">
    <img src="https://badge.status.php.gt/fetch-version.svg" alt="Current version" />
</a>
<a href="https://www.php.gt/fetch" target="_blank">
    <img src="https://badge.status.php.gt/fetch-docs.svg" alt="PHP.Gt/Fetch documentation" />
</a>

## Example usage: compute multiple HTTP requests in parallel, using `fetch`

```php
<?php
use function Gt\Fetch\fetch;

// Rather than creating the request now, `fetch` returns a Promise, 
// for later resolution with the BodyResponse.
fetch("http://example.com/api/something.json")
->then(function(Response $response) {
// The first Promise resolves as soon as a response is received, even before
// the body's content has completed downloading.
	if(!$response->ok) {
		echo "Looks like there was a problem. Status code: "
			. $response->getStatusCode() . PHP_EOL;
		return null;
	}

// Within this Promise callback, you have access to the body stream, but
// to access the contents of the whole body, return a new Promise here:
    return $response->json();
})
->then(function(object $json) {
// The second Promise resolves once the whole body has completed downloading.
    echo "Got JSON result length "
    	. count($json->results)
    	. PHP_EOL;

// Notice that type-safe getters are available on all Json objects:
    echo "Name of first result: "
    	. $json->results[0]->name
    	. PHP_EOL;
});

// A third request is made here to show a different type of body response:
fetch("http://example.com/something.jpg")
->then(function(Response $response) {
    return $response->blob();
})
->then(function(string $blob) {
    echo "Got JPG blob. Saving file." . PHP_EOL;
    file_put_contents("/tmp/something.jpg", $blob);
});

// Once all Promises are registered, all HTTP requests can be initiated in
// parallel, with the callback function triggered when they are all complete. 
fetchAwait()->then(function() {
    echo "All HTTP calls have completed!" . PHP_EOL;
});
```

## Example usage: HTTPlug PHP-HTTP Client & Asynchronous Client

```php
<?php
$http = new Gt\Fetch\HttpFetch();

$slowRequest = new Request("GET", "http://slow.example.com");
$fastRequest = new Request("GET", "http://fast.example.com");

// Send the slow request asynchronously (returns a Http\Promise)
$http->sendAsyncRequest($slowRequest)
->then(function(ResponseInterface $response) {
	echo $response->getBody();
});

// Perform fast request synchronously (block until response ready)
$response = $http->sendRequest($fastRequest);

// Wait for any asynchronous requests to be completed.
$http->await();
``` 

For more extensive examples, check out the code in the [example directory](/example).

[psr-7]: http://www.php-fig.org/psr/psr-7/
[fetch-standard]: https://fetch.spec.whatwg.org/
[fetch-js]: https://developer.mozilla.org/en/docs/Web/API/Fetch_API
[php-http-client]: http://docs.php-http.org/en/latest/index.html