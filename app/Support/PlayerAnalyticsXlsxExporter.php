<?php

namespace App\Support;

use App\Models\Player;
use Illuminate\Support\Enumerable;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlayerAnalyticsXlsxExporter
{
    /** @param Enumerable<int, Player> $players */
    public static function download(Enumerable $players, string $filters): StreamedResponse
    {
        $fileName = 'top-players-'.now()->format('Y-m-d-H-i-s').'.xlsx';

        return response()->streamDownload(
            fn () => self::write($players, $filters, 'php://output'),
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /** @param Enumerable<int, Player> $players */
    public static function write(Enumerable $players, string $filters, string $path): void
    {
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues([$filters]));
        $writer->addRow(Row::fromValues(['Ник', 'LTV всего']));

        foreach ($players as $player) {
            $writer->addRow(Row::fromValues([
                $player->nickname,
                (float) ($player->ltv_total ?? 0),
            ]));
        }

        $writer->close();
    }
}
