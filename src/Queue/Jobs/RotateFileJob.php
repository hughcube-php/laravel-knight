<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use Exception;
use HughCube\Laravel\Knight\Queue\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use SplFileObject;
use Symfony\Component\Finder\Finder;

class RotateFileJob extends Job
{
    /**
     * @return array
     */
    protected function rules(): array
    {
        return [
            'items' => ['array'],

            'items.*.dir'     => ['required'],
            'items.*.pattern' => ['nullable'],

            'items.*.date_format' => ['nullable', 'string'],
        ];
    }

    /**
     * @throws Exception
     */
    protected function action(): void
    {
        foreach ($this->p('items', []) as $item) {
            $this->cleanFiles($item);
        }
    }

    /**
     * @throws Exception
     */
    protected function cleanFiles($job)
    {
        $dirs = Collection::wrap($job['dir'])->filter();
        $patterns = Collection::wrap($job['pattern'] ?? '*')->filter();
        $dateFormat = $job['date_format'] ?: 'Y-m-d';

        /** @var Collection $existDirs */
        $existDirs = $dirs->filter(function ($dir) {
            return File::exists($dir);
        })->values();

        $files = [];
        if ($existDirs->isNotEmpty()) {
            $files = Finder::create()->in($existDirs->all())->name($patterns->all())->files();
        }

        $results = Collection::empty();
        foreach ($files as $file) {
            $datePath = sprintf(
                '%s/%s-%s%s',
                File::dirname($file->getRealPath()),
                $file->getFilenameWithoutExtension(),
                Carbon::now()->format($dateFormat),
                $file->getExtension() ? sprintf('.%s', $file->getExtension()) : ''
            );

            $handle = $file->openFile('w+');
            if (!$handle instanceof SplFileObject) {
                throw new Exception('Failed to open file!');
            }

            if (!$handle->flock(LOCK_EX)) {
                throw new Exception('Failed to lock the file!');
            }

            if (!File::copy($file->getRealPath(), $datePath)) {
                throw new Exception('Copy file failed!');
            }

            if (!$handle->ftruncate(0)) {
                throw new Exception('Failed to clear file.');
            }

            $results = $results->add(['path' => $file->getRealPath(), 'date_path' => $datePath]);
        }

        $this->info(sprintf(
            'Rotate %s files in directory "%s" that match "%s", results: %s.',
            $results->count(),
            $dirs->implode(', '),
            $patterns->implode(', '),
            $results->map(function ($result) use ($dirs) {
                if ($dirs->count() > 1) {
                    return sprintf('[%s => %s]', $result['path'], $result['date_path']);
                } else {
                    return sprintf('[%s => %s]', File::basename($result['path']), File::basename($result['date_path']));
                }
            })->implode(', ')
        ));
    }
}
