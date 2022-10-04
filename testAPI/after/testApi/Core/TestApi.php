<?php
declare(strict_types=1);

namespace Test\testApi\Core {

    use Common\Core\Traits\DebugTrait;
    use Common\Core\Traits\SerializerTrait;
    use Test\TestCase;

    abstract class TestApi extends TestCase
    {
        use DebugTrait;
        use SerializerTrait;

        protected $listSize;

        public function getListSize()
        {
            return $this->listSize;
        }

        /**
         * @param int $size
         */
        public function setListSize(int $size)
        {
            $this->listSize = $size;
        }
    }
}