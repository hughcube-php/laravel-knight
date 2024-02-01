<?php

namespace HughCube\Laravel\Knight\Sanctum\Jobs;

use HughCube\Laravel\Knight\Sanctum\PersonalAccessToken;
use Illuminate\Support\Carbon;

class PersonalAccessTokenGcJob extends \HughCube\Laravel\Knight\Queue\Job
{
    protected function action(): void
    {
        $query = PersonalAccessToken::query()
            ->where(
                'last_used_at',
                '<=',
                Carbon::now()->subSeconds(PersonalAccessToken::getExpiresIn())->subDays()
            );

        $doneCount = 0;
        while (true) {
            $doneCount += ($n = $query->clone()->limit(200)->delete());
            if (0 >= $n) {
                break;
            }
        }

        $this->info(sprintf('共删除tokens %s条!', $doneCount));
    }
}
