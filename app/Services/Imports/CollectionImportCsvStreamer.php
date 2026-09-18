<?php

namespace App\Services\Imports;

class CollectionImportCsvStreamer
{
    /**
     * @param  callable(array<int, string>): void  $onHeader
     * @param  callable(array<int, string|null>, int): (bool|void)  $onRow
     */
    public function process(string $absolutePath, callable $onHeader, callable $onRow): void
    {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open CSV file [{$absolutePath}].");
        }

        try {
            $header = $this->readCsvRow($handle);

            if ($header === null) {
                return;
            }

            $header = $this->normalizeHeader($header);
            $onHeader($header);

            $lineNumber = 1;

            while (($row = $this->readCsvRow($handle)) !== null) {
                $lineNumber++;
                if ($onRow($row, $lineNumber) === false) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<int, string|null>|null
     */
    protected function readCsvRow($handle): ?array
    {
        $row = fgetcsv($handle, 0, ',', '"', '\\');

        if ($row === false) {
            return null;
        }

        return $row;
    }

    /**
     * @param  array<int, string|null>  $header
     * @return array<int, string>
     */
    protected function normalizeHeader(array $header): array
    {
        return array_map(function ($value, int $index): string {
            $value = (string) $value;

            if ($index === 0) {
                $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
            }

            return trim($value);
        }, $header, array_keys($header));
    }
}
