<?php

namespace Routegroup\Imoje\Payment\Factories;

use Illuminate\Database\Eloquent\Factories\Factory as BaseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Mockery;
use Routegroup\Imoje\Payment\DTO\Responses\ResponseDto;

abstract class Factory extends BaseFactory
{
    public function create($attributes = [], Model $parent = null)
    {
        return $this->make($attributes, $parent);
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
