<?php

namespace TestApp\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Translator for tests that simply return domain / locale / id
 */
class MockTranslator implements TranslatorInterface
{
    private array $replacements = [];

    public function __construct(
        private string $defaultLocale = 'en_GB',
        private ?string $defaultDomain = null,
    ) {
    }

    /**
     * Set explicit replacements that be used instead of the default domain / locale / id format
     *
     * @param string[] $replacements e.g. ['tzunghaor/de_AT/text' => 'DADA']
     */
    public function setReplacements(array $replacements)
    {
        $this->replacements = $replacements;
    }

    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        $translation = ($domain ?? $this->defaultDomain) . '/' . ($locale ?? $this->defaultLocale) . '/' . $id;

        return $this->replacements[$translation] ?? $translation;
    }

    public function getLocale(): string
    {
        return $this->defaultLocale;
    }
}