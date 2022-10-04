<?php

namespace Test\testApi\Company\Staff {

    use Common\Model\Company\BankStaff;
    use Common\Model\Company\Company;
    use Common\Model\User\User;

    class FakeBankStaff
    {
        //Может быть декоратором компании и юзера, наследовать от спец. стафф декоратора
        //сотрудники компании и сотрудники банка могут использоваться в одном тесте, как это разрулить?
        //Может быть фейкер, который декорирует fakeCompany, fakeUser, fakeStaff, fakeBankStaff
        
        /**
         * @param User $user
         * @param Company $company
         *
         * @return BankStaff
         * @throws \Exception
         */
        public function fakeBankStaff(User $user, Company $company): BankStaff
        {
            $faker = $this->getFaker();

            $staff = (new BankStaff())->setAttributes([
                'company_id' => $company->getId(),
                'user_id'    => $user->getId(),
                'position'   => $faker->sentence,
            ]);

            if (!$staff->save()) {
                throw new \RuntimeException('Сотрудник uid: '
                    . $user->getId()
                    . ' не зарегистрирован в компании '
                    . $company->getId()
                    . '. '
                    . $staff->getJsonErrors());
            }

            return $staff;
        }
    }
}