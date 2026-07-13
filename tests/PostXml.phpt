<?php declare(strict_types=1);

use DG\Pohoda\PohodaClient;
use DG\Pohoda\Response;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

const OK_RESPONSE = '<rsp:responsePack xmlns:rsp="http://www.stormware.cz/schema/version_2/response.xsd" state="ok" programVersion="14000.3"><rsp:responsePackItem state="ok"/></rsp:responsePack>';


/** Swaps the transport, as a downstream CLI-fallback subclass would. */
class CapturingTransportClient extends PohodaClient
{
	public ?string $sentXml = null;


	protected function postXml(string $xml): Response
	{
		$this->sentXml = $xml;
		return new Response(OK_RESPONSE);
	}
}


test('a structured request rides the postXml() hook with the full dataPack', function () {
	$client = new CapturingTransportClient('http://localhost:8080', '12345678', 'user', 'pass');

	$response = $client->createInvoice(['type' => 'issuedInvoice', 'text' => 'Test'], []);

	Assert::true($response->isOk());
	Assert::contains('dat:dataPack', $client->sentXml);
	Assert::contains('ico="12345678"', $client->sentXml);
	Assert::contains('issuedInvoice', $client->sentXml);
});


test('sendRawXml() rides the same hook', function () {
	$client = new CapturingTransportClient('http://localhost:8080', '12345678', 'user', 'pass');

	$response = $client->sendRawXml('<inv:invoice version="2.0"/>');

	Assert::true($response->isOk());
	Assert::contains('<inv:invoice version="2.0"/>', $client->sentXml);
});
