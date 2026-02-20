<?php

namespace OCA\LetterMaker\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings
{
    public function getForm(): TemplateResponse
    {
        return new TemplateResponse('lettermaker', 'admin');
    }

    public function getSection(): string
    {
        return 'lettermaker';
    }

    public function getPriority(): int
    {
        return 10;
    }
}
