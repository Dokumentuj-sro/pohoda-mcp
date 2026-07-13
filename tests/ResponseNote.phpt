<?php declare(strict_types=1);

use DG\Pohoda\Response;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


test('a rejected item carries mServer\'s note', function () {
	$response = new Response(
		'<rsp:responsePack xmlns:rsp="http://www.stormware.cz/schema/version_2/response.xsd" state="ok" programVersion="14000.3">'
		. '<rsp:responsePackItem state="error" id="inv-1" note="Required attribute \'invoiceType\' is missing"/>'
		. '</rsp:responsePack>',
	);

	Assert::true($response->isOk());
	Assert::false($response->items[0]->isOk());
	Assert::same("Required attribute 'invoiceType' is missing", $response->items[0]->note);
	Assert::same("Required attribute 'invoiceType' is missing", $response->items[0]->toArray()['note']);
});


test('an accepted item has an empty note', function () {
	$response = new Response(
		'<rsp:responsePack xmlns:rsp="http://www.stormware.cz/schema/version_2/response.xsd" state="ok" programVersion="14000.3">'
		. '<rsp:responsePackItem state="ok" id="inv-1"/>'
		. '</rsp:responsePack>',
	);

	Assert::same('', $response->items[0]->note);
});
