<?php

namespace App\Console\Commands;

use App\Models\GraphSyncOutbox;
use App\Services\Microsoft\GraphSyncProcessor;
use Illuminate\Console\Command;

class ProcessGraphSyncOutbox extends Command
{
    protected $signature = 'graph:sync-outbox {--limit=25}';

    protected $description = 'Retry recorded Microsoft calendar attendee synchronizations';

    public function handle(GraphSyncProcessor $processor): int
    {
        $limit = max(1, min(100, (int) $this->option('limit')));
        $rows = GraphSyncOutbox::query()
            ->whereIn('status', [GraphSyncOutbox::STATUS_PENDING, GraphSyncOutbox::STATUS_FAILED])
            ->where(function ($query) {
                $query->whereNull('available_at')->orWhere('available_at', '<=', now());
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($rows as $row) {
            $processor->process($row);
        }

        $this->info('Processed '.$rows->count().' calendar sync records.');

        return self::SUCCESS;
    }
}
