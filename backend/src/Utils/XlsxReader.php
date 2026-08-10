<?php

declare(strict_types=1);

namespace App\Utils;

use RuntimeException;
use SimpleXMLElement;

/**
 * Minimal, dependency-free reader for modern Excel files (`.xlsx`).
 *
 * An `.xlsx` document is a ZIP archive containing several XML parts. This reader
 * only needs the phone-book basics: shared strings, the workbook/relationship
 * map and the first worksheet. It returns the raw cell matrix (header row
 * included) so callers can map columns themselves.
 *
 * Requires the `zip` and `simplexml` extensions.
 */
final class XlsxReader
{
    private const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const NS_SPREADSHEET = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * Parse the first worksheet of an `.xlsx` file into a row/column matrix.
     *
     * @return list<list<string|null>> each row is a list of cell values (null
     *                                for empty cells), cell order matches column
     *                                order (gaps are filled with null)
     * @throws RuntimeException when the file is not a readable `.xlsx`
     */
    public static function parse(string $filePath): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new RuntimeException('The PHP "zip" extension is required to read Excel files.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Could not open the file (invalid or corrupted .xlsx archive).');
        }

        try {
            $sharedStrings = self::readSharedStrings($zip);
            $sheetPath = self::firstSheetPath($zip);

            return self::readSheet($zip, $sheetPath, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    /**
     * Read `xl/sharedStrings.xml` into a list of plain-text strings.
     *
     * @return list<string>
     */
    private static function readSharedStrings(\ZipArchive $zip): array
    {
        $xml = self::entryXml($zip, 'xl/sharedStrings.xml');
        if ($xml === null) {
            return [];
        }

        $strings = [];
        $texts = $xml->xpath('//' . self::prefix('si'));
        foreach ($texts as $si) {
            $parts = [];
            foreach ($si->xpath('.//' . self::prefix('t')) as $t) {
                $parts[] = (string) $t;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    /**
     * Resolve the worksheet XML path of the first sheet using the workbook and
     * its relationship file, falling back to the conventional default path.
     */
    private static function firstSheetPath(\ZipArchive $zip): string
    {
        $workbookXml = self::entryXml($zip, 'xl/workbook.xml');
        $relsXml = self::entryXml($zip, 'xl/_rels/workbook.xml.rels');

        if ($workbookXml !== null && $relsXml !== null) {
            $sheet = $workbookXml->sheet ?? null;
            $relId = $sheet !== null ? self::attribute($sheet, 'id', self::NS_REL) : null;

            $target = null;
            if ($relId !== null) {
                foreach ($relsXml->Relationship as $rel) {
                    if (self::attribute($rel, 'Id') === $relId) {
                        $target = self::attribute($rel, 'Target');
                        break;
                    }
                }
            }

            if ($target !== null) {
                if (str_starts_with($target, '/')) {
                    $target = ltrim($target, '/');
                } elseif (!str_starts_with($target, 'xl/')) {
                    $target = 'xl/' . $target;
                }

                if ($zip->locateName($target) !== false) {
                    return $target;
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * Read one worksheet into a cell matrix.
     *
     * @param list<string> $sharedStrings
     * @return list<list<string|null>>
     */
    private static function readSheet(\ZipArchive $zip, string $sheetPath, array $sharedStrings): array
    {
        $xml = self::entryXml($zip, $sheetPath);
        if ($xml === null) {
            return [];
        }

        $rows = [];
        foreach ($xml->xpath('//' . self::prefix('sheetData') . '/' . self::prefix('row')) as $row) {
            $cells = self::readRow($row, $sharedStrings);
            if ($cells !== []) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    /**
     * @param list<string> $sharedStrings
     * @return list<string|null>
     */
    private static function readRow(SimpleXMLElement $row, array $sharedStrings): array
    {
        $cells = [];

        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            $index = self::columnIndex($ref);
            if ($index === null) {
                continue;
            }

            $value = self::cellValue($cell, $sharedStrings);
            $cells[$index] = $value;
        }

        ksort($cells);

        $result = [];
        $max = $cells === [] ? -1 : (int) max(array_keys($cells));
        for ($i = 0; $i <= $max; $i++) {
            $result[] = $cells[$i] ?? null;
        }

        return $result;
    }

    /**
     * @param list<string> $sharedStrings
     */
    private static function cellValue(SimpleXMLElement $cell, array $sharedStrings): ?string
    {
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            return self::inlineText($cell);
        }

        if ($type === 's') {
            $value = $cell->v !== null ? (int) trim((string) $cell->v) : null;
            return $value !== null && isset($sharedStrings[$value]) ? $sharedStrings[$value] : null;
        }

        return $cell->v !== null ? (string) $cell->v : null;
    }

    private static function inlineText(SimpleXMLElement $cell): string
    {
        $parts = [];
        foreach ($cell->xpath('.//' . self::prefix('t')) as $t) {
            $parts[] = (string) $t;
        }

        return implode('', $parts);
    }

    /**
     * Convert a cell reference such as "B7" or "AA1" into a 0-based column index.
     */
    private static function columnIndex(string $ref): ?int
    {
        if ($ref === '' || !preg_match('/^([A-Za-z]+)[0-9]+$/', $ref, $m)) {
            return null;
        }

        $letters = str_split(strtoupper($m[1]));
        $index = 0;
        foreach ($letters as $letter) {
            $index = $index * 26 + (ord($letter) - ord('A') + 1);
        }

        return $index - 1;
    }

    private static function entryXml(\ZipArchive $zip, string $path): ?SimpleXMLElement
    {
        $raw = $zip->getFromName($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        $xml = simplexml_load_string($raw);
        return $xml === false ? null : $xml;
    }

    private static function attribute(SimpleXMLElement $node, string $name, ?string $namespace = null): ?string
    {
        $attrs = $namespace !== null ? $node->attributes($namespace) : $node->attributes();
        $value = $attrs !== null && isset($attrs[$name]) ? (string) $attrs[$name] : null;

        return $value !== '' ? $value : null;
    }

    private static function prefix(string $name): string
    {
        return '*[local-name()="' . $name . '"]';
    }
}