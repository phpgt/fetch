<?php
namespace Gt\Fetch\Test;

use Gt\Fetch\Http;
use Gt\Fetch\Promise;
use Gt\Fetch\Response\BodyResponse;
use Gt\Fetch\Test\Helper\ResponseSimulator;
use Gt\Fetch\Test\Helper\TestCurl;
use Gt\Fetch\Test\Helper\TestCurlMulti;
use Gt\Http\Request;
use Gt\Http\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class HttpTest extends TestCase {
	/** @runInSeparateProcess */
	public function testFetchBodyResponsePromise() {
		$http = new Http(
			[],
			0.01,
			TestCurl::class,
			TestCurlMulti::class
		);

		$fakeStatus = null;

		$http->fetch("test://should-return")
		->then(function(BodyResponse $response)use(&$fakeStatus) {
			$fakeStatus = $response->status;
		});

		$http->wait();
		self::assertEquals(999, $fakeStatus);
	}

	public function testFetchBodyResponsePromiseResolves() {
		$expectedHtml = "<!doctype html><h1>Hello, Fetch!</h1>";
		ResponseSimulator::setExpectedBody($expectedHtml);
		$http = new Http(
			[],
			0.01,
			TestCurl::class,
			TestCurlMulti::class
		);

		$actualResponse = null;

		$http->fetch("test://should-return")
		->then(function(BodyResponse $response)use(&$fakeStatus) {
			return $response->text();
		})
		->then(function(string $text)use(&$actualResponse) {
			$actualResponse = $text;
		});

		$http->wait();
		self::assertEquals($expectedHtml, $actualResponse);

	}

	public function testGetOptions() {
		$options = [
			uniqid() => uniqid(),
			uniqid() => uniqid(),
			uniqid() => uniqid(),
			uniqid() => uniqid(),
			uniqid() => uniqid(),
		];
		$http = new Http($options);
		$actualOptions = $http->getCurlOptions();

		foreach($options as $key => $value) {
			self::assertEquals($value, $actualOptions[$key]);
		}

		self::assertGreaterThan(count($options), count($actualOptions));
	}

	public function testAll() {
		$http = new Http(
			[],
			0.01,
			TestCurl::class,
			TestCurlMulti::class
		);

		$actualResponse = null;

		$http->fetch("test://should-return")
			->then(function(BodyResponse $response)use(&$fakeStatus) {
				return $response->text();
			})
			->then(function(string $text)use(&$actualResponse) {
				$actualResponse = $text;
			});

		$finalPromiseResolved = false;

		$http->all()
		->then(function() use(&$finalPromiseResolved) {
			$finalPromiseResolved = true;
		});

		self::assertTrue($finalPromiseResolved);
	}

	public function testEnsureUriInterface() {
		$sut = new Http();
		$uri = $sut->ensureUriInterface("test://example");
		self::assertInstanceOf(UriInterface::class, $uri);

		$uriInterface = self::createMock(Uri::class);
		$uriInterface->method("__toString")
			->willReturn("test://example");
		$request = self::createMock(Request::class);
		$request->method("getUri")
			->willReturn($uriInterface);
		$uri = $sut->ensureUriInterface($request);
		self::assertInstanceOf(UriInterface::class, $uri);
	}
}