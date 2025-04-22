<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Storage;

trait CSVExportTrait {
    public function execute(string $exportName, array $arParams = [], string $fileName = null, int $chunkSize = 500, int $timeLimit = 10)
    {
        if(empty($fileName)){
            $date = date('Y-m-d_H-i-s');
            $fileName = "{$exportName}_{$date}.csv";
        }
        $sessionId = session()->getId();
        $tmpFileName = "{$exportName}_{$sessionId}.csv";

        $step = session()->get('csv_' . $exportName . '_export_step', 1);

        if ($step === 1) {
            $totalCount = $this->getCount($arParams);
            session()->put('csv_'. $exportName .'_export_total', $totalCount);
        } else {
            $totalCount = session()->get('csv_'.$exportName.'_export_total', 0);
        }

        $storagePath = storage_path("app/exports/{$tmpFileName}");
        Storage::makeDirectory('exports');
        $file = fopen($storagePath, $step === 1 ? 'w' : 'a');

        $startTime = microtime(true);
        $currentStep = $step;

        $downloadFile = false;
        while (true) {
            $offset = ($currentStep - 1) * $chunkSize;
            $entities = $this->query($chunkSize, $offset, $arParams);

            if ($entities->isEmpty()) {
                $downloadFile = true;
                break;
            }

            $arRows = $this->getRows($entities);

            if ($currentStep === 1) {
                fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, $this->getHeadings(), ';');
            }

            foreach ($arRows as $row) {
                fputcsv($file, $row, ';');
            }

            $currentStep++;

            // Прекращаем, если прошло более $timeLimit секунд
            if ((microtime(true) - $startTime) > $timeLimit) {
                break;
            }
        }

        fclose($file);

        $nextOffset = ($currentStep - 1) * $chunkSize;

        if (session()->get('csv_'.$exportName.'_download', '0') == '1') {
            session()->forget(['csv_' . $exportName . '_export_step', 'csv_' . $exportName . '_export_total', 'csv_'.$exportName.'_download']);
            return response()->download($storagePath, $fileName)->deleteFileAfterSend()->sendHeaders();
        }

        if($downloadFile){
            session()->put('csv_'.$exportName.'_download', '1');
        }

        session()->put('csv_' . $exportName . '_export_step', $currentStep);
        if($nextOffset >= $totalCount){
            $nextOffset = $totalCount;
        }
        return response("Экспорт в csv: {$nextOffset} из {$totalCount} записей выгружено.<br/>Файл будет загружен автоматически по окончанию выгрузки. <script>setTimeout(() => {window.location.reload()}, 2000)</script>");
    }
}
