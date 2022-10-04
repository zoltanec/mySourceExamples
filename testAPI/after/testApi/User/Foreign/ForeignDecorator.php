<?php

namespace Test\testApi\User\Foreign {

    use Test\testApi\Core\FakeDataTrait;

    /**
     * Class PassportDecorator
     * @package Test\testApi\User
     */
    class ForeignDecorator extends FakeMigration
    {
        use FakeDataTrait;

        /** @var FakeMigration */
        private $wrappedFaker;

        /**
         * ForeignDecorator constructor.
         *
         * @param FakeMigration $faker
         */
        public function __construct(FakeMigration $faker)
        {
            $this->wrappedFaker = $faker;
        }

        public function getWrapperFaker(): FakeMigration
        {
            return $this->wrappedFaker;
        }
    }
}