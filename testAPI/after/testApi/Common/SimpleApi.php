<?php
declare(strict_types=1);

namespace Test\testApi\Common {

    use Test\testApi\Core\TestApi;

    abstract class SimpleApi extends TestApi
    {
        //Фейкер через который мы будем методы вложенных фейкеров через вложенные вызовы __call
        abstract protected function getFaker();

        //Чтобы из любого api можно было
        // напрямую через $this вызывать метод любого фейкера
        public function __call($name, $arguments)
        {
            return call_user_func_array([$this->getFaker(), $name], $arguments);
        }
    }
}