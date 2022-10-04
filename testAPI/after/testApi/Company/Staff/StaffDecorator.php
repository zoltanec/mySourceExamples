<?php

namespace Test\testApi\Company\Staff {

    use Test\testApi\Core\FakeDataTrait;

    /**
     * Class StaffDecorator
     * @package Test\testApi\User
     */
    class StaffDecorator extends FakeStaff
    {
        use FakeDataTrait;

        private $wrappedFaker;

        /**
         * StaffDecorator constructor.
         *
         * @param FakeStaff $faker
         */
        public function __construct(FakeStaff $faker)
        {
            $this->wrappedFaker = $faker;
        }

        public function getWrapperFaker(): FakeStaff
        {
            return $this->wrappedFaker;
        }
    }
}