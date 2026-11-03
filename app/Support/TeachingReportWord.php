<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/** Fills the approved Word template, preserving its page and paragraph formatting. */
class TeachingReportWord
{
    public static function create(string $html): string
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($dom);
        $body = '';
        foreach ($xpath->query('//div[@class="Section1"]/*') as $element) {
            if ($element->nodeName === 'table') {
                $body .= self::table($element, $xpath);
            } else {
                $title = $element->getAttribute('class') === 'document-title';
                $body .= self::paragraph(self::text($element), $title, $title ? 'center' : 'left', $title ? 30 : 26);
            }
        }
        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$body.'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>'
            .'<w:pgMar w:top="1134" w:right="850" w:bottom="1020" w:left="1134"/>'
            .'</w:sectPr></w:body></w:document>';

        $path = tempnam(sys_get_temp_dir(), 'teaching-docx-');
        if ($path === false) {
            throw new RuntimeException('Cannot create Word document temporary file.');
        }
        $zip = new ZipArchive();
        try {
            if (! copy(resource_path('templates/teaching-report.docx'), $path)) {
                throw new RuntimeException('Cannot copy Word report template.');
            }
            if ($zip->open($path) !== true) {
                throw new RuntimeException('Cannot create Word document archive.');
            }
            $zip->addFromString('word/document.xml', self::fillTemplate($zip->getFromName('word/document.xml'), $document, $xpath));
            if (! $zip->close()) {
                throw new RuntimeException('Cannot finish Word document archive.');
            }
        } catch (\Throwable $exception) {
            if (is_file($path)) unlink($path);
            throw $exception;
        }

        return $path;
    }

    private static function fillTemplate(string $template, string $generated, DOMXPath $html): string
    {
        $dom = new DOMDocument();
        $dom->loadXML($template, LIBXML_NONET);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $source = new DOMDocument();
        $source->loadXML($generated, LIBXML_NONET);
        $data = new DOMXPath($source);
        $data->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Keep the approved institutional header, signatures, section and styles intact.
        self::setParagraph($xpath->query('(//w:tbl)[1]/w:tr[2]/w:tc[2]/w:p')->item(0),
            self::text($html->query('//*[@class="issued-at"]')->item(0)));
        self::setParagraph($xpath->query('/w:document/w:body/w:p[1]')->item(0),
            self::text($html->query('//*[@class="document-title"]')->item(0)));
        self::setParagraph($xpath->query('/w:document/w:body/w:p[2]')->item(0),
            self::text($html->query('//*[@class="teacher-info"]')->item(0)));

        $table = $xpath->query('(//w:tbl)[2]')->item(0);
        $newTable = $data->query('(//w:tbl)[2]')->item(0);
        $center = $data->query('./w:tblGrid/w:gridCol', $newTable)->length === 5;
        if ($center) {
            // Preserve the exact column widths, header row and total row from the sample.
            $rows = iterator_to_array($xpath->query('./w:tr', $table));
            $total = $rows[count($rows) - 1];
            $newRows = iterator_to_array($data->query('./w:tr', $newTable));
            for ($i = 1; $i < count($rows) - 1; $i++) $table->removeChild($rows[$i]);
            for ($i = 1; $i < count($newRows) - 1; $i++) {
                $table->insertBefore($dom->importNode($newRows[$i], true), $total);
            }
            self::setParagraph($xpath->query('./w:tc[2]/w:p', $total)->item(0),
                $data->evaluate('string(./w:tc[2]/w:p)', $newRows[count($newRows) - 1]));
        } else {
            // Training uses six columns; retain the template's table styling.
            $properties = $dom->importNode($table->getElementsByTagNameNS(self::WORD_NS, 'tblPr')->item(0), true);
            $replacement = $dom->importNode($newTable, true);
            $replacement->replaceChild($properties, $replacement->firstChild);
            $table->parentNode->replaceChild($replacement, $table);
        }

        return $dom->saveXML();
    }

    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private static function setParagraph(DOMElement $paragraph, string $text): void
    {
        $dom = $paragraph->ownerDocument;
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NS);
        $style = $xpath->query('./w:r/w:rPr', $paragraph)->item(0)?->cloneNode(true);
        foreach (iterator_to_array($paragraph->childNodes) as $child) {
            if ($child->localName !== 'pPr') $paragraph->removeChild($child);
        }
        foreach (explode("\n", trim($text)) as $index => $line) {
            $run = $dom->createElementNS(self::WORD_NS, 'w:r');
            if ($style) $run->appendChild($style->cloneNode(true));
            if ($index) $run->appendChild($dom->createElementNS(self::WORD_NS, 'w:br'));
            $node = $dom->createElementNS(self::WORD_NS, 'w:t');
            $node->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
            $node->appendChild($dom->createTextNode($line));
            $run->appendChild($node);
            $paragraph->appendChild($run);
        }
    }

    private static function table(DOMElement $table, DOMXPath $xpath): string
    {
        $schedule = $table->getAttribute('class') === 'schedule';
        $signatures = $table->getAttribute('class') === 'signatures';
        $rows = $xpath->query('./tr | ./thead/tr | ./tbody/tr', $table);
        $firstCells = $xpath->query('./th | ./td', $rows->item(0));
        $widths = [];
        foreach ($firstCells as $cell) {
            preg_match('/width:\s*(\d+)%/', $cell->getAttribute('style'), $match);
            $widths[] = isset($match[1]) ? (int) round(9922 * (int) $match[1] / 100) : (int) (9922 / $firstCells->length);
        }
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblLayout w:type="fixed"/>';
        if ($schedule) {
            $xml .= '<w:tblBorders>';
            foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $edge) {
                $xml .= '<w:'.$edge.' w:val="single" w:sz="6" w:color="000000"/>';
            }
            $xml .= '</w:tblBorders>';
        }
        $xml .= '<w:tblCellMar><w:top w:w="80" w:type="dxa"/><w:left w:w="80" w:type="dxa"/>'
            .'<w:bottom w:w="80" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>';
        foreach ($widths as $width) $xml .= '<w:gridCol w:w="'.$width.'"/>';
        $xml .= '</w:tblGrid>';
        foreach ($rows as $row) {
            $isHeader = $xpath->query('./th', $row)->length > 0;
            $xml .= '<w:tr><w:trPr><w:cantSplit/>'.($isHeader ? '<w:tblHeader/>' : '')
                .($signatures ? '<w:trHeight w:val="1800" w:hRule="atLeast"/>' : '').'</w:trPr>';
            $column = 0;
            foreach ($xpath->query('./td | ./th', $row) as $cell) {
                $span = max(1, (int) $cell->getAttribute('colspan'));
                $width = array_sum(array_slice($widths, $column, $span));
                $column += $span;
                $class = $cell->getAttribute('class');
                $alignment = ! $schedule || $isHeader || in_array($class, ['center', 'total-label']) ? 'center'
                    : (in_array($class, ['right', 'total-value']) ? 'right' : 'left');
                $bold = ! $schedule || $isHeader || str_starts_with($class, 'total-');
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'
                    .($span > 1 ? '<w:gridSpan w:val="'.$span.'"/>' : '')
                    .'<w:vAlign w:val="'.($schedule ? 'center' : 'top').'"/></w:tcPr>'
                    .self::paragraph(self::text($cell), $bold, $alignment, $schedule ? 24 : 22).'</w:tc>';
            }
            $xml .= '</w:tr>';
        }

        return $xml.'</w:tbl>'.self::paragraph('');
    }

    private static function paragraph(string $text, bool $bold = false, string $alignment = 'left', int $size = 26): string
    {
        $lines = explode("\n", trim($text));
        $runs = '';
        foreach ($lines as $index => $line) {
            $runs .= '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
                .($bold ? '<w:b/>' : '').'<w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/></w:rPr>'
                .($index ? '<w:br/>' : '').'<w:t xml:space="preserve">'
                .htmlspecialchars($line, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</w:t></w:r>';
        }

        return '<w:p><w:pPr><w:jc w:val="'.$alignment.'"/><w:spacing w:after="120" w:line="276" w:lineRule="auto"/></w:pPr>'.$runs.'</w:p>';
    }

    private static function text(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) return preg_replace('/\s+/u', ' ', $node->textContent);
        if ($node->nodeName === 'br') return "\n";
        $text = '';
        foreach ($node->childNodes as $child) {
            $text .= self::text($child);
            if (in_array($child->nodeName, ['div', 'p'])) $text .= "\n";
        }

        return trim($text);
    }
}
