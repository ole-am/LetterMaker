<?php

namespace OCA\LetterMaker\Repair;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CopyTemplates implements IRepairStep
{
    private const APP_ID = 'lettermaker';

    public function __construct(private IConfig $config)
    {
    }

    public function getName(): string
    {
        return 'Copy default templates into appdata';
    }

    public function run(IOutput $output): void
    {
        $sourceRoot = dirname(__DIR__) . '/Templates';
        if (!is_dir($sourceRoot)) {
            return;
        }

        $targetRoot = $this->getTemplatesDir();
        if (!is_dir($targetRoot)) {
            if (!@mkdir($targetRoot, 0770, true) && !is_dir($targetRoot)) {
                return;
            }
        }

        $this->copyHtmlFiles($sourceRoot, $targetRoot);
    }

    private function getTemplatesDir(): string
    {
        $dataDir = rtrim((string) $this->config->getSystemValue('datadirectory', ''), "/\\");
        $instanceId = (string) $this->config->getSystemValue('instanceid', '');

        return $dataDir . '/appdata_' . $instanceId . '/' . self::APP_ID . '/templates';
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

            $targetPath = $targetDir . '/' . $entry;
            if (is_file($targetPath)) {
                // Keep existing file untouched.
                continue;
            }

            $sourcePath = $sourceDir . '/' . $entry;
            $content = @file_get_contents($sourcePath);
            if ($content === false) {
                continue;
            }

            @file_put_contents($targetPath, $content);
        }
    }
}
