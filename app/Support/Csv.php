<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class Csv
{
    /**
     * @param  array<string, callable|string>  $columns  heading => attribute or closure($row)
     */
    public static function download(string $filename, iterable $rows, array $columns): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, array_keys($columns));
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($c) => is_callable($c) ? $c($row) : data_get($row, $c), array_values($columns)));
            }
            fclose($out);
        }, $filename.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
