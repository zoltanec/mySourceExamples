<?php
declare(strict_types=1);

namespace Test\testApi\Warranty {

    use Common\Model\Company\Company;
    use Common\Model\User\User;
    use Test\testApi\Common\SimpleApi;

    class WarrantyApi extends SimpleApi
    {
        public $faker;

        public function __construct(FakeWarranty $faker)
        {
            $this->faker = $faker;
        }

        //Фейкер через который мы будем методы вложенных фейкеров через вложенные вызовы __call
        protected function getFaker()
        {
            return $this->faker;
        }

        public function givenWarranty()
        {
            $user    = $this->fakeUser();
            $company = $this->fakeCompany();

            $this->fakeStaff($company, $user);

            return $this->fakeWarranty($user, $company);
        }
    }
}