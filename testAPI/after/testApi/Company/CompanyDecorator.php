<?php

namespace Test\testApi\Company {

    use Test\testApi\Core\FakeDataTrait;

    /**
     * Class CompanyDecorator
     * @package Test\testApi\Company
     */
    class CompanyDecorator extends FakeCompany
    {
        use FakeDataTrait;

        private $wrappedFaker;

        /**
         * CompanyDecorator constructor.
         *
         * @param FakeCompany $faker
         */
        public function __construct(FakeCompany $faker)
        {
            $this->wrappedFaker = $faker;
        }

        public function getWrapperFaker(): FakeCompany
        {
            return $this->wrappedFaker;
        }
    }
}