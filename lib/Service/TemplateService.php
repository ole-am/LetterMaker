<?php

namespace OCA\LetterMaker\Service;

use OCP\IConfig;

class TemplateService
{
    private const APP_ID = 'lettermaker';
    private const LETTER_TEMPLATE = 'letter';
    private const LEGACY_LETTER_TEMPLATE = 'din-5008-letter';

    public function __construct(private IConfig $config)
    {
    }

    /**
     * Extract page format from template's @page CSS rule.
     *
     * @param string $templateName Template name without extension (e.g., 'din-long-envelope')
     * @return array|string mPDF format: [width, height] in mm or string like 'A4'
     * @throws \RuntimeException if template not found or format cannot be determined
     */
    public function getTemplateFormat(string $templateName): array|string
    {
        $templatePath = $this->resolveTemplatePath($templateName);
        $html = $templatePath ? @file_get_contents($templatePath) : false;

        if ($html === false) {
            throw new \RuntimeException('Template file not found: ' . $templatePath);
        }

        // Extract CSS from <style> tags
        $css = $this->extractCss($html);

        // Parse @page rule and extract size
        $size = $this->extractPageSize($css);

        if ($size !== null) {
            return $size;
        }

        // Fallback if no @page size found
        return $this->getDefaultFormat($css);
    }

    /**
     * Extract all CSS from <style> tags.
     */
    private function extractCss(string $html): string
    {
        $css = '';
        if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $matches)) {
            $css = implode("\n", $matches[1]);
        }
        return $css;
    }

    /**
     * Parse @page rule and extract size value.
     * Supports formats like:
     * - size: 220mm 110mm
     * - size: 162mm 114mm
     * - size: A4
     * - size: 220mm 110mm landscape
     */
    private function extractPageSize(string $css): array|string|null
    {
        // Match @page { ... size: ... ... }
        if (preg_match('/@page\s*\{[^}]*size\s*:\s*([^;}\n]+)/i', $css, $matches)) {
            $sizeValue = trim($matches[1]);

            // Remove trailing semicolon if present
            $sizeValue = rtrim($sizeValue, ';');

            // Check if it's a named format (A4, A3, etc.)
            if (preg_match('/^[A-Z]\d+$/i', trim(explode(' ', $sizeValue)[0]))) {
                return trim(explode(' ', $sizeValue)[0]);
            }

            // Parse numeric dimensions (e.g., "220mm 110mm" or "220mm 110mm landscape")
            if (preg_match('/^\s*(\d+\.?\d*)\s*(mm|cm|in|pt|px)\s+(\d+\.?\d*)\s*(mm|cm|in|pt|px)/i', $sizeValue, $matches)) {
                $width = (float) $matches[1];
                $height = (float) $matches[3];
                $unit1 = strtolower($matches[2]);
                $unit2 = strtolower($matches[4]);

                // Convert to mm
                $width = $this->convertToMm($width, $unit1);
                $height = $this->convertToMm($height, $unit2);

                // Round to reasonable precision
                $width = round($width);
                $height = round($height);

                return [$width, $height];
            }
        }

        return null;
    }

    /**
     * Convert a value from a given unit to millimeters.
     */
    private function convertToMm(float $value, string $unit): float
    {
        return match (strtolower($unit)) {
            'mm' => $value,
            'cm' => $value * 10,
            'in' => $value * 25.4,
            'pt' => $value * 0.352778,
            'px' => $value * 0.264583,
            default => $value,
        };
    }

    /**
     * Fallback format if @page size is not found in CSS.
     * Uses template metadata comments first:
     * - @template-width: 220mm
     * - @template-height: 110mm
     */
    private function getDefaultFormat(string $css): array|string
    {
        $width = $this->extractNumber($css, '@template-width');
        $height = $this->extractNumber($css, '@template-height');

        if ($width !== null && $height !== null && $width > 0 && $height > 0) {
            return [round($width), round($height)];
        }

        return 'A4';
    }

    /**
     * Get template type from @template-type CSS comment.
     * Returns 'letter', 'envelope', or null if not found.
     */
    public function getTemplateType(string $templateName): ?string
    {
        $templatePath = $this->resolveTemplatePath($templateName);
        $html = $templatePath ? @file_get_contents($templatePath) : false;

        if ($html === false) {
            return null;
        }

        // Extract CSS from <style> tags
        $css = $this->extractCss($html);

        // Parse @template-type comment (e.g., /* @template-type: envelope */)
        if (preg_match('/@template-type\s*:\s*(\w+)/i', $css, $matches)) {
            return strtolower($matches[1]);
        }

        return null;
    }

    /**
     * Check if template is an envelope based on @template-type.
     */
    public function isEnvelopeTemplate(string $templateName): bool
    {
        return $this->getTemplateType($templateName) === 'envelope';
    }

    /**
     * Get stamp placement metadata from template CSS comments.
     * Returns array with x, y, w, h, rotation (degrees) or null if missing.
     */
    public function getStampPlacement(string $templateName): ?array
    {
        $templatePath = $this->resolveTemplatePath($templateName);
        $html = $templatePath ? @file_get_contents($templatePath) : false;

        if ($html === false) {
            return null;
        }

        $css = $this->extractCss($html);

        $x = $this->extractNumber($css, '@stamp-position-x');
        $y = $this->extractNumber($css, '@stamp-position-y');
        $w = $this->extractNumber($css, '@stamp-width');
        $h = $this->extractNumber($css, '@stamp-height');
        $rotation = $this->extractNumber($css, '@stamp-rotation');

        if ($x === null || $y === null || $w === null || $h === null) {
            return null;
        }

        return [
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h,
            'rotation' => $rotation ?? 0.0,
        ];
    }

    /**
     * Get all available templates with their metadata.
     * Returns array of [id, name, type].
     *
     * @return array List of templates with metadata
     */
    public function getTemplates(): array
    {
        $this->normalizeLetterTemplates();

        $templates = [];
        $templateDir = $this->getTemplatesDir();

        if (!is_dir($templateDir)) {
            return [];
        }

        $files = @scandir($templateDir);
        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            if (substr($file, -5) !== '.html') {
                continue;
            }

            $templateName = substr($file, 0, -5);
            $templatePath = $templateDir . '/' . $file;
            $html = @file_get_contents($templatePath);

            if ($html === false) {
                continue;
            }

            $metadata = $this->extractTemplateMetadata($html, $templateName);
            if ($metadata !== null) {
                $metadata['filename'] = $file;
                $templates[] = $metadata;
            }
        }

        return $templates;
    }

    /**
     * Get templates filtered by type.
     *
     * @param string $type Template type (e.g., 'letter', 'envelope')
     * @return array List of matching templates
     */
    public function getTemplatesByType(string $type): array
    {
        return array_filter($this->getTemplates(), function ($template) use ($type) {
            return $template['type'] === strtolower($type);
        });
    }

    public function deleteTemplate(string $filename): bool
    {
        $normalized = basename($filename);
        if ($normalized === '' || substr($normalized, -5) !== '.html') {
            return false;
        }
        if ($normalized === self::LETTER_TEMPLATE . '.html') {
            return false;
        }

        $path = $this->getTemplatesDir() . '/' . $normalized;
        if (!is_file($path)) {
            return false;
        }

        return @unlink($path);
    }

    public function uploadTemplateHtml($templateUpload, ?string $templateTarget = null): string
    {
        if (empty($templateUpload)) {
            throw new \RuntimeException('Template HTML upload missing.');
        }

        $expectedType = null;
        if ($templateTarget !== null && $templateTarget !== '') {
            $expectedType = strtolower(trim($templateTarget));
            if (!in_array($expectedType, ['letter', 'envelope'], true)) {
                throw new \RuntimeException('Invalid template target. Use letter or envelope.');
            }
        }

        if (is_array($templateUpload)) {
            $tmpPath = $templateUpload['tmp_name'] ?? '';
            $errorCode = (int) ($templateUpload['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($errorCode !== UPLOAD_ERR_OK) {
                throw new \RuntimeException('Template HTML upload failed with error code: ' . $errorCode);
            }
            if ($tmpPath === '' || !is_file($tmpPath)) {
                throw new \RuntimeException('Template HTML upload missing temporary file.');
            }

            $originalName = (string) ($templateUpload['name'] ?? 'template.html');
            $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
            $mimeType = strtolower((string) ($templateUpload['type'] ?? ''));
            if ($extension !== 'html' && $extension !== 'htm' && $mimeType !== 'text/html') {
                throw new \RuntimeException('Only HTML template files are allowed.');
            }

            $content = @file_get_contents($tmpPath);
            if ($content === false) {
                throw new \RuntimeException('Template HTML upload missing temporary file.');
            }
            $templateType = $this->extractTemplateTypeFromHtml($content);
            if ($templateType === null) {
                throw new \RuntimeException('Template type missing. Add @template-type: letter|envelope in <style>.');
            }
            if ($expectedType !== null && $templateType !== $expectedType) {
                throw new \RuntimeException('Template type mismatch. Expected: ' . $expectedType . '.');
            }

            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $baseName = preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $baseName);
            $baseName = trim((string) $baseName, '-');
            if ($baseName === '') {
                $baseName = 'template';
            }

            $htmlName = $templateType === 'letter' ? self::LETTER_TEMPLATE . '.html' : $baseName . '.html';
            $htmlPath = $this->getTemplatesDir() . '/' . $htmlName;
            if ($templateType !== 'letter' && is_file($htmlPath)) {
                $htmlName = $baseName . '_' . bin2hex(random_bytes(8)) . '.html';
                $htmlPath = $this->getTemplatesDir() . '/' . $htmlName;
            }

            if (is_uploaded_file($tmpPath)) {
                if (!move_uploaded_file($tmpPath, $htmlPath)) {
                    throw new \RuntimeException('Failed to move uploaded template HTML.');
                }
            } else {
                if (!copy($tmpPath, $htmlPath)) {
                    throw new \RuntimeException('Failed to copy template HTML.');
                }
            }

            if ($templateType === 'letter') {
                $this->removeAdditionalLetterTemplates(self::LETTER_TEMPLATE . '.html');
            }

            return $htmlName;
        }

        throw new \RuntimeException('Invalid template HTML upload payload.');
    }

    public function getTemplatePath(string $templateName): string
    {
        $templatePath = $this->resolveTemplatePath($templateName);
        if ($templatePath === null) {
            throw new \RuntimeException('Template file not found: ' . $this->getTemplatesDir() . '/' . $templateName . '.html');
        }

        return $templatePath;
    }

    public function getTemplateDownload(string $filename): array
    {
        $normalized = basename($filename);
        if ($normalized === '' || substr($normalized, -5) !== '.html') {
            throw new \RuntimeException('Invalid template filename.');
        }

        $path = $this->getTemplatesDir() . '/' . $normalized;
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Template not found: ' . $normalized);
        }

        return [
            'filename' => $normalized,
            'content' => $content,
        ];
    }

    public function resetTemplates(): void
    {
        $sourceRoot = dirname(__DIR__) . '/Templates';
        if (!is_dir($sourceRoot)) {
            throw new \RuntimeException('Default templates directory not found: ' . $sourceRoot);
        }

        $targetRoot = $this->getTemplatesDir();
        $this->deleteDirectoryRecursively($targetRoot);
        $this->ensureDirectory($targetRoot);

        $this->copyHtmlFiles($sourceRoot, $targetRoot);

        $sourceOriginals = $sourceRoot . '/originals';
        if (is_dir($sourceOriginals)) {
            $targetOriginals = $targetRoot . '/originals';
            $this->ensureDirectory($targetOriginals);
            $this->copyHtmlFiles($sourceOriginals, $targetOriginals);
        }
    }

    /**
     * Extract metadata from template HTML/CSS.
     * Looks for:
     * - @template-id: name
     * - @template-name: display name
     * - @template-type: type
     *
     * @return array|null Array with 'id', 'name', 'type' or null if invalid
     */
    private function extractTemplateMetadata(string $html, string $fallbackId): ?array
    {
        // Extract CSS from <style> tags
        $css = '';
        if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $matches)) {
            $css = implode("\n", $matches[1]);
        }

        // Extract template-id
        $id = $fallbackId;
        if (preg_match('/@template-id\s*:\s*([a-zA-Z0-9\-_]+)/i', $css, $matches)) {
            $id = $matches[1];
        }

        // Extract template-name (human-readable name)
        $name = null;
        if (preg_match('/@template-name\s*:\s*(.+?)(?:\s*\*\/|$)/i', $css, $matches)) {
            $name = trim($matches[1]);
        }

        // Fallback: generate name from ID if not explicitly provided
        if ($name === null) {
            $name = $this->generateNameFromId($id);
        }

        // Extract template-type
        $type = null;
        if (preg_match('/@template-type\s*:\s*(letter|envelope)/i', $css, $matches)) {
            $type = strtolower($matches[1]);
        }

        return [
            'id' => $id,
            'name' => $name,
            'type' => $type,
        ];
    }

    /**
     * Generate human-readable display name from template ID.
     * E.g., 'din-long-envelope' -> 'DIN Long Envelope'
     */
    private function generateNameFromId(string $id): string
    {
        return implode(' ', array_map('ucfirst', explode('-', $id)));
    }

    /**
     * Extract a numeric value (in mm or degrees) from a CSS comment tag.
        * Example: @stamp-width: 42mm
     */
    private function extractNumber(string $css, string $tag): ?float
    {
        $pattern = '/' . preg_quote($tag, '/') . '\s*:\s*([-+]?\d*\.?\d+)/i';
        if (!preg_match($pattern, $css, $matches)) {
            return null;
        }

        return (float) $matches[1];
    }

    private function extractTemplateTypeFromHtml(string $html): ?string
    {
        $css = $this->extractCss($html);
        if (preg_match('/@template-type\s*:\s*(letter|envelope)/i', $css, $matches)) {
            return strtolower($matches[1]);
        }

        return null;
    }

    private function getTemplatesDir(): string
    {
        $dataDir = rtrim((string) $this->config->getSystemValue('datadirectory', ''), "/\\");
        $instanceId = (string) $this->config->getSystemValue('instanceid', '');

        if ($dataDir === '' || $instanceId === '') {
            throw new \RuntimeException('Failed to resolve Nextcloud appdata path.');
        }

        return $dataDir . '/appdata_' . $instanceId . '/' . self::APP_ID . '/templates';
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!@mkdir($path, 0770, true) && !is_dir($path)) {
            throw new \RuntimeException('Failed to create directory: ' . $path);
        }
    }

    private function deleteDirectoryRecursively(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = @scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                $this->deleteDirectoryRecursively($fullPath);
            } else {
                @unlink($fullPath);
            }
        }

        @rmdir($path);
    }

    private function copyHtmlFiles(string $sourceDir, string $targetDir): void
    {
        $entries = @scandir($sourceDir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if (substr($entry, -5) !== '.html') {
                continue;
            }

            $sourcePath = $sourceDir . '/' . $entry;
            $targetPath = $targetDir . '/' . $entry;

            $content = @file_get_contents($sourcePath);
            if ($content === false) {
                continue;
            }

            @file_put_contents($targetPath, $content);
        }
    }

    private function resolveTemplatePath(string $templateName): ?string
    {
        $templateDir = $this->getTemplatesDir();
        $templatePath = $templateDir . '/' . $templateName . '.html';
        if (is_file($templatePath)) {
            return $templatePath;
        }

        if ($templateName === self::LETTER_TEMPLATE) {
            $legacyPath = $templateDir . '/' . self::LEGACY_LETTER_TEMPLATE . '.html';
            if (!is_file($legacyPath)) {
                return null;
            }

            @rename($legacyPath, $templatePath);
            if (is_file($templatePath)) {
                return $templatePath;
            }

            return $legacyPath;
        }

        return null;
    }

    private function normalizeLetterTemplates(): void
    {
        $templateDir = $this->getTemplatesDir();
        if (!is_dir($templateDir)) {
            return;
        }

        $this->resolveTemplatePath(self::LETTER_TEMPLATE);
        $this->removeAdditionalLetterTemplates(self::LETTER_TEMPLATE . '.html');
    }

    private function removeAdditionalLetterTemplates(string $keepFilename): void
    {
        $templateDir = $this->getTemplatesDir();
        $entries = @scandir($templateDir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if (substr($entry, -5) !== '.html' || $entry === $keepFilename) {
                continue;
            }

            $path = $templateDir . '/' . $entry;
            $html = @file_get_contents($path);
            if ($html === false) {
                continue;
            }

            if ($this->extractTemplateTypeFromHtml($html) === 'letter') {
                @unlink($path);
            }
        }
    }

}
