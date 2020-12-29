<?php
namespace Gt\Fetch;

use Gt\Curl\Curl;
use Gt\Curl\CurlMulti;
use Gt\Fetch\Response\BodyResponse;
use Gt\Http\Uri;
use Http\Promise\Promise as HttpPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;

class Http {
	const USER_AGENT = "PhpGt/Fetch";

	/** @var float */
	protected $interval;
	/** @var RequestResolver */
	protected $requestResolver;
	/** @var array cURL options */
	protected $curlOptions = [
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_USERAGENT => self::USER_AGENT,
	];
	/** @var LoopInterface */
	protected $loop;
	/** @var TimerInterface */
	protected $timer;

	public function __construct(
		array $curlOptions = [],
		float $interval = 0.01,
		string $curlClass = Curl::class,
		string $curlMultiClass = CurlMulti::class
	) {
		$this->curlOptions = $curlOptions + $this->curlOptions;
		$this->interval = $interval;

		$this->loop = LoopFactory::create();
		$this->requestResolver = new RequestResolver(
			$this->loop,
			$curlClass,
			$curlMultiClass
		);
		$this->timer = $this->loop->addPeriodicTimer(
			$this->interval,
			[$this->requestResolver, "tick"]
		);
	}

	/**
	 * Creates a new Deferred object to perform the resolution of the request and
	 * returns a PSR-7 compatible promise that represents the result of the response
	 *
	 * Long-hand for the GlobalFetchHelper get, head, post, etc.
	 *
	 * @param string|UriInterface|RequestInterface $input
	 * @param array $init
	 */
	public function fetch($input, array $init = []):HttpPromise {
		$deferred = new Deferred();
		$deferredPromise = $deferred->promise();

		$uri = $this->ensureUriInterface($input);

		$curlOptBuilder = new CurlOptBuilder($input, $init);
		$curlOptArray = $this->curlOptions;

		foreach($curlOptBuilder->asCurlOptArray() as $key => $value) {
			$curlOptArray[$key] = $value;
		}

		$this->requestResolver->add(
			$uri,
			$curlOptArray,
			$deferred,
			$curlOptBuilder->getIntegrity(),
			$curlOptBuilder->getSignal()
		);

		$newPromise = new Promise($this->loop);

		$deferredPromise->then(function(ResponseInterface $response)
		use($newPromise) {
			$newPromise->resolve($response);
		});

		return $newPromise;
	}

	public function getCurlOptions():array {
		return $this->curlOptions;
	}

	/**
	 * @param string|UriInterface $input
	 * @return string
	 */
	public function ensureUriInterface($input):UriInterface {
		if($input instanceof RequestInterface) {
			$uri = $input->getUri();
		}
		else {
			$uri = new Uri($input);
		}

		return $uri;
	}

	/**
	 * Executes all promises in parallel, returning only when all promises
	 * have been fulfilled.
	 */
	public function wait():void {
		$this->loop->run();
	}

	/**
	 * Begins execution of all promises, returning its own Promise that will
	 * resolve when the last HTTP request is fully resolved.
	 */
	public function all():HttpPromise {
		$start = microtime(true);
		$this->wait();
		$end = microtime(true);

		$promise = new Promise($this->loop);
		$promise->resolve($end - $start);
		return $promise;
	}
}