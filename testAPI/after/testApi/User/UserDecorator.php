<?php

namespace Test\testApi\User {

    use Test\testApi\Core\FakeDataTrait;

    /**
     * Class UserDecorator
     * @package Test\testApi\User
     */
    class UserDecorator extends FakeUser
    {
        use FakeDataTrait;

        private $wrappedFaker;

        /**
         * UserDecorator constructor.
         *
         * @param FakeUser $faker
         */
        public function __construct(FakeUser $faker)
        {
            $this->wrappedFaker = $faker;
        }

        public function getWrapperFaker(): FakeUser
        {
            return $this->wrappedFaker;
        }
    }
}