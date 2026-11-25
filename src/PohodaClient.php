<?php declare(strict_types=1);

namespace DG\Pohoda;

use function in_array, is_string;


/**
 * HTTP client for Pohoda mServer API.
 */
class PohodaClient
{
	/********************* Agenda configuration ****************d*g**/


	private const AgendaNamespaces = [
		'stock' => ['listPrefix' => 'lStk', 'listXsd' => 'list_stock.xsd', 'listRequest' => 'listStockRequest', 'requestTag' => 'requestStock'],
		'invoice' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listInvoiceRequest', 'requestTag' => 'requestInvoice'],
		'addressbook' => ['listPrefix' => 'lAdb', 'listXsd' => 'list_addBook.xsd', 'listRequest' => 'listAddressBookRequest', 'requestTag' => 'requestAddressBook', 'versionAttr' => 'addressBookVersion'],
		'order' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listOrderRequest', 'requestTag' => 'requestOrder'],
		'voucher' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listVoucherRequest', 'requestTag' => 'requestVoucher'],
		'bank' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listBankRequest', 'requestTag' => 'requestBank'],
		'contract' => ['listPrefix' => 'lCon', 'listXsd' => 'list_contract.xsd', 'listRequest' => 'listContractRequest', 'requestTag' => 'requestContract'],
		'intDoc' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listIntDocRequest', 'requestTag' => 'requestIntDoc'],
		'offer' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listOfferRequest', 'requestTag' => 'requestOffer'],
		'enquiry' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listEnquiryRequest', 'requestTag' => 'requestEnquiry'],
		'vydejka' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listVydejkaRequest', 'requestTag' => 'requestVydejka'],
		'prijemka' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listPrijemkaRequest', 'requestTag' => 'requestPrijemka'],
		'prodejka' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listProdejkaRequest', 'requestTag' => 'requestProdejka'],
		'prevodka' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listPrevodkaRequest', 'requestTag' => 'requestPrevodka'],
		'vyroba' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listVyrobaRequest', 'requestTag' => 'requestVyroba'],
		'accountancy' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listAccountancyRequest', 'requestTag' => 'requestAccountancy'],
		'store' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listStoreRequest', 'requestTag' => 'requestStore'],
		'bankAccount' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listBankAccountRequest', 'requestTag' => 'requestBankAccount', 'versionAttr' => 'bankAccountVersion'],
		'cashRegister' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listCashRegisterRequest', 'requestTag' => 'requestCashRegister', 'versionAttr' => 'cashRegisterVersion'],
		'numericalSeries' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listNumericalSeriesRequest', 'requestTag' => 'requestNumericalSeries', 'versionAttr' => 'numericalSeriesVersion'],
		'centre' => ['listPrefix' => 'lCen', 'listXsd' => 'list_centre.xsd', 'listRequest' => 'listCentreRequest', 'requestTag' => 'requestCentre'],
		'activity' => ['listPrefix' => 'lAcv', 'listXsd' => 'list_activity.xsd', 'listRequest' => 'listActivityRequest', 'requestTag' => 'requestActivity'],
		'service' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listServiceRequest', 'requestTag' => 'requestService'],
		'movement' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listMovementRequest', 'requestTag' => 'requestMovement'],
		'classificationVAT' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listClassificationVATRequest', 'requestTag' => 'requestClassificationVAT'],
		'payment' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listPaymentRequest', 'requestTag' => 'requestPayment'],
		'storage' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listStorageRequest', 'requestTag' => 'requestStorage'],
		'category' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listCategoryRequest', 'requestTag' => 'requestCategory'],
		'accountingUnit' => ['listPrefix' => 'lst', 'listXsd' => 'list.xsd', 'listRequest' => 'listAccountingUnitRequest', 'requestTag' => 'requestAccountingUnit'],
	];

	/**
	 * Document agendas {@see createDocument()}, {@see updateRecord()} and
	 * {@see deleteRecord()} write: agenda => [namespace prefix, root element,
	 * header element, has a `<p>:<root>Type` discriminator]. Structures follow
	 * the Stormware XSDs (and the MIT-licensed pohoda-mcp by Hleb Tkachenko,
	 * which mapped them first).
	 */
	public const DocumentAgendas = [
		'invoice' => ['inv', 'invoice', 'invoiceHeader', true],
		'order' => ['ord', 'order', 'orderHeader', true],
		'voucher' => ['vch', 'voucher', 'voucherHeader', true],
		'bank' => ['bnk', 'bank', 'bankHeader', true],
		'intDoc' => ['int', 'intDoc', 'intDocHeader', false],
		'offer' => ['ofr', 'offer', 'offerHeader', true],
		'enquiry' => ['enq', 'enquiry', 'enquiryHeader', true],
		'contract' => ['con', 'contract', 'contractDesc', false],
		'service' => ['ser', 'service', 'serviceHeader', false],
		'prijemka' => ['pri', 'prijemka', 'prijemkaHeader', false],
		'vydejka' => ['vyd', 'vydejka', 'vydejkaHeader', false],
		'prodejka' => ['pro', 'prodejka', 'prodejkaHeader', false],
		'prevodka' => ['pre', 'prevodka', 'prevodkaHeader', false],
		'vyroba' => ['vyr', 'vyroba', 'vyrobaHeader', false],
		'addressbook' => ['adb', 'addressbook', 'addressbookHeader', false],
		'stock' => ['stk', 'stock', 'stockHeader', false],
	];

	/**
	 * Header fields in the XSD `xs:sequence` order the document headers share,
	 * so one builder serves every agenda. A field an agenda does not define is
	 * refused by mServer with a note naming it, which the caller sees verbatim.
	 */
	private const HeaderOrder = [
		'cashRegister', 'bankAccount', 'number', 'symVar', 'date', 'datePayment', 'dateTax', 'dateAccounting',
		'dateDue', 'dateDelivery', 'datePlan', 'accounting', 'classificationVAT', 'text', 'partnerIdentity',
		'paymentType', 'account', 'symConst', 'symSpec', 'centre', 'activity', 'contract', 'note', 'intNote',
	];
	private string $authHeader;
	private XmlBuilder $xml;
	private ?MServerController $controller = null;
	private bool $started = false;
	private bool $controllerStarted = false;


	public function __construct(
		private string $url,
		string $ico,
		string $username,
		string $password,
		string $application = 'MCP Server',
	) {
		$this->url = rtrim($url, '/');
		$this->authHeader = 'Basic ' . base64_encode($username . ':' . $password);
		$this->xml = new XmlBuilder($ico, $application);
	}


	/**
	 * Enable auto-start of mServer. When set, the client will lazily start the
	 * mServer before the first HTTP request and stop it on shutdown (only if
	 * we actually started it, i.e. it wasn't running already).
	 */
	public function setController(MServerController $controller): void
	{
		$this->controller = $controller;
	}


	public function __destruct()
	{
		if ($this->controllerStarted && $this->controller !== null) {
			try {
				$this->controller->stop();
			} catch (\Throwable) {
				// destructor must not throw
			}
		}
	}


	/********************* High-level API ****************d*g**/


	/**
	 * Check mServer status.
	 * @return array{status?: string, server?: string, processing?: string, message?: string, company?: string, databaseName?: string, year?: string}
	 */
	public function getStatus(bool $companyDetail = false): array
	{
		$url = $this->url . '/status' . ($companyDetail ? '?companyDetail' : '');
		$headers = $companyDetail ? ['STW-Authorization: ' . $this->authHeader] : [];
		$response = $this->httpGet($url, $headers);

		$doc = @simplexml_load_string($response);
		if ($doc === false) {
			return ['message' => trim($response)];
		}

		$result = [
			'status' => (string) $doc->status,
			'server' => (string) $doc->server,
			'processing' => (string) $doc->processing,
		];
		if (isset($doc->companyDetail)) {
			$result['company'] = (string) $doc->companyDetail->company;
			$result['databaseName'] = (string) $doc->companyDetail->databaseName;
			$result['year'] = (string) $doc->companyDetail->year;
		}
		return $result;
	}


	/**
	 * List records from a given agenda.
	 * @param array<string, mixed> $filter
	 */
	public function listRecords(string $agenda, array $filter = [], string $subType = ''): Response
	{
		$ns = self::AgendaNamespaces[$agenda]
			?? throw new \InvalidArgumentException("Unknown agenda: $agenda. Supported: " . implode(', ', array_keys(self::AgendaNamespaces)));

		$versionAttr = $ns['versionAttr'] ?? ($agenda . 'Version');
		$requestTag = $ns['listPrefix'] . ':' . $ns['listRequest'];
		$innerTag = $ns['listPrefix'] . ':' . $ns['requestTag'];

		$filterData = self::buildFilterData($filter);
		$inner = [$innerTag => $filterData ? ['ftr:filter' => $filterData] : []];

		return $this->send($requestTag, '2.0', $inner, 'List ' . $agenda, [
			$versionAttr => '2.0',
			...($subType !== '' ? [$agenda . 'Type' => $subType] : []),
		]);
	}


	/**
	 * @param array<string, mixed> $header  Keys: type, date, partnerName, partnerIco, etc.
	 * @param list<array<string, mixed>> $items  Each: text, quantity, unit, unitPrice, vatRate, stockCode
	 */
	public function createInvoice(array $header, array $items = []): Response
	{
		$type = match ($header['type'] ?? '') {
			'receivedInvoice' => 'receivedInvoice',
			default => 'issuedInvoice',
		};
		$currency = $header['currency'] ?? '';

		$invoiceHeader = array_filter([
			'inv:invoiceType' => $type,
			'inv:symVar' => $header['symVar'] ?? null,
			'inv:date' => $header['date'] ?? null,
			'inv:dateTax' => $header['dateTax'] ?? null,
			'inv:dateAccounting' => $header['dateAccounting'] ?? null,
			'inv:dateDue' => $header['dateDue'] ?? null,
			'inv:accounting' => self::ids($header['accounting'] ?? ''),
			'inv:classificationVAT' => ($v = $header['classificationVAT'] ?? '') !== '' ? ['typ:classificationVATType' => $v] : null,
			'inv:text' => $header['text'] ?? null,
			'inv:partnerIdentity' => self::buildPartnerIdentity($header),
			'inv:paymentType' => self::buildPaymentType($header['paymentType'] ?? ''),
			'inv:account' => self::ids($header['accountIds'] ?? ''),
			'inv:note' => $header['note'] ?? null,
			'inv:intNote' => $header['intNote'] ?? null,
			'inv:centre' => self::ids($header['centre'] ?? ''),
			'inv:activity' => self::ids($header['activity'] ?? ''),
			'inv:contract' => self::ids($header['contract'] ?? ''),
		], fn($v) => $v !== null && $v !== '' && $v !== []);

		$invoiceItems = [];
		foreach ($items as $item) {
			$priceElement = $currency
				? ['inv:foreignCurrency' => ['typ:unitPrice' => (float) ($item['unitPrice'] ?? 0)]]
				: ['inv:homeCurrency' => ['typ:unitPrice' => (float) ($item['unitPrice'] ?? 0)]];

			$invoiceItems[] = array_filter([
				'inv:text' => $item['text'] ?? '',
				'inv:quantity' => (float) ($item['quantity'] ?? 1),
				'inv:unit' => $item['unit'] ?? null,
				'inv:rateVAT' => $item['vatRate'] ?? 'high',
				...$priceElement,
				'inv:stockItem' => ($code = $item['stockCode'] ?? '') !== ''
					? ['typ:stockItem' => ['typ:ids' => $code]]
					: null,
			], fn($v) => $v !== null && $v !== '');
		}

		$data = ['inv:invoiceHeader' => $invoiceHeader];
		if ($invoiceItems) {
			$data['inv:invoiceDetail'] = array_map(fn($item) => ['inv:invoiceItem' => $item], $invoiceItems);
		}
		if ($currency) {
			$data['inv:invoiceSummary'] = [
				'inv:foreignCurrency' => [
					'typ:currency' => ['typ:ids' => $currency],
					'typ:rate' => (float) ($header['currencyRate'] ?? 0) ?: 1,
					'typ:amount' => 1,
				],
			];
		}

		return $this->send('inv:invoice', '2.0', $data, 'Create invoice');
	}


	/**
	 * @param array<string, string> $data  Keys: company, ico, dic, street, city, zip, phone, email
	 */
	public function createAddress(array $data): Response
	{
		return $this->send('adb:addressbook', '2.0', [
			'adb:addressbookHeader' => self::addressHeader($data),
		], 'Create address');
	}


	/**
	 * @param array<string, mixed> $data  Keys: code, name, sellingPrice, unit, storage, vatRate, etc.
	 */
	public function createStock(array $data): Response
	{
		return $this->send('stk:stock', '2.0', ['stk:stockHeader' => self::stockHeader($data)], 'Create stock');
	}


	/**
	 * @param array<string, string> $header  Keys: type, partnerName, date, partnerIco
	 * @param list<array<string, mixed>> $items
	 */
	public function createOrder(array $header, array $items): Response
	{
		$type = match ($header['type'] ?? '') {
			'issuedOrder' => 'issuedOrder',
			default => 'receivedOrder',
		};

		$orderItems = [];
		foreach ($items as $item) {
			$orderItems[] = ['ord:orderItem' => array_filter([
				'ord:text' => $item['text'] ?? '',
				'ord:quantity' => (float) ($item['quantity'] ?? 1),
				'ord:unit' => $item['unit'] ?? null,
				'ord:rateVAT' => $item['vatRate'] ?? 'high',
				'ord:homeCurrency' => ['typ:unitPrice' => (float) ($item['unitPrice'] ?? 0)],
			], fn($v) => $v !== null && $v !== '')];
		}

		return $this->send('ord:order', '2.0', [
			'ord:orderHeader' => array_filter([
				'ord:orderType' => $type,
				'ord:date' => $header['date'] ?? '',
				'ord:partnerIdentity' => self::buildPartnerIdentity($header),
			], fn($v) => $v !== null && $v !== '' && $v !== []),
			'ord:orderDetail' => $orderItems,
		], 'Create order');
	}


	/**
	 * @param array{agenda: string, recordId: int, reportId: int, pdfPath?: string, pdfBase64?: bool, printer?: string, copies?: int} $options
	 */
	public function printRecord(array $options): Response
	{
		$pdfPath = $options['pdfPath'] ?? '';
		$pdfBase64 = $options['pdfBase64'] ?? false;

		if ($pdfBase64 && $pdfPath === '') {
			throw new \InvalidArgumentException('pdfPath is required when using pdfBase64');
		}

		$printerSettings = [
			'prn:report' => ['prn:id' => $options['reportId']],
		];
		if (($options['printer'] ?? '') !== '') {
			$printerSettings['prn:printer'] = $options['printer'];
		}
		if ($pdfPath !== '') {
			$pdf = ['prn:fileName' => $pdfPath];
			if ($pdfBase64) {
				$pdf['prn:binaryData'] = ['prn:responseXml' => 'true'];
			}
			$printerSettings['prn:pdf'] = $pdf;
		}
		if (($options['copies'] ?? 1) > 1) {
			$printerSettings['prn:parameters'] = ['prn:copy' => min($options['copies'], 20)];
		}

		return $this->send('prn:print', '1.0', [
			'prn:record' => [
				'@agenda' => $options['agenda'],
				'ftr:filter' => ['ftr:id' => $options['recordId']],
			],
			'prn:printerSettings' => $printerSettings,
		], 'Print ' . $options['agenda']);
	}


	/**
	 * Create a document in any agenda of {@see DocumentAgendas} (not addressbook
	 * or stock, which have their own shapes).
	 * @param array<string, mixed> $header  Keys: type, number, symVar, date, dateDue, text, partnerId|partnerName…, cashRegister, bankAccount, note, …
	 * @param list<array<string, mixed>> $items  Each: text, quantity, unit, unitPrice, vatRate, stockCode
	 */
	public function createDocument(string $agenda, array $header, array $items = []): Response
	{
		[$p, $root, $headerTag] = self::documentAgenda($agenda);
		if ($agenda === 'addressbook' || $agenda === 'stock') {
			throw new \InvalidArgumentException("Use the dedicated $agenda create.");
		}

		$data = ["$p:$headerTag" => $this->documentHeader($agenda, $header)];
		if ($items) {
			$data["$p:{$root}Detail"] = array_map(fn(array $item) => ["$p:{$root}Item" => self::documentItem($p, $item)], $items);
		}

		return $this->send("$p:$root", '2.0', $data, "Create $agenda");
	}


	/**
	 * Update one record, selected by its Pohoda ID (or by `code` for stock).
	 * Only the fields given are sent; for addressbook and stock the keys are
	 * those of {@see createAddress()} / {@see createStock()}.
	 * @param array<string, mixed> $fields
	 */
	public function updateRecord(string $agenda, int $id, array $fields, string $code = ''): Response
	{
		[$p, $root, $headerTag] = self::documentAgenda($agenda);

		$header = match ($agenda) {
			'addressbook' => self::addressHeader($fields),
			'stock' => self::stockHeader($fields, update: true),
			default => $this->documentHeader($agenda, $fields, update: true),
		};
		if (!$header) {
			throw new \InvalidArgumentException('Nothing to update: no fields given.');
		}

		return $this->send("$p:$root", '2.0', [
			"$p:actionType" => ["$p:update" => self::recordFilter($id, $code)],
			"$p:$headerTag" => $header,
		], "Update $agenda");
	}


	/**
	 * Delete one record, selected by its Pohoda ID (or by `code` for stock).
	 */
	public function deleteRecord(string $agenda, int $id, string $code = ''): Response
	{
		[$p, $root] = self::documentAgenda($agenda);

		return $this->send("$p:$root", '2.0', [
			"$p:actionType" => ["$p:delete" => self::recordFilter($id, $code)],
		], "Delete $agenda");
	}


	/**
	 * Send raw XML string as dataPackItem content.
	 *
	 * $period names the účetní období the import lands in; null leaves it to the
	 * logged-in user's currently open one, which is the pre-existing behaviour.
	 */
	public function sendRawXml(string $innerXml, string $note = '', ?string $period = null): Response
	{
		return $this->postXml($this->xml->buildRaw($innerXml, $note, $period));
	}


	/********************* Helpers ****************d*g**/


	/**
	 * Build and send a structured request.
	 * @param array<string, mixed> $data
	 * @param array<string, string> $rootAttrs  Extra attributes on the root element
	 * @param ?string $period  the účetní období to import into; null = the user's open one
	 */
	private function send(string $rootElement, string $version, array $data, string $note, array $rootAttrs = [], ?string $period = null): Response
	{
		return $this->postXml($this->xml->build($rootElement, $version, $data, $note, $rootAttrs, $period));
	}


	/**
	 * Deliver one built dataPack and return the parsed response. Protected so a
	 * downstream subclass can swap the transport (e.g. `pohoda.exe /XML` batch
	 * CLI) while inheriting every agenda mapping unchanged; the default posts
	 * to mServer exactly as before.
	 */
	protected function postXml(string $xml): Response
	{
		return new Response($this->httpPost($this->url . '/xml', $xml));
	}


	/**
	 * Shortcut for <typ:ids>$value</typ:ids> reference.
	 * @return array<string, string>|null
	 */
	private static function ids(string $value): ?array
	{
		return $value !== '' ? ['typ:ids' => $value] : null;
	}


	/**
	 * @return array{string, string, string, bool}
	 */
	private static function documentAgenda(string $agenda): array
	{
		return self::DocumentAgendas[$agenda]
			?? throw new \InvalidArgumentException("Unknown agenda: $agenda. Supported: " . implode(', ', array_keys(self::DocumentAgendas)));
	}


	/**
	 * @return array{'ftr:filter': array<string, int|string>}
	 */
	private static function recordFilter(int $id, string $code): array
	{
		if ($id > 0) {
			return ['ftr:filter' => ['ftr:id' => $id]];
		}
		if ($code !== '') {
			return ['ftr:filter' => ['ftr:code' => $code]];
		}
		throw new \InvalidArgumentException('Select the record by its Pohoda ID (or stock code).');
	}


	/**
	 * @param array<string, mixed> $header
	 * @return array<string, mixed>
	 */
	private function documentHeader(string $agenda, array $header, bool $update = false): array
	{
		[$p, $root, , $typed] = self::documentAgenda($agenda);
		$str = fn(string $key): string => (string) ($header[$key] ?? '');

		$values = [
			'cashRegister' => self::ids($str('cashRegister')),
			// a bank doklad names its own account early; every other agenda names the payment account after paymentType
			'bankAccount' => $agenda === 'bank' ? self::ids($str('accountIds')) : null,
			'number' => $str('number') !== '' ? ['typ:numberRequested' => $str('number')] : null,
			'symVar' => $str('symVar'),
			// a bank statement line has no `date`, its doklad date is `datePayment`
			'date' => $agenda === 'bank' ? null : $str('date'),
			'datePayment' => $agenda === 'bank' ? ($str('datePayment') ?: $str('date')) : $str('datePayment'),
			'dateTax' => $str('dateTax'),
			'dateAccounting' => $str('dateAccounting'),
			'dateDue' => $str('dateDue'),
			'dateDelivery' => $str('dateDelivery'),
			'datePlan' => $str('datePlan'),
			'accounting' => self::ids($str('accounting')),
			'classificationVAT' => $str('classificationVAT') !== '' ? ['typ:classificationVATType' => $str('classificationVAT')] : null,
			'text' => $str('text'),
			'partnerIdentity' => self::buildPartnerIdentity($header),
			'paymentType' => self::buildPaymentType($str('paymentType')),
			'account' => $agenda === 'bank' ? null : self::ids($str('accountIds')),
			'symConst' => $str('symConst'),
			'symSpec' => $str('symSpec'),
			'centre' => self::ids($str('centre')),
			'activity' => self::ids($str('activity')),
			'contract' => self::ids($str('contract')),
			'note' => $str('note'),
			'intNote' => $str('intNote'),
		];

		$result = [];
		if ($typed && $str('type') !== '') {
			$result["$p:{$root}Type"] = $str('type');
		} elseif ($typed && !$update) {
			throw new \InvalidArgumentException("A new $agenda needs its type.");
		}
		foreach (self::HeaderOrder as $field) {
			$tag = ['bankAccount' => 'account', 'cashRegister' => 'cashAccount'][$field] ?? $field;
			if ($values[$field] !== null && $values[$field] !== '') {
				$result["$p:$tag"] = $values[$field];
			}
		}
		return $result;
	}


	/**
	 * @param array<string, mixed> $item
	 * @return array<string, mixed>
	 */
	private static function documentItem(string $p, array $item): array
	{
		$code = (string) ($item['stockCode'] ?? '');
		return array_filter([
			"$p:text" => $item['text'] ?? null,
			"$p:quantity" => (float) ($item['quantity'] ?? 1),
			"$p:unit" => $item['unit'] ?? null,
			"$p:rateVAT" => isset($item['unitPrice']) ? ($item['vatRate'] ?? 'high') : null,
			"$p:homeCurrency" => isset($item['unitPrice']) ? ['typ:unitPrice' => (float) $item['unitPrice']] : null,
			"$p:stockItem" => $code !== '' ? ['typ:stockItem' => ['typ:ids' => $code]] : null,
		], fn($v) => $v !== null && $v !== '');
	}


	/**
	 * @param array<string, mixed> $data  Keys: company, ico, dic, street, city, zip, phone, email
	 * @return array<string, mixed>
	 */
	private static function addressHeader(array $data): array
	{
		$address = array_filter([
			'typ:company' => $data['company'] ?? null,
			'typ:ico' => $data['ico'] ?? null,
			'typ:dic' => $data['dic'] ?? null,
			'typ:street' => $data['street'] ?? null,
			'typ:city' => $data['city'] ?? null,
			'typ:zip' => $data['zip'] ?? null,
		], fn($v) => $v !== null && $v !== '');

		return array_filter([
			'adb:identity' => $address ? ['typ:address' => $address] : null,
			'adb:phone' => $data['phone'] ?? null,
			'adb:email' => $data['email'] ?? null,
		], fn($v) => $v !== null && $v !== '');
	}


	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private static function stockHeader(array $data, bool $update = false): array
	{
		$vatRate = $data['vatRate'] ?? ($update ? null : 'high');
		$positive = fn(string $key): ?float => ($data[$key] ?? 0) > 0 ? (float) $data[$key] : null;

		return array_filter([
			'stk:stockType' => $update ? null : 'card',
			'stk:code' => $data['code'] ?? null,
			'stk:EAN' => $data['EAN'] ?? null,
			'stk:PLU' => ($data['PLU'] ?? 0) ?: null,
			'stk:isSales' => $data['isSales'] ?? ($update ? null : true),
			'stk:isInternet' => $data['isInternet'] ?? ($update ? null : false),
			'stk:name' => $data['name'] ?? null,
			'stk:nameComplement' => $data['nameComplement'] ?? null,
			'stk:unit' => $data['unit'] ?? ($update ? null : 'ks'),
			'stk:storage' => self::ids((string) ($data['storage'] ?? '')),
			'stk:sellingRateVAT' => $vatRate,
			'stk:purchasingRateVAT' => $vatRate,
			'stk:purchasingPrice' => $positive('purchasingPrice'),
			'stk:sellingPrice' => isset($data['sellingPrice']) ? (float) $data['sellingPrice'] : null,
			'stk:limitMin' => $positive('limitMin'),
			'stk:limitMax' => $positive('limitMax'),
			'stk:mass' => $positive('mass'),
			'stk:supplier' => ($data['supplierId'] ?? 0) > 0 ? ['typ:id' => (int) $data['supplierId']] : null,
			'stk:shortName' => $data['shortName'] ?? null,
			'stk:guaranteeType' => ($data['guarantee'] ?? 0) > 0 ? ($data['guaranteeType'] ?? 'year') : null,
			'stk:guarantee' => ($data['guarantee'] ?? 0) > 0 ? (int) $data['guarantee'] : null,
			'stk:description' => $data['description'] ?? null,
			'stk:description2' => $data['description2'] ?? null,
			'stk:note' => $data['note'] ?? null,
		], fn($v) => $v !== null && $v !== '');
	}


	/**
	 * @param array<string, mixed> $header
	 * @return array<string, mixed>|null
	 */
	private static function buildPartnerIdentity(array $header): ?array
	{
		$partnerId = (int) ($header['partnerId'] ?? 0);
		if ($partnerId > 0) {
			return ['typ:id' => $partnerId];
		}

		$name = $header['partnerName'] ?? '';
		if ($name === '') {
			return null;
		}

		return ['typ:address' => array_filter([
			'typ:company' => $name,
			'typ:ico' => $header['partnerIco'] ?? null,
			'typ:street' => $header['partnerStreet'] ?? null,
			'typ:city' => $header['partnerCity'] ?? null,
			'typ:zip' => $header['partnerZip'] ?? null,
		], fn($v) => $v !== null && $v !== '')];
	}


	/**
	 * @return array<string, string>|null
	 */
	private static function buildPaymentType(string $paymentType): ?array
	{
		if ($paymentType === '') {
			return null;
		}
		return in_array($paymentType, ['draft', 'cash', 'card', 'compensation'], true)
			? ['typ:paymentType' => $paymentType]
			: ['typ:ids' => $paymentType];
	}


	/**
	 * @param array<string, mixed> $filter
	 * @return array<string, mixed>
	 */
	private static function buildFilterData(array $filter): array
	{
		$data = [];
		foreach ($filter as $key => $value) {
			if ($value === '' || $value === null) {
				continue;
			}
			$data += match ($key) {
				'id' => ['ftr:id' => (int) $value],
				'dateFrom' => ['ftr:dateFrom' => $value],
				'dateTill' => ['ftr:dateTill' => $value],
				'lastChanges' => ['ftr:lastChanges' => $value],
				'company' => ['ftr:selectedCompanys' => ['ftr:company' => $value]],
				'ico' => ['ftr:selectedIco' => ['ftr:ico' => $value]],
				'number' => ['ftr:selectedNumbers' => ['ftr:number' => ['typ:numberRequested' => $value]]],
				'code' => ['ftr:code' => $value],
				'name' => ['ftr:name' => $value],
				'EAN' => ['ftr:EAN' => $value],
				'storage' => ['ftr:storage' => ['typ:ids' => $value]],
				'store' => ['ftr:store' => ['typ:ids' => $value]],
				'internet' => ['ftr:internet' => (bool) $value],
				default => [],
			};
		}
		return $data;
	}


	/********************* Low-level transport ****************d*g**/


	private function ensureRunning(): void
	{
		if ($this->started || $this->controller === null) {
			return;
		}

		// Set BEFORE controller->start, which polls getStatus()→httpRequest()→ensureRunning() (infinite recursion otherwise).
		$this->started = true;

		try {
			// Quick ping; if mServer already runs, skip the start.
			$this->httpRequest($this->url . '/status', []);
			return;
		} catch (\RuntimeException) {
			// not up — fall through to controller->start
		}

		try {
			fwrite(STDERR, "Pohoda mServer not running, starting...\n");
			$this->controller->start($this);
			$this->controllerStarted = true;
			fwrite(STDERR, "Pohoda mServer ready.\n");
		} catch (\Throwable $e) {
			$this->started = false;  // reset so next call retries
			throw $e;
		}
	}


	/**
	 * @param list<string> $headers
	 */
	private function httpRequest(string $url, array $headers, ?string $postBody = null): string
	{
		$this->ensureRunning();
		return $this->transport($url, $headers, $postBody);
	}


	/**
	 * One HTTP round-trip to mServer, and the only place this package touches the
	 * network. Protected so a host can swap the wire call wholesale — a Laravel
	 * app can route it through the `Http` facade and get `Http::fake()`,
	 * `Http::preventStrayRequests()` and request instrumentation over every
	 * Pohoda call for free. The default stays dependency-free curl, so the
	 * standalone MCP server still runs on nothing but ext-curl.
	 *
	 * Returns the response body; throws `\RuntimeException` on a transport
	 * failure or any non-200 status. An override must keep that contract —
	 * `Response` parsing and the mServer autostart both assume it.
	 *
	 * @param list<string> $headers
	 */
	protected function transport(string $url, array $headers, ?string $postBody = null): string
	{
		$ch = curl_init($url);
		$opts = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_TIMEOUT => $postBody !== null ? 120 : 30,
			CURLOPT_CONNECTTIMEOUT => 10,
		];
		if ($postBody !== null) {
			$opts[CURLOPT_POST] = true;
			$opts[CURLOPT_POSTFIELDS] = $postBody;
		}
		curl_setopt_array($ch, $opts);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);

		if (!is_string($response)) {
			throw new \RuntimeException('HTTP request failed: ' . $error);
		}
		if ($httpCode !== 200) {
			throw new \RuntimeException('mServer returned HTTP ' . $httpCode . ': ' . $response);
		}

		return $response;
	}


	private function httpPost(string $url, string $body): string
	{
		return $this->httpRequest($url, $this->xmlHeaders(), $body);
	}


	/**
	 * Headers sent with every XML POST. Override in a subclass to add extra
	 * headers (e.g. a proxy/authorization header) without modifying the package.
	 * @return list<string>
	 */
	protected function xmlHeaders(): array
	{
		return [
			'Content-Type: text/xml',
			'STW-Authorization: ' . $this->authHeader,
		];
	}


	/**
	 * @param list<string> $headers
	 */
	private function httpGet(string $url, array $headers = []): string
	{
		return $this->httpRequest($url, $headers);
	}
}
