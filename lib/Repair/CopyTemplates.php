<?php

namespace OCA\LetterMaker\Repair;

use OCP\Files\AlreadyExistsException;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CopyTemplates implements IRepairStep
{
    public function __construct(private IAppData $appData)
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

        $templatesFolder = $this->getOrCreateAppDataFolder('templates');
        $this->copyHtmlFiles($sourceRoot, $templatesFolder);
    }

    private function getOrCreateAppDataFolder(string $name)
    {
        try {
            return $this->appData->getFolder($name);
        } catch (NotFoundException $e) {
            try {
                return $this->appData->newFolder($name);
            } catch (AlreadyExistsException $e) {
                return $this->appData->getFolder($name);
            }
        }
    }

    private function copyHtmlFiles(string $sourceDir, $targetFolder): void
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
            $content = @file_get_contents($sourcePath);
            if ($content === false) {
                continue;
            }

            try {
                $targetFile = $targetFolder->newFile($entry);
                $targetFile->putContent($content);
            } catch (AlreadyExistsException $e) {
                // Keep existing file untouched.
            }
        }
    }
}
