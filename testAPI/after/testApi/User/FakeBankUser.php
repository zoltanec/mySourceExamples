<?php

namespace Test\testApi\User {

    use Common\Model\User\User;

    class FakeBankUser extends UserDecorator
    {
        public function getUserData()
        {
            return $this->getFakeData([
                'role' => User::ROLE_BANK,
            ]);
        }

        /**
         * @return User
         * @throws \yii\base\Exception
         */
        public function fakeUser(): User
        {
            $wrapped = $this->getWrapperFaker();
            return $wrapped->createFakeEntity(function () {
                return $this->getUserData();
            });
        }
    }
}