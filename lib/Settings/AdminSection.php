<?php

namespace OCA\LetterMaker\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection
{
    public function __construct(
        private IL10N $l10n,
        private IURLGenerator $urlGenerator
    ) {
    }

    public function getID(): string
    {
        return 'lettermaker';
    }

    public function getName(): string
    {
        return $this->l10n->t('LetterMaker');
    }

    public function getPriority(): int
    {
        return 5;
    }

    public function getIcon(): string
    {
        return $this->urlGenerator->imagePath('lettermaker', 'app_admin.svg');
    }
}
