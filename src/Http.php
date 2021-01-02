<?php
namespace Gt\Fetch;

use Gt\Curl\CurlHttpClient;
use Gt\Http\Request;
use Gt\Http\RequestMethod;
use Gt\Http\Uri;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise as HttpPromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class Http {
	const USER_AGENT = "PhpGt/Fetch";

	protected HttpAsyncClient $client;

	public function __construct(
		HttpAsyncClient $client = null
	) {
		$this->client = $client ?? new CurlHttpClient();
	}

	/**
	 * Creates a new Deferred object to perform the resolution of the request and
	 * returns a PSR-7 compatible promise that represents the result of the response
	 *
	 * Long-hand for the GlobalFetchHelper get, head, post, etc.
	 *
	 * @param string|UriInterface|RequestInterface $resource
	 * @param array $init
	 */
	public function fetch($resource, array $init = []):HttpPromiseInterface {
		$request = null;

		if($resource instanceof RequestInterface) {
			$request = $resource;
		}
		else {
			$uri = null;

			if($resource instanceof UriInterface) {
				$uri = $resource;
			}
			else {
				$uri = new Uri($resource);
			}

			$request = new Request(
				RequestMethod::METHOD_GET,
				$uri
			);
		}

		// TODO: Process $init onto $request.

		$client = new CurlHttpClient();
		return $client->sendAsyncRequest($request);
	}

	// TODO: await stuff
}