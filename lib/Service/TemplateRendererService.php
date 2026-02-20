<?php

namespace OCA\LetterMaker\Service;

class TemplateRendererService
{
    public function __construct(private TemplateService $templateService)
    {
    }

    public function render(array $data, string $templateName): string
    {
        $templatePath = $this->templateService->getTemplatePath($templateName);
        $template = @file_get_contents($templatePath);

        if ($template === false) {
            throw new \RuntimeException('Template file not found: ' . $templatePath);
        }

        $letterTextHtml = (string) ($data['lettertext_html'] ?? '');
        if ($letterTextHtml === '') {
            $plainText = (string) ($data['lettertext'] ?? '');
            $plainText = $this->sanitizeText($plainText);
            $letterTextHtml = nl2br(htmlspecialchars($plainText, ENT_QUOTES, 'UTF-8'));
        } else {
            $letterTextHtml = $this->sanitizeHtmlForPdf($letterTextHtml);
        }

        $placeholders = [
            'recipient_name',
            'recipient_info',
            'recipient_street',
            'recipient_zip',
            'recipient_city',
            'sender_name',
            'sender_info',
            'sender_street',
            'sender_zip',
            'sender_city',
            'date',
            'subject',
        ];

        foreach ($placeholders as $placeholder) {
            $value = $data[$placeholder] ?? '';
            if (is_string($value)) {
                $value = $this->sanitizeText($value);
                $value = trim($value);
            }

            if ($value === '' || $value === null) {
                $template = $this->removePlaceholderLine($template, $placeholder);
                $safeValue = '';
            } else {
                $safeValue = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            }

            $template = str_replace('{' . $placeholder . '}', $safeValue, $template);
        }

        $template = str_replace('{lettertext_html}', $letterTextHtml, $template);
        $template = str_replace('{stamp}', '', $template);

        return $template;
    }

    private function removePlaceholderLine(string $template, string $placeholder): string
    {
        $token = preg_quote('{' . $placeholder . '}', '/');
        $pattern = '/^[ \t]*(<br\s*\/?>\s*)?' . $token . '(\s*<br\s*\/?>)?[ \t]*\R?/m';

        return preg_replace($pattern, '', $template) ?? $template;
    }

    private function sanitizeText(string $value): string
    {
        $value = str_replace("\0", '', $value);
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? '';
    }

    private function sanitizeHtmlForPdf(string $html): string
    {
        $html = $this->sanitizeText($html);
        $html = preg_replace('/<\s*(script|style)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $html) ?? '';
        $html = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html) ?? '';
        return $html;
    }
}
