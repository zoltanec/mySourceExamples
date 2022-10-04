<?php

namespace Test\testApi\Company\Staff {

    use Common\Model\Company\Company;
    use Common\Model\User\User;
    use Common\Staff\Model\CompanyStaff;

    class FakeCompanyStaff extends StaffDecorator
    {
        /**
         * @param Company $company
         * @param User $user
         *
         * @return array
         */
        public function getStaffData(Company $company, User $user)
        {
            return $this->getFakeData([
                'company_id' => $company->getId(),
                'user_id'    => $user->getId(),
            ]);
        }

        /**
         * @param Company $company
         * @param User $user
         *
         * @return CompanyStaff
         */
        public function fakeStaff(Company $company = null, User $user = null): CompanyStaff
        {
            $wrapped = $this->getWrapperFaker();

            return $wrapped->createFakeEntity(function () use ($company, $user) {
                return $this->getStaffData($company, $user);
            });
        }
    }
}