<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 1:57 下午.
 */

namespace HughCube\Laravel\Knight\Support;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;

trait Validation
{
    /**
     * Request rules.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * @param  array  $request
     *
     * @return array
     * @throws ValidationException
     *
     */
    protected function validate(array $request): array
    {
        /** @var Factory $factory */
        $factory = app(Factory::class);

        $validator = $factory->make($request, $this->rules());

        /** @var array|null $data */
        $data = $validator->validate();

        /** Compatible with php7.0 */
        if (null === $data && method_exists($validator, 'valid')) {
            $data = $validator->valid();
        }

        return empty($data) ? [] : $data;
    }
}
