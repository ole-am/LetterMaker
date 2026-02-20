<?php

namespace OCA\LetterMaker\Repair;

use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ClearAppData implements IRepairStep
{
    public function __construct(private IAppData $appData, private IConfig $config)
    {
    }

    public function getName(): string
    {
        return 'Clear LetterMaker appdata';
    }

    public function run(IOutput $output): void
    {
        $appDataPath = $this->getAppDataBasePath();
        if (!is_dir($appDataPath)) {
            return;
        }

        $this->deleteAppDataFiles($appDataPath);
    }

    private function getAppDataBasePath(): string
    {
        $dataDir = rtrim((string) $this->config->getSystemValue('datadirectory', ''), "/\\");
        $instanceId = (string) $this->config->getSystemValue('instanceid', '');

        if ($dataDir === '' || $instanceId === '') {
            throw new \RuntimeException('Failed to resolve Nextcloud appdata path.');
        }

        return $dataDir . '/appdata_' . $instanceId . '/lettermaker';
    }

    private function deleteAppDataFiles(string $appDataPath): void
    {
        if (is_link($appDataPath) || is_file($appDataPath)) {
            @unlink($appDataPath);
            return;
        }

        $items = @scandir($appDataPath);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $this->deleteAppDataFiles($appDataPath . '/' . $item);
        }

        @rmdir($appDataPath);
    }
}
