<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Support\Carbon;
use Illuminate\Support\Facades\File;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Finder\Finder;

class AutoCleanFileJob extends Job
{
    /**
     * @return string[][]
     */
    #[ArrayShape([])]
    protected function rules(): array
    {
        return [
            'dir' => ['required', 'string'],
            'pattern' => ['string', 'default:*'],
            'max_days' => ['integer', 'min:0', 'default:30'],
        ];
    }

    protected function action(): void
    {
        $files = Finder::create()
            ->in($this->getDirs())
            ->name($this->getPatterns())
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $mDate = Carbon::createFromTimestamp($file->getMTime());
            if (!$mDate->addDays($this->getMaxDays())->isPast()) {
                continue;
            }

            $count++;
            File::delete($file);
        }

        $this->info(sprintf('Delete %s files.', $count));
    }

    protected function getMaxDays(): int
    {
        return intval($this->get('max_days', 30));
    }

    /**
     * @return array
     */
    protected function getDirs(): array
    {
        return [$this->get('dir')];
    }

    /**
     * @return array
     */
    protected function getPatterns(): array
    {
        return [$this->get('pattern')];
    }
}
