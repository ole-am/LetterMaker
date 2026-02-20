<?php

namespace OCA\LetterMaker\Service;

use Mpdf\Mpdf;

class PdfService
{
    public function __construct(
        private TemplateService $templateService,
        private TemplateRendererService $templateRendererService,
        private StampService $stampService,
        private TempDirService $tempDirService
    ) {
    }

    public function generateLetter(array $data, string $templateName): string
    {
        if (!class_exists(Mpdf::class)) {
            $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
            if (is_file($autoloadPath)) {
                require_once $autoloadPath;
            }
        }
        if (!class_exists(Mpdf::class)) {
            throw new \RuntimeException('mPDF library not available.');
        }

        $tempInfo = $this->tempDirService->createTempDir();
        $tempDir = $tempInfo['path'];
        $tempFolderName = $tempInfo['folder'];
        $stampPath = null;
        $cleanupStamp = false;

        try {
            $stampInfo = $this->stampService->prepareStampPdf($data['stamp_pdf'] ?? null, $tempDir);
            $stampPath = $stampInfo['path'] ?? null;
            $cleanupStamp = $stampInfo['cleanup'] ?? false;

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => $this->templateService->getTemplateFormat($templateName),
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'tempDir' => $tempDir,
            ]);

            $html = $this->templateRendererService->render($data, $templateName);
            $mpdf->WriteHTML($html);

            if ($stampPath !== null && $this->templateService->isEnvelopeTemplate($templateName)) {
                $this->stampService->placeStampPdf($mpdf, $templateName, $stampPath);
            }

            return $mpdf->Output('', 'S');
        } finally {
            if ($cleanupStamp && $stampPath !== null && is_file($stampPath)) {
                @unlink($stampPath);
            }
            $this->tempDirService->deleteTempDir($tempFolderName);
        }
    }
}
