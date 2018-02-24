<?php

namespace Armandsar\LaravelTranslationio\Service;

use Armandsar\LaravelTranslationio\TargetPOGenerator;
use Armandsar\LaravelTranslationio\GettextPOGenerator;
use Armandsar\LaravelTranslationio\GettextTranslationSaver;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;

class Init
{
    private $config;

    /**
     * @var TargetPOGenerator
     */
    private $poGenerator;

    /**
     * @var GettextPOGenerator
     */
    private $gettextPoGenerator;

    /**
     * @var GettextTranslationSaver
     */
    private $gettextTranslationSaver;

    public function __construct(
      Application $application,
      TargetPOGenerator $poGenerator,
      GettextPOGenerator $gettextPoGenerator,
      GettextTranslationSaver $gettextTranslationSaver
    )
    {
        $this->poGenerator = $poGenerator;
        $this->gettextPoGenerator = $gettextPoGenerator;
        $this->gettextTranslationSaver = $gettextTranslationSaver;
        $this->config = $application['config']['translationio'];
    }

    public function call($command)
    {
        $client = new Client(['base_uri' => $this->url()]);
        $body = $this->createBody();

        $responseData = $this->makeRequest($client, $body);

        # Save new po files created from backend
        foreach ($this->targetLocales() as $locale) {
            $this->gettextTranslationSaver->call(
                $locale,
                $responseData['po_data_' . $locale]
            );
        }

        $this->displayInfoProjectUrl($responseData, $command);
    }

    private function createBody()
    {
        $formData = [
            'from' => 'laravel-translationio',
            'gem_version' => '2.0',
            'source_language' => $this->sourceLocale(),
        ];

        // key/values from PHP translation files
        $poData = $this->poGenerator->call($this->sourceLocale(), $this->targetLocales());
        foreach ($this->targetLocales() as $locale) {
            $formData['yaml_po_data_' . $locale] = $poData[$locale];
        }

        // source/translation from Gettext
        $gettextPoData = $this->gettextPoGenerator->call($this->sourceLocale(), $this->targetLocales());
        $formData['pot_data'] = $gettextPoData['pot_data'];
        foreach ($this->targetLocales() as $locale) {
            $formData['po_data_' . $locale] = $gettextPoData[$locale];
        }

        $body = http_build_query($formData);

        foreach ($this->targetLocales() as $locale) {
            $body = $body . "&target_languages[]=$locale";
        }

        return $body;
    }

    private function makeRequest($client, $body)
    {
        $response = $client->request('POST', '', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => $body
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function displayInfoProjectUrl($responseData, $command) {
        $command->line("");
        $command->line("----------");
        $command->info("Use this URL to translate: {$responseData['project_url']}");
        $command->line("----------");
    }

    private function sourceLocale()
    {
        return $this->config['source_locale'];
    }

    private function targetLocales()
    {
        return $this->config['target_locales'];
    }

    private function url()
    {
//        return 'https://requestb.in/11l8hjp1';
        return 'https://translation.io/api/projects/' . $this->config['key'] . '/init';
    }
}
