<?php

namespace Test\testApi\Core {

    abstract class Faker extends BaseFaker
    {
        abstract function createFakeEntity(\Closure $closure);

        abstract function getFakeData(): array;
    }
}