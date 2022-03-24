<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Support\Carbon;
use Illuminate\Support\Arr;
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
            'items' => ['required', 'array', 'min:1'],

            'items.*.dir'      => ['required'],
            'items.*.pattern'  => ['nullable'],
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
        $dirs = Arr::wrap($job['dir']);
        $patterns = Arr::wrap($job['pattern'] ?? '*');
        $maxDays = $job['max_days'] ?: 30;

        $files = Finder::create()->in($dirs)->name($patterns)->files();

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
            implode(',', $dirs),
            implode(',', $patterns)
        ));
    }
}
