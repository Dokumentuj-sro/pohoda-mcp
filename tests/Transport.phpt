<?php declare(strict_types=1);

/**
 * The transport() hook: every mServer round-trip, POST and GET alike, goes
 * through one overridable method.
 */

use DG\Pohoda\PohodaClient;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

const OK_RESPONSE = '<rsp:responsePack xmlns:rsp="http://www.stormware.cz/schema/version_2/response.xsd" state="ok" programVersion="14000.3"><rsp:responsePackItem state="ok"/></rsp:responsePack>';


/**
 * Swaps the wire call, as a Laravel host routing mServer through the `Http`
 * facade would. Nothing here may reach the network — port 1 is closed, so a
 * request that slips past the hook fails loudly instead of being mistaken for
 * a pass.
 */
class RecordingTransportClient extends PohodaClient
{
	/** @var list<array{url: string, headers: list<string>, body: ?string}> */
	public array $calls = [];


	protected function transport(string $url, array $headers, ?string $postBody = null): string
	{
		$this->calls[] = ['url' => $url, 'headers' => $headers, 'body' => $postBody];
		return $postBody === null ? 'mServer je pripraven' : OK_RESPONSE;
	}
}


test('an XML POST rides the transport() hook, headers and body included', function () {
	$client = new RecordingTransportClient('http://127.0.0.1:1', '12345678', 'user', 'pass');

	Assert::true($client->sendRawXml('<inv:invoice version="2.0"/>')->isOk());

	Assert::count(1, $client->calls);
	Assert::same('http://127.0.0.1:1/xml', $client->calls[0]['url']);
	Assert::contains('Content-Type: text/xml', $client->calls[0]['headers']);
	Assert::contains('dat:dataPack', $client->calls[0]['body']);
});


test('the /status GET rides the same hook', function () {
	$client = new RecordingTransportClient('http://127.0.0.1:1', '12345678', 'user', 'pass');

	Assert::same(['message' => 'mServer je pripraven'], $client->getStatus());

	Assert::count(1, $client->calls);
	Assert::same('http://127.0.0.1:1/status', $client->calls[0]['url']);
	Assert::null($client->calls[0]['body']);
});
