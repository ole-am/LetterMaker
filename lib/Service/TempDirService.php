<?php

namespace OCA\LetterMaker\Service;

use OCP\Files\AlreadyExistsException;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\IConfig;

class TempDirService
{
    private const APP_ID = 'lettermaker';

    public function __construct(private IAppData $appData, private IConfig $config)
    {
    }

    public function createTempDir(): array
    {
        $folderName = '';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = 'tmp_' . bin2hex(random_bytes(8));
            try {
                $this->appData->newFolder($candidate);
                $folderName = $candidate;
                break;
            } catch (AlreadyExistsException $e) {
                continue;
            }
        }

        if ($folderName === '') {
            throw new \RuntimeException('Failed to create a unique AppData temp folder.');
        }

        $tempDir = $this->getAppDataBasePath() . '/' . $folderName;

        if (!is_dir($tempDir)) {
            throw new \RuntimeException('AppData temp dir is not available locally: ' . $tempDir);
        }

        return [
            'path' => $tempDir,
            'folder' => $folderName,
        ];
    }

    public function deleteTempDir(string $folderName): void
    {
        try {
            $folder = $this->appData->getFolder($folderName);
            $folder->delete();
        } catch (NotFoundException $e) {
            // Already deleted or never created.
        }
    }

    private function getAppDataBasePath(): string
    {
        $dataDir = rtrim((string) $this->config->getSystemValue('datadirectory', ''), "/\\");
        $instanceId = (string) $this->config->getSystemValue('instanceid', '');

        if ($dataDir === '' || $instanceId === '') {
            throw new \RuntimeException('Failed to resolve Nextcloud appdata path.');
        }

        return $dataDir . '/appdata_' . $instanceId . '/' . self::APP_ID;
    }
}
