<?php


namespace Test\testApi\Core;

use Common\Util\Helper\CommonHelper;

trait FakeDataTrait
{
    public function getFakeData($attributes = []): array
    {
        $attributes = array_merge($this->getWrapperFaker()->getFakeData(), $attributes);
        return CommonHelper::toUnderscore($attributes);
    }
}