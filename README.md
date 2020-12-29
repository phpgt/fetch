# Asynchronous HTTP client with promises.

Asynchronous HTTP client, [PSR-7 compatible][psr-7] implementation of the [Fetch Standard][fetch-standard] which defines requests, responses, and the process that binds them: _fetching_.

This repository is compatible with [PSR-18 HTTP Clients][psr-18] for standardised HTTP interoperability.

See also, the [JavaScript implementation][fetch-js] that ships as standard in all modern browsers.

***

<a href="https://github.com/PhpGt/Fetch/actions" target="_blank">
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
$http = new Gt\Fetch\Http();

// Rather than creating the request now, `fetch` returns a Promise, 
// for later resolution with the BodyResponse.
$http->fetch("http://example.com/api/something.json")
->then(function(BodyResponse $response) {
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
->then(function(Json $json) {
// The second Promise resolves once the whole body has completed downloading.
    echo "Got JSON result length "
    	. count($json->results)
    	. PHP_EOL;

// Notice that type-safe getters are available on all Json objects:
    echo "Name of first result: "
    	. $json->results[0]->getString("name")
    	. PHP_EOL;
});

// A third request is made here to show a different type of body response:
$http->fetch("http://example.com/something.jpg")
->then(function(BodyResponse $response) {
    return $response->blob();
})
->then(function($blob) {
    echo "Got JPG blob. Saving file." . PHP_EOL;
    file_put_contents("/tmp/something.jpg", $blob);
});

// Once all Promises are registered, all HTTP requests can be initiated in
// parallel, with the callback function triggered when they are all complete. 
$http->all()->then(function() {
    echo "All HTTP calls have completed!" . PHP_EOL;
});
```

## Example usage: PSR-18 compatibility

Fetch does not implement a [PSR-18 compatible HTTP client][psr-18], but instead it allows a PSR-18 HTTP client to be injected as a dependency. 

This is useful for projects that already use a specific HTTP client, so that there needs no more HTTP implementations used in your project than are already present.

```php
<?php
$httpClient = new \ThirdParty\Http\Client();
$http = new Gt\Fetch\Http($httpClient);

$http->fetch("http://example.com/api/something.json")
->then(function(BodyResponse $response) {
	return $response->json();
})
->then(function(Json $json) {
	echo $json->name;
});

``` 

For more extensive examples, check out the code in the [example directory](/example).

[psr-7]: http://www.php-fig.org/psr/psr-7/
[psr-18]: http://www.php-fig.org/psr/psr-18/
[fetch-standard]: https://fetch.spec.whatwg.org/
[fetch-js]: https://developer.mozilla.org/en/docs/Web/API/Fetch_API