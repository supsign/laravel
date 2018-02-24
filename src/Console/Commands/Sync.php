<?php

namespace Armandsar\LaravelTranslationio\Console\Commands;

use Illuminate\Console\Command;
use Armandsar\LaravelTranslationio\Service\SourceEditSync as SourceEditSyncService;
use Armandsar\LaravelTranslationio\Service\Sync as SyncService;

class Sync extends Command
{
    protected $signature = 'translation:sync';

    protected $description = 'Send new translatable keys/strings and get new translations from Translation.io';

    /**
     * @var SyncService
     */
    private $syncService;
    /**
     * @var SourceEditSyncService
     */
    private $sourceEditSyncService;

    public function __construct(
        SourceEditSyncService $sourceEditSyncService,
        SyncService $syncService
    )
    {
        $this->sourceEditSyncService = $sourceEditSyncService;
        $this->syncService = $syncService;
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Sync started');
        $this->sourceEditSyncService->call();
        $this->syncService->call($this, [
            'purge' => false,
            'show_purgeable' => false
        ]);
    }
}
