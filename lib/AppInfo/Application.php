<?php

namespace OCA\LetterMaker\AppInfo;

use OCA\LetterMaker\Service\TemplateService;
use OCA\LetterMaker\Service\TemplateRendererService;
use OCA\LetterMaker\Service\StampService;
use OCA\LetterMaker\Service\TempDirService;
use OCA\LetterMaker\Service\PdfService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\IAppData;
use OCP\IConfig;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'lettermaker';

    public function __construct()
    {
        parent::__construct(self::APP_ID);

        $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
        }
    }

    public function register(IRegistrationContext $context): void
    {
        $context->registerService(TemplateService::class, function ($container) {
            return new TemplateService($container->get(IConfig::class));
        });

        $context->registerService(TemplateRendererService::class, function ($container) {
            return new TemplateRendererService(
                $container->get(TemplateService::class)
            );
        });

        $context->registerService(StampService::class, function ($container) {
            return new StampService(
                $container->get(TemplateService::class)
            );
        });

        $context->registerService(TempDirService::class, function ($container) {
            return new TempDirService(
                $container->get(IAppData::class),
                $container->get(IConfig::class)
            );
        });

        $context->registerService(PdfService::class, function ($container) {
            return new PdfService(
                $container->get(TemplateService::class),
                $container->get(TemplateRendererService::class),
                $container->get(StampService::class),
                $container->get(TempDirService::class)
            );
        });
    }

    public function boot(IBootContext $context): void
    {
    }
}
