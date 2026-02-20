<?php

namespace OCA\LetterMaker\Controller;

use OCA\LetterMaker\Service\PdfService;
use OCA\LetterMaker\Service\TemplateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

class LetterController extends Controller
{

    private PdfService $pdfService;
    private TemplateService $templateService;

    public function __construct(
        string $appName,
        IRequest $request,
        PdfService $pdfService,
        TemplateService $templateService
    ) {
        parent::__construct($appName, $request);
        $this->pdfService = $pdfService;
        $this->templateService = $templateService;
    }

    /**
     * @NoAdminRequired
     */
    public function generate(): Response
    {
        $stampPdf = $this->request->getUploadedFile('stamp_pdf');
        $data = [
            'recipient_name' => $this->request->getParam('recipient_name'),
            'recipient_info' => $this->request->getParam('recipient_info'),
            'recipient_street' => $this->request->getParam('recipient_street'),
            'recipient_zip' => $this->request->getParam('recipient_zip'),
            'recipient_city' => $this->request->getParam('recipient_city'),
            'sender_name' => $this->request->getParam('sender_name'),
            'sender_info' => $this->request->getParam('sender_info'),
            'sender_street' => $this->request->getParam('sender_street'),
            'sender_zip' => $this->request->getParam('sender_zip'),
            'sender_city' => $this->request->getParam('sender_city'),
            'date' => $this->request->getParam('date'),
            'subject' => $this->request->getParam('subject'),
            'lettertext' => $this->request->getParam('lettertext'),
            'lettertext_html' => $this->request->getParam('lettertext_html'),
            'template_name' => $this->request->getParam('template_name'),
            'stamp_pdf' => $stampPdf,
        ];

        try {
            $templateName = $data['template_name'] ?: 'letter';
            $pdfContent = $this->pdfService->generateLetter($data, $templateName);
            $filename = 'Letter_' . date('d-m-Y_H-i-s') . '.pdf';

            return new DataDownloadResponse(
                $pdfContent,
                $filename,
                'application/pdf'
            );
        } catch (\Exception $e) {
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     */
    public function templates(): JSONResponse
    {
        try {
            $templates = $this->templateService->getTemplates();
            return new JSONResponse(['templates' => $templates]);
        } catch (\Exception $e) {
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @AdminRequired
     */
    public function deleteTemplate(): JSONResponse
    {
        $filename = (string) $this->request->getParam('filename', '');
        if ($filename === '') {
            return new JSONResponse(
                ['error' => 'Missing template filename.'],
                Http::STATUS_BAD_REQUEST
            );
        }

        if (!$this->templateService->deleteTemplate($filename)) {
            return new JSONResponse(
                ['error' => 'Template not found or could not be deleted.'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse(['status' => 'ok']);
    }

    /**
     * @AdminRequired
     */
    public function uploadTemplate(): JSONResponse
    {
        $templateHtml = $this->request->getUploadedFile('template_html');
        $templateTarget = (string) $this->request->getParam('template_target', '');

        try {
            $filename = $this->templateService->uploadTemplateHtml($templateHtml, $templateTarget);
            return new JSONResponse([
                'status' => 'ok',
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @AdminRequired
     */
    public function resetTemplates(): JSONResponse
    {
        try {
            $this->templateService->resetTemplates();
            return new JSONResponse(['status' => 'ok']);
        } catch (\Exception $e) {
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @AdminRequired
     */
    public function downloadTemplate(): Response
    {
        $filename = (string) $this->request->getParam('filename', '');

        try {
            $template = $this->templateService->getTemplateDownload($filename);
            return new DataDownloadResponse(
                $template['content'],
                $template['filename'],
                'text/html; charset=UTF-8'
            );
        } catch (\Exception $e) {
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        }
    }

}
