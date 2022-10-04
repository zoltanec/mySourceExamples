<?php


namespace Test\testApi\Company {

    use Common\Model\Company\Company;
    use Common\Model\User\User;
    use Test\testApi\Common\ComplexFakerDecorator;

    class FakeCompanyWithContact extends ComplexFakerDecorator
    {
        /**
         * @param User|null $contactUser
         *
         * @return Company
         */
        public function fakeCompany(User $contactUser = null): Company
        {
            $company = $this->fakeCompany();

            //Создаем контакт компании
            $contactUser = $this->fakeUser();
            $company->link('contact', $contactUser);
            //$this->fakeStaff($company, $contactUser);

            return $company;
        }
    }
}