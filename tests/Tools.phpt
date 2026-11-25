<?php declare(strict_types=1);

/**
 * The generic write tools: create_document, update_record, delete_record and
 * list_codebook build the agenda's own XML, and a refused item's note reaches
 * the caller instead of a bare "ok" envelope.
 */

use DG\Pohoda\McpTools;
use DG\Pohoda\PohodaClient;
use Mcp\Exception\ToolCallException;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class ToolsRecordingClient extends PohodaClient
{
	/** @var list<string> */
	public array $bodies = [];

	public string $reply = '<rsp:responsePack xmlns:rsp="http://www.stormware.cz/schema/version_2/response.xsd" state="ok" programVersion="14000.3"><rsp:responsePackItem state="ok" id="x"/></rsp:responsePack>';


	protected function transport(string $url, array $headers, ?string $postBody = null): string
	{
		$this->bodies[] = (string) $postBody;
		return $this->reply;
	}
}


function toolsClient(): ToolsRecordingClient
{
	return new ToolsRecordingClient('http://127.0.0.1:1', '12345678', 'user', 'pass');
}


test('create_document writes a cash voucher with its type, cash account and items', function () {
	$client = toolsClient();
	(new McpTools($client))->createDocument(
		agenda: 'voucher',
		type: 'expense',
		items: [['text' => 'Papír', 'quantity' => 2, 'unitPrice' => 100, 'rateVAT' => 'high']],
		date: '2026-11-25',
		cashRegister: 'HP',
		text: 'Nákup',
	);

	$body = $client->bodies[0];
	Assert::contains('<vch:voucherType>expense</vch:voucherType>', $body);
	Assert::contains('<vch:cashAccount><typ:ids>HP</typ:ids></vch:cashAccount>', $body);
	Assert::contains('<vch:voucherItem>', $body);
	Assert::contains('Papír', $body);
	Assert::true(strpos($body, 'voucherType') < strpos($body, 'cashAccount'));
});


test('create_document on bank uses the early account tag, not the payment one', function () {
	$client = toolsClient();
	(new McpTools($client))->createDocument(agenda: 'bank', type: 'receipt', date: '2026-11-25', accountIds: 'KB', text: 'Příjem');

	$body = $client->bodies[0];
	Assert::same(1, substr_count($body, '<bnk:account>'));
	Assert::contains('<bnk:datePayment>2026-11-25</bnk:datePayment>', $body);
});


test('create_document on invoice puts the payment account after paymentType', function () {
	$client = toolsClient();
	(new McpTools($client))->createDocument(agenda: 'invoice', type: 'issuedInvoice', paymentType: 'draft', accountIds: 'KB', text: 'X');

	$body = $client->bodies[0];
	Assert::same(1, substr_count($body, '<inv:account>'));
	Assert::true(strpos($body, 'paymentType') < strpos($body, '<inv:account>'));
});


test('a typed agenda without a type is refused before it reaches mServer', function () {
	$client = toolsClient();
	Assert::exception(
		fn() => (new McpTools($client))->createDocument(agenda: 'invoice', text: 'X'),
		\Throwable::class,
	);
	Assert::count(0, $client->bodies);
});


test('update_record sends an update action filtered by id', function () {
	$client = toolsClient();
	(new McpTools($client))->updateRecord(agenda: 'invoice', id: 42, fields: '{"text":"Nový text"}');

	$body = $client->bodies[0];
	Assert::contains('<inv:actionType><inv:update><ftr:filter><ftr:id>42</ftr:id></ftr:filter></inv:update></inv:actionType>', $body);
	Assert::contains('<inv:text>Nový text</inv:text>', $body);
});


test('update_record refuses fields that are not a JSON object', function () {
	$client = toolsClient();
	Assert::exception(
		fn() => (new McpTools($client))->updateRecord(agenda: 'invoice', id: 42, fields: '["text"]'),
		ToolCallException::class,
	);
	Assert::count(0, $client->bodies);
});


test('delete_record sends a delete action; stock may be selected by code', function () {
	$client = toolsClient();
	(new McpTools($client))->deleteRecord(agenda: 'stock', code: 'ABC-1');

	Assert::contains('<stk:delete><ftr:filter><ftr:code>ABC-1</ftr:code></ftr:filter></stk:delete>', $client->bodies[0]);
});


test('delete_record with neither id nor code is a tool error, not a wire call', function () {
	$client = toolsClient();
	Assert::exception(
		fn() => (new McpTools($client))->deleteRecord(agenda: 'invoice'),
		ToolCallException::class,
	);
	Assert::count(0, $client->bodies);
});


test('list_codebook asks mServer for the codebook list', function () {
	$client = toolsClient();
	(new McpTools($client))->listCodebook('cashRegister');

	Assert::contains('listCashRegisterRequest', $client->bodies[0]);
});


test('a refused item surfaces mServer\'s note even under an ok envelope', function () {
	$client = toolsClient();
	$client->reply = '<rsp:responsePack xmlns:rsp="http://www.stormware.cz/schema/version_2/response.xsd" state="ok" programVersion="14000.3"><rsp:responsePackItem state="error" id="x" note="Doklad je zaúčtován"/></rsp:responsePack>';

	$e = Assert::exception(
		fn() => (new McpTools($client))->deleteRecord(agenda: 'invoice', id: 7),
		ToolCallException::class,
	);
	Assert::contains('Pohoda mServer returned error: Doklad je zaúčtován', $e->getMessage());
});
