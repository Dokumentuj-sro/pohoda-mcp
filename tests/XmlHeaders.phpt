<?php declare(strict_types=1);

use DG\Pohoda\PohodaClient;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


/** Exposes the protected xmlHeaders() hook for assertion. */
class ExposedPohodaClient extends PohodaClient
{
	/** @return list<string> */
	public function exposedXmlHeaders(): array
	{
		return $this->xmlHeaders();
	}
}


/** Overrides the hook to append an extra header, as a downstream fork would. */
class ExtraHeaderPohodaClient extends ExposedPohodaClient
{
	/** @return list<string> */
	protected function xmlHeaders(): array
	{
		return [...parent::xmlHeaders(), 'X-Extra: from-subclass'];
	}
}


test('base xmlHeaders() returns exactly the two known headers', function () {
	$client = new ExposedPohodaClient('http://localhost:8080', '12345678', 'user', 'pass');
	Assert::same([
		'Content-Type: text/xml',
		'STW-Authorization: Basic ' . base64_encode('user:pass'),
	], $client->exposedXmlHeaders());
});


test('overriding subclass adds its extra header on top of the parent headers', function () {
	$client = new ExtraHeaderPohodaClient('http://localhost:8080', '12345678', 'user', 'pass');
	Assert::same([
		'Content-Type: text/xml',
		'STW-Authorization: Basic ' . base64_encode('user:pass'),
		'X-Extra: from-subclass',
	], $client->exposedXmlHeaders());
});
