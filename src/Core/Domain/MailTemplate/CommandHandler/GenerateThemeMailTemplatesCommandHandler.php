<?php
/**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Core\Domain\MailTemplate\CommandHandler;

use PrestaShop\PrestaShop\Core\Domain\MailTemplate\Command\GenerateThemeMailTemplatesCommand;
use PrestaShop\PrestaShop\Core\Exception\InvalidArgumentException;
use PrestaShop\PrestaShop\Core\Language\LanguageInterface;
use PrestaShop\PrestaShop\Core\Language\LanguageRepositoryInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\MailTemplateGenerator;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeCatalogInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeInterface;
use PrestaShopBundle\Translation\DataCollectorTranslator;
use Symfony\Component\Translation\Loader\ArrayLoader;

/**
 * Class GenerateThemeMailTemplatesCommandHandler generates email templates with parameters provided
 * by GenerateThemeMailTemplatesCommand. If no output folders are defined by the command its output
 * folders are the default ones.
 */
class GenerateThemeMailTemplatesCommandHandler implements GenerateThemeMailTemplatesCommandHandlerInterface
{
    /** @var LanguageRepositoryInterface */
    private $languageRepository;

    /** @var ThemeCatalogInterface */
    private $themeCatalog;

    /** @var MailTemplateGenerator */
    private $generator;

    /** @var DataCollectorTranslator */
    private $translator;

    /** @var string */
    private $defaultCoreMailsFolder;

    /** @var string */
    private $defaultModulesMailFolder;

    /**
     * @param LanguageRepositoryInterface $languageRepository
     * @param ThemeCatalogInterface $themeCatalog
     * @param MailTemplateGenerator $generator
     * @param DataCollectorTranslator $translator
     * @param string $defaultCoreMailsFolder
     * @param string $defaultModulesMailFolder
     */
    public function __construct(
        LanguageRepositoryInterface $languageRepository,
        ThemeCatalogInterface $themeCatalog,
        MailTemplateGenerator $generator,
        DataCollectorTranslator $translator,
        $defaultCoreMailsFolder,
        $defaultModulesMailFolder
    ) {
        $this->languageRepository = $languageRepository;
        $this->themeCatalog = $themeCatalog;
        $this->generator = $generator;
        $this->translator = $translator;
        $this->defaultCoreMailsFolder = $defaultCoreMailsFolder;
        $this->defaultModulesMailFolder = $defaultModulesMailFolder;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GenerateThemeMailTemplatesCommand $command)
    {
        /** @var LanguageInterface $language */
        $language = $this->languageRepository->getOneByLocaleOrIsoCode($command->getLanguage());
        if (null === $language) {
            throw new InvalidArgumentException(sprintf('Could not find Language for locale: %s', $command->getLanguage()));
        }

        /** @var ThemeInterface $theme */
        $theme = $this->themeCatalog->getByName($command->getThemeName());

        $this->cleanTranslatorLocaleCache($command->getLanguage());

        $coreMailsFolder = $command->getCoreMailsFolder() ?: $this->defaultCoreMailsFolder;
        $modulesMailFolder = $command->getModulesMailFolder() ?: $this->defaultModulesMailFolder;

        $this->generator->generateTemplates($theme, $language, $coreMailsFolder, $modulesMailFolder, $command->overwriteTemplates());
    }

    /**
     * When installing a new Language, if it's a new one the Translator component can't manage it because its cache is
     * already filled with the default one as fallback. We force the component to update its cache by adding a fake
     * resource for this locale (this is the only way clean its local cache)
     *
     * @param string $locale
     */
    private function cleanTranslatorLocaleCache($locale)
    {
        $this->translator->addLoader('array', new ArrayLoader());
        $this->translator->addResource(
            'array',
            ['Fake clean cache message' => 'Fake clean cache message'],
            $locale
        );
    }
}
