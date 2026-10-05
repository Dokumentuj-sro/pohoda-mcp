<?php declare(strict_types=1);

use DG\Pohoda\XmlBuilder;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$builder = new XmlBuilder('12345678', 'Test');


test('produces valid XML with encoding', function () use ($builder) {
	$xml = $builder->build('inv:invoice', '2.0', ['inv:invoiceHeader' => ['inv:invoiceType' => 'issuedInvoice']]);
	Assert::match('<?xml version="1.0" encoding="UTF-8"?>%A%', $xml);

	$doc = new DOMDocument;
	Assert::true($doc->loadXML($xml));
});


test('declaration matches the actual bytes (UTF-8, diacritics intact)', function () use ($builder) {
	// XMLWriter emits UTF-8; the declaration must say so — a Windows-1250
	// declaration over UTF-8 bytes breaks file consumers (pohoda.exe /XML).
	$xml = $builder->build('inv:invoice', '2.0', [
		'inv:invoiceHeader' => ['inv:text' => 'Údržba plošin — červenec'],
	]);
	Assert::match('<?xml version="1.0" encoding="UTF-8"?>%A%', $xml);
	Assert::true(mb_check_encoding($xml, 'UTF-8'));
	Assert::contains('Údržba plošin — červenec', $xml);

	$raw = $builder->buildRaw('<inv:invoice version="2.0"><inv:invoiceHeader><inv:text>Údržba plošin</inv:text></inv:invoiceHeader></inv:invoice>');
	Assert::match('<?xml version="1.0" encoding="UTF-8"?>%A%', $raw);
	Assert::true(mb_check_encoding($raw, 'UTF-8'));
});


test('dataPack contains ICO and application', function () use ($builder) {
	$xml = $builder->build('inv:invoice', '2.0', []);
	Assert::match('%A%ico="12345678"%A%', $xml);
	Assert::match('%A%application="Test"%A%', $xml);
});


test('writes nested data recursively', function () use ($builder) {
	$xml = $builder->build('inv:invoice', '2.0', [
		'inv:invoiceHeader' => [
			'inv:invoiceType' => 'issuedInvoice',
			'inv:date' => '2024-01-15',
			'inv:partnerIdentity' => [
				'typ:address' => [
					'typ:company' => 'Firma s.r.o.',
				],
			],
		],
	]);
	Assert::match('%A%<inv:invoiceType>issuedInvoice</inv:invoiceType>%A%', $xml);
	Assert::match('%A%<inv:date>2024-01-15</inv:date>%A%', $xml);
	Assert::match('%A%<typ:company>Firma s.r.o.</typ:company>%A%', $xml);
});


test('escapes special characters', function () use ($builder) {
	$xml = $builder->build('inv:invoice', '2.0', [
		'inv:invoiceHeader' => ['inv:text' => 'A & B < "C"'],
	]);
	Assert::match('%A%A &amp; B &lt; &quot;C&quot;%A%', $xml);
});


test('skips null and empty string values', function () use ($builder) {
	$xml = $builder->build('inv:invoice', '2.0', [
		'inv:invoiceHeader' => [
			'inv:invoiceType' => 'issuedInvoice',
			'inv:text' => null,
			'inv:note' => '',
		],
	]);
	Assert::false(str_contains($xml, '<inv:text'));
	Assert::false(str_contains($xml, '<inv:note'));
});


test('empty array generates empty element', function () use ($builder) {
	$xml = $builder->build('inv:invoice', '2.0', [
		'lst:requestInvoice' => [],
	]);
	Assert::match('%A%<lst:requestInvoice/>%A%', $xml);
});


test('writes boolean values', function () use ($builder) {
	$xml = $builder->build('stk:stock', '2.0', [
		'stk:stockHeader' => [
			'stk:isSales' => true,
			'stk:isInternet' => false,
		],
	]);
	Assert::match('%A%<stk:isSales>true</stk:isSales>%A%', $xml);
	Assert::match('%A%<stk:isInternet>false</stk:isInternet>%A%', $xml);
});


test('writes attributes from @-prefixed keys', function () use ($builder) {
	$xml = $builder->build('prn:print', '1.0', [
		'prn:record' => [
			'@agenda' => 'vydane_faktury',
			'ftr:filter' => ['ftr:id' => 42],
		],
	]);
	Assert::match('%A%agenda="vydane_faktury"%A%', $xml);
	Assert::match('%A%<ftr:id>42</ftr:id>%A%', $xml);
});


test('writes root attributes', function () use ($builder) {
	$xml = $builder->build('lst:listInvoiceRequest', '2.0', [], '', [
		'invoiceVersion' => '2.0',
		'invoiceType' => 'issuedInvoice',
	]);
	Assert::match('%A%invoiceVersion="2.0"%A%', $xml);
	Assert::match('%A%invoiceType="issuedInvoice"%A%', $xml);
});


test('handles numeric keys for array items', function () use ($builder) {
	$xml = $builder->build('ord:order', '2.0', [
		'ord:orderDetail' => [
			['ord:orderItem' => ['ord:text' => 'Item 1']],
			['ord:orderItem' => ['ord:text' => 'Item 2']],
		],
	]);
	Assert::match('%A%<ord:orderItem>%A?%Item 1%A?%</ord:orderItem>%A?%<ord:orderItem>%A?%Item 2%A?%</ord:orderItem>%A%', $xml);
});


test('emits exactly the bytes XMLWriter did, without needing ext-xmlwriter', function () use ($builder) {
	// The Accounting Bridge's static PHP has no ext-xmlwriter; these bytes are
	// what XMLWriter produced for the same input, escaping included.
	$xml = $builder->build('lst:listInvoiceRequest', '2.0', [
		'lst:requestInvoice' => ['@note' => "a\"b\n\tc", 'ftr:filter' => ['ftr:id' => 7]],
		'lst:flag' => false,
		'lst:empty' => [],
		'lst:text' => "x & <y> \"z\" 'w'\r",
	], rootAttrs: ['invoiceType' => 'receivedInvoice']);

	Assert::same(
		'<?xml version="1.0" encoding="UTF-8"?>' . "\n"
		. '<dat:dataPack id="X" ico="12345678" application="Test" version="2.0" note=""'
		. ' xmlns:dat="http://www.stormware.cz/schema/version_2/data.xsd" xmlns:typ="http://www.stormware.cz/schema/version_2/type.xsd" xmlns:ftr="http://www.stormware.cz/schema/version_2/filter.xsd" xmlns:inv="http://www.stormware.cz/schema/version_2/invoice.xsd" xmlns:ord="http://www.stormware.cz/schema/version_2/order.xsd" xmlns:adb="http://www.stormware.cz/schema/version_2/addressbook.xsd" xmlns:stk="http://www.stormware.cz/schema/version_2/stock.xsd" xmlns:prn="http://www.stormware.cz/schema/version_2/print.xsd" xmlns:lst="http://www.stormware.cz/schema/version_2/list.xsd" xmlns:lStk="http://www.stormware.cz/schema/version_2/list_stock.xsd" xmlns:lAdb="http://www.stormware.cz/schema/version_2/list_addBook.xsd" xmlns:lCon="http://www.stormware.cz/schema/version_2/list_contract.xsd" xmlns:lCen="http://www.stormware.cz/schema/version_2/list_centre.xsd" xmlns:lAcv="http://www.stormware.cz/schema/version_2/list_activity.xsd">'
		. '<dat:dataPackItem id="X" version="2.0">'
		. '<lst:listInvoiceRequest version="2.0" invoiceType="receivedInvoice">'
		. '<lst:requestInvoice note="a&quot;b&#10;&#9;c"><ftr:filter><ftr:id>7</ftr:id></ftr:filter></lst:requestInvoice>'
		. '<lst:flag>false</lst:flag><lst:empty/>'
		. '<lst:text>x &amp; &lt;y&gt; &quot;z&quot; \'w\'&#13;</lst:text>'
		. '</lst:listInvoiceRequest></dat:dataPackItem></dat:dataPack>' . "\n",
		preg_replace('/ id="\d{8}"/', ' id="X"', $xml),
	);
});
