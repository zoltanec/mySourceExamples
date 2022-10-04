<?php

namespace Test\testApi\Company\Staff {

    use Common\Model\Company\Company;
    use Common\Model\User\User;
    use Common\Staff\Model\StaffRequest;
    use Test\testApi\Common\ComplexFakerDecorator;

    class FakeStaffRequest extends ComplexFakerDecorator
    {
        /**
         * @param User|null $user
         * @param Company|null $company
         *
         * @return StaffRequest
         * @throws \yii\base\Exception
         */
        public function fakeStaffRequest(User $user = null, Company $company = null)
        {
            $company = $company ?? $this->fakeCompany();
            $user    = $user ?? $this->fakeUser();

            $staffRequest = (new StaffRequest())->setAttributes([
                'user_id'     => $user->getId(),
                'company_inn' => $company->getInn(),
            ]);

            if (!$staffRequest->save()) {
                throw new \RuntimeException('Запрос не создан: добавление сотрудника uid: '
                    . $user->getId() . ' в компанию '
                    . $company->getId() . ' '
                    . $staffRequest->getJsonErrors());
            }

            return $staffRequest;

        }
    }
}