<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class CleanFilesJob extends Job
{
    /**
     * @return array
     */
    protected function rules(): array
    {
        return [
            'items' => ['array'],

            'items.*.dir' => ['required'],
            'items.*.pattern' => ['nullable'],
            'items.*.max_days' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function action(): void
    {
        foreach ($this->p('items', []) as $item) {
            $this->cleanFiles($item);
        }
    }

    protected function cleanFiles($job)
    {
        $dirs = Collection::wrap($job['dir'])->filter();
        $patterns = Collection::wrap($job['pattern'] ?? '*')->filter();
        $maxDays = $job['max_days'] ?: 30;

        /** @var Collection $existDirs */
        $existDirs = $dirs->filter(function ($dir) {
            return File::exists($dir);
        })->values();

        $files = [];
        if ($existDirs->isNotEmpty()) {
            $files = Finder::create()->in($existDirs->all())->name($patterns->all())->files();
        }

        $count = 0;
        foreach ($files as $file) {
            $mDate = Carbon::createFromTimestamp($file->getMTime());
            if (!$mDate->addDays($maxDays)->isPast()) {
                continue;
            }

            $count++;
            File::delete($file);
        }

        $this->info(sprintf(
            'Delete %s files in directory "%s" that match "%s".',
            $count,
            $dirs->implode(','),
            $patterns->implode(',')
        ));
    }
}
