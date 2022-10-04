<?php

namespace Test\testApi\User\Passport {

    use Test\testApi\Core\FakeDataTrait;

    /**
     * Class PassportDecorator
     * @package Test\testApi\User
     */
    class PassportDecorator extends FakePassport
    {
        use FakeDataTrait;

        /** @var FakePassport */
        private $wrappedFaker;

        /**
         * PassportDecorator constructor.
         *
         * @param FakePassport $faker
         */
        public function __construct(FakePassport $faker)
        {
            $this->wrappedFaker = $faker;
        }

        public function getWrapperFaker(): FakePassport
        {
            return $this->wrappedFaker;
        }
    }
}