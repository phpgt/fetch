<?php
namespace Gt\Fetch;

use Gt\Async\GlobalLoop;
use Gt\Async\Loop;
use Gt\Curl\CurlHttpClient;
use Gt\Curl\CurlOptions;
use Gt\Http\Header\HeaderLine;
use Gt\Http\Header\RequestHeaders;
use Gt\Http\Request;
use Gt\Http\RequestMethod;
use Gt\Http\Uri;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise as HttpPromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Http {
	const USER_AGENT = "PhpGt/Fetch";

	protected HttpAsyncClient $client;
	private Loop $loop;

	public function __construct(
		HttpAsyncClient $client = null,
		Loop $loop = null
	) {
		if($client) {
			$this->client = $client;
		}
		else {
			$this->client = new CurlHttpClient();
		}

		$this->loop = $loop ?? GlobalLoop::get();
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

		$curlOpts = new CurlOptions();
		$request = $this->initRequest($request, $init, $curlOpts);

		$this->client->pushCurlOptions($curlOpts);
		return $this->client->sendAsyncRequest($request);
	}

	private function initRequest(
		RequestInterface $request,
		array $init,
		CurlOptions $curlOpts
	):RequestInterface {
		if(isset($init["method"])) {
			$request = $request->withMethod(strtoupper($init["method"]));
		}

		if(isset($init["headers"])) {
			if(is_array($init["headers"])) {
				$headers = new RequestHeaders($init["headers"]);
			}
			else {
				$headers = $init["headers"];
			}

			foreach($headers as $headerLine) {
				/** @var HeaderLine $headerLine */
				$request = $request->withHeader(
					$headerLine->getName(),
// TODO: Unit test required to prove an individual or multiple header is set correctly.
					$headerLine->getValues()
				);
			}
		}

		if(isset($init["body"])) {
// TODO: Issue Http#40 - body might be many types (FormData, UrlSearchParams, etc.)
			$request = $request->withBody($init["body"]);
		}

		if(isset($init["cache"])) {
			throw new FetchException("Cache mode is not yet implemented. See issue #91");
		}

		if(isset($init["redirect"])) {
			if($init["redirect"] === "follow") {
				$curlOpts->set(CURLOPT_FOLLOWLOCATION, true);
			}
			elseif($init["redirect"] === "manual") {
				$curlOpts->set(CURLOPT_FOLLOWLOCATION, false);
			}
		}

		if(isset($init["referrer"])) {
// The typo here ("Referer") was made in 1991 when the HTTP protocol was designed.
// Please direct any complaints to Sir Tim Berners-Lee.
			$request = $request->withHeader("Referer", $init["referrer"]);
		}

		if(isset($init["integrity"])) {
			throw new FetchException("Subresource integrity is not yet implemented. See issue #92");
		}

		if(isset($init["signal"])) {
			throw new FetchException("AbortSignal is not yet implemented. See issue #93");
		}

// TODO: Should the following init parameters be respected on the server-side?
// Tracked in issue #90
		foreach(["mode", "credentials", "referrerPolicy", "keepalive"] as $nonServerSideInit) {
			if(isset($init[$nonServerSideInit])) {
				throw new FetchException("The init parameter '$nonServerSideInit' is not compatible in a server-side context. See issue #90");
			}
		}

		return $request;
	}

	/**
	 *
	 */
	public function await():void {
		$loop = $this->loop;
		$loop->haltWhenAllDeferredComplete();

		foreach($this->client->getDeferredList() as $deferred) {
			$loop->addDeferredToTimer($deferred);
		}

		$loop->run(true);
	}
}