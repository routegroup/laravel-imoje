<?php

namespace Routegroup\Imoje\Payment\Factories;

use Illuminate\Database\Eloquent\Factories\Factory as BaseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Mockery;
use Routegroup\Imoje\Payment\DTO\Responses\ResponseDto;

/**
 * @template TModel of \Routegroup\Imoje\Payment\DTO\BaseDto
 */
abstract class Factory extends BaseFactory
{
    /**
     * @var class-string<\Routegroup\Imoje\Payment\DTO\BaseDto|TModel>
     */
    protected $model;

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \Routegroup\Imoje\Payment\DTO\BaseDto|TModel>|\Routegroup\Imoje\Payment\DTO\BaseDto|TModel
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        if (! empty($attributes)) {
            return $this->state($attributes)->create([], null);
        }

        return $this->make($attributes, null);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \Routegroup\Imoje\Payment\DTO\BaseDto|TModel>|\Routegroup\Imoje\Payment\DTO\BaseDto|TModel
     */
    public function make($attributes = [], ?Model $parent = null)
    {
        return parent::make($attributes, null);
    }

    public function newModel(array $attributes = [])
    {
        $model = $this->modelName();

        if (is_a($model, ResponseDto::class, true)) {
            return $this->getResponseModel($attributes);
        }

        return new $model($attributes);
    }

    protected function getResponseModel(array $attributes)
    {
        $response = Mockery::mock(Response::class)->makePartial();

        $response
            ->allows('body')
            ->andReturn(json_encode($attributes));

        $model = $this->modelName();

        return new $model($response);
    }
}
