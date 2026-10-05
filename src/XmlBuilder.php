<?php declare(strict_types=1);

namespace DG\Pohoda;

use function is_array, is_bool, is_int, is_scalar, is_string, sprintf;


/**
 * Builds Pohoda XML requests as plain strings.
 * Data is specified as nested PHP arrays, written recursively.
 *
 * Deliberately not XMLWriter: the static PHP that ships inside the Accounting
 * Bridge is built without ext-xmlwriter, and every mServer request died there
 * with `Class "XMLWriter" not found`. The escaping below is libxml's, so the
 * bytes match what XMLWriter produced (tests/XmlBuilder.phpt).
 */
final class XmlBuilder
{
	private const Namespaces = [
		'dat' => 'http://www.stormware.cz/schema/version_2/data.xsd',
		'typ' => 'http://www.stormware.cz/schema/version_2/type.xsd',
		'ftr' => 'http://www.stormware.cz/schema/version_2/filter.xsd',
		'inv' => 'http://www.stormware.cz/schema/version_2/invoice.xsd',
		'ord' => 'http://www.stormware.cz/schema/version_2/order.xsd',
		'adb' => 'http://www.stormware.cz/schema/version_2/addressbook.xsd',
		'stk' => 'http://www.stormware.cz/schema/version_2/stock.xsd',
		'prn' => 'http://www.stormware.cz/schema/version_2/print.xsd',
		'lst' => 'http://www.stormware.cz/schema/version_2/list.xsd',
		'lStk' => 'http://www.stormware.cz/schema/version_2/list_stock.xsd',
		'lAdb' => 'http://www.stormware.cz/schema/version_2/list_addBook.xsd',
		'lCon' => 'http://www.stormware.cz/schema/version_2/list_contract.xsd',
		'lCen' => 'http://www.stormware.cz/schema/version_2/list_centre.xsd',
		'lAcv' => 'http://www.stormware.cz/schema/version_2/list_activity.xsd',
	];


	public function __construct(
		private readonly string $ico,
		private readonly string $application = 'MCP Server',
	) {
	}


	/**
	 * Build complete dataPack XML with one dataPackItem.
	 * @param array<string, mixed> $data
	 * @param array<string, string> $rootAttrs
	 */
	public function build(
		string $rootElement,
		string $version,
		array $data,
		string $note = '',
		array $rootAttrs = [],
	): string
	{
		$id = sprintf('%08d', random_int(1, 99_999_999));

		$packAttrs = ['id' => $id, 'ico' => $this->ico, 'application' => $this->application, 'version' => '2.0', 'note' => $note];
		foreach (self::Namespaces as $prefix => $uri) {
			$packAttrs['xmlns:' . $prefix] = $uri;
		}

		// UTF-8 bytes, so the declaration must say UTF-8 — Pohoda accepts
		// UTF-8-declared dataPacks (mServer i pohoda.exe /XML); a Windows-1250
		// declaration over UTF-8 bytes breaks file consumers.
		return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. self::element('dat:dataPack', $packAttrs,
				self::element('dat:dataPackItem', ['id' => $id, 'version' => '2.0'],
					self::element($rootElement, ['version' => $version] + $rootAttrs, $this->writeData($data))))
			. "\n";
	}


	/**
	 * Build dataPack XML from raw inner XML string (for sendRawXml).
	 */
	public function buildRaw(string $innerXml, string $note = ''): string
	{
		$id = sprintf('%08d', random_int(1, 99_999_999));
		return '<?xml version="1.0" encoding="UTF-8"?>'
			. '<dat:dataPack'
			. ' xmlns:dat="http://www.stormware.cz/schema/version_2/data.xsd"'
			. ' xmlns:typ="http://www.stormware.cz/schema/version_2/type.xsd"'
			. ' id="' . $id . '"'
			. ' ico="' . htmlspecialchars($this->ico) . '"'
			. ' application="' . htmlspecialchars($this->application) . '"'
			. ' version="2.0"'
			. ' note="' . htmlspecialchars($note) . '">'
			. '<dat:dataPackItem id="' . $id . '" version="2.0">'
			. $innerXml
			. '</dat:dataPackItem>'
			. '</dat:dataPack>';
	}


	/**
	 * Recursively write nested data structure as XML elements.
	 * @param array<int|string, mixed> $data
	 */
	private function writeData(array $data): string
	{
		$xml = '';
		foreach ($data as $key => $value) {
			if ($value === null || $value === '') {
				continue;
			}

			// Numeric key = wrapper array, recurse
			if (is_int($key)) {
				if (is_array($value)) {
					$xml .= $this->writeData($value);
				}
				continue;
			}

			$attrs = [];
			$content = '';
			if (is_array($value)) {
				// Check for '@attr' keys = attributes
				foreach ($value as $k => $v) {
					if (is_string($k) && str_starts_with($k, '@') && is_scalar($v)) {
						$attrs[substr($k, 1)] = (string) $v;
					}
				}
				// Write child elements (skip @attr keys)
				$children = array_filter($value, fn($k) => !is_string($k) || !str_starts_with($k, '@'), ARRAY_FILTER_USE_KEY);
				if ($children) {
					$content = $this->writeData($children);
				}
			} elseif ($value instanceof \DateTimeInterface) {
				$content = self::escapeText($value->format('Y-m-d'));
			} elseif (is_bool($value)) {
				$content = $value ? 'true' : 'false';
			} elseif (is_scalar($value)) {
				$content = self::escapeText((string) $value);
			}

			$xml .= self::element($key, $attrs, $content);
		}

		return $xml;
	}


	/**
	 * One element; empty content self-closes, as XMLWriter's endElement() did.
	 * @param array<string, string> $attrs
	 */
	private static function element(string $name, array $attrs, string $content): string
	{
		$xml = '<' . $name;
		foreach ($attrs as $k => $v) {
			$xml .= ' ' . $k . '="' . self::escapeAttribute((string) $v) . '"';
		}

		return $content === '' ? $xml . '/>' : $xml . '>' . $content . '</' . $name . '>';
	}


	/** libxml's xmlEncodeSpecialChars, which XMLWriter::text() used. */
	private static function escapeText(string $text): string
	{
		return strtr($text, ['&' => '&amp;', '<' => '&lt;', '>' => '&gt;', '"' => '&quot;', "\r" => '&#13;']);
	}


	/** libxml's attribute serialisation, which XMLWriter::writeAttribute() used. */
	private static function escapeAttribute(string $text): string
	{
		return strtr($text, ['&' => '&amp;', '<' => '&lt;', '>' => '&gt;', '"' => '&quot;', "\n" => '&#10;', "\r" => '&#13;', "\t" => '&#9;']);
	}
}
