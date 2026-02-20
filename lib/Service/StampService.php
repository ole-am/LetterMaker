<?php

namespace OCA\LetterMaker\Service;

use Mpdf\Mpdf;

class StampService
{
    public function __construct(private TemplateService $templateService)
    {
    }

    public function prepareStampPdf($stampUpload, string $tempDir): array
    {
        if (empty($stampUpload)) {
            return [
                'path' => null,
                'cleanup' => false,
            ];
        }

        if (is_array($stampUpload)) {
            $tmpPath = $stampUpload['tmp_name'] ?? '';
            $errorCode = (int) ($stampUpload['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($errorCode !== UPLOAD_ERR_OK) {
                throw new \RuntimeException('Stamp PDF upload failed with error code: ' . $errorCode);
            }
            if ($tmpPath === '' || !is_file($tmpPath)) {
                throw new \RuntimeException('Stamp PDF upload missing temporary file.');
            }

            $pdfPath = $tempDir . '/stamp_' . bin2hex(random_bytes(8)) . '.pdf';

            if (is_uploaded_file($tmpPath)) {
                if (!move_uploaded_file($tmpPath, $pdfPath)) {
                    throw new \RuntimeException('Failed to move uploaded stamp PDF.');
                }
            } else {
                if (!copy($tmpPath, $pdfPath)) {
                    throw new \RuntimeException('Failed to copy stamp PDF.');
                }
            }

            return [
                'path' => $pdfPath,
                'cleanup' => true,
            ];
        }

        if (is_string($stampUpload) && is_file($stampUpload)) {
            return [
                'path' => $stampUpload,
                'cleanup' => false,
            ];
        }

        return [
            'path' => null,
            'cleanup' => false,
        ];
    }

    public function placeStampPdf(Mpdf $mpdf, string $templateName, string $stampPath): void
    {
        $position = $this->templateService->getStampPlacement($templateName);
        if ($position === null) {
            return;
        }

        $pageCount = $mpdf->SetSourceFile($stampPath);
        if ($pageCount < 1) {
            throw new \RuntimeException('Stamp PDF contains no pages.');
        }

        $templateId = $mpdf->ImportPage(1);
        $templateSize = $mpdf->getTemplateSize($templateId);

        if (empty($templateSize['width']) || empty($templateSize['height'])) {
            throw new \RuntimeException('Stamp PDF has invalid template dimensions.');
        }

        $scale = min(
            $position['w'] / $templateSize['width'],
            $position['h'] / $templateSize['height']
        );

        $targetW = $templateSize['width'] * $scale;
        $targetH = $templateSize['height'] * $scale;
        $targetX = $position['x'] + (($position['w'] - $targetW) / 2);
        $targetY = $position['y'] + (($position['h'] - $targetH) / 2);
        $centerX = $position['x'] + ($position['w'] / 2);
        $centerY = $position['y'] + ($position['h'] / 2);

        $rotation = (float) ($position['rotation'] ?? 0.0);
        if ($rotation !== 0.0) {
            $mpdf->Rotate($rotation, $centerX, $centerY);
        }
        $mpdf->UseTemplate($templateId, $targetX, $targetY, $targetW, $targetH);
        if ($rotation !== 0.0) {
            $mpdf->Rotate(0);
        }
    }
}
