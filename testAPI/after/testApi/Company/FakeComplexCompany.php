<?php


namespace Test\testApi\Company {

    use Common\Model\Company\Bank;
    use Common\Model\Company\Company;

    class FakeComplexCompany extends CompanyDecorator
    {
        private $builder;

        public function __construct(FakeCompany $company, FakeCompanyBuilder $builder)
        {
            $this->builder = $builder;
            parent::__construct($company);
        }

        /**
         * @return Company
         * @throws \Exception
         */
        public function fakeCompany(): Company
        {
            $company = $this->getWrapperFaker()->fakeCompany();

            $this->builder
                ->withCompany($company)
                ->withClient()
                ->withDebt()
                ->withBankAccount()
                ->withRegistrar()
                ->withFiles()
                ->build();
            return $company;
        }

        public function fakeCompanyBank(): Bank
        {

        }
    }
}