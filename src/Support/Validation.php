<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 1:57 下午.
 */

namespace HughCube\Laravel\Knight\Support;

use Illuminate\Contracts\Validation\Factory;

trait Validation
{
    /**
     * Get a validation factory instance.
     *
     * @return \Illuminate\Contracts\Validation\Factory
     */
    protected function getValidationFactory()
    {
        return app(Factory::class);
    }

    /**
     * Request rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [];
    }

    /**
     * @param array $request
     *
     * @throws \Illuminate\Validation\ValidationException
     *
     * @return array|null
     */
    protected function validate(array $request)
    {
        $validator = $this->getValidationFactory()->make($request, $this->rules());

        /** @var array|null $data */
        $data = $validator->validate();

        /** Compatible with php7.0 */
        if (null === $data && method_exists($validator, 'valid')) {
            $data = $validator->valid();
        }

        return $data;
    }
}
