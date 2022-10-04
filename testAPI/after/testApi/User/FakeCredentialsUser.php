<?php

namespace Test\testApi\User {

    use Common\Model\User\User;

    /**
     * For Login, Registration test
     *
     * Class FakeCredentialsUser
     * @package Test\testApi\User
     */
    class FakeCredentialsUser extends UserDecorator
    {
        private $password;
        private $email;

        public function getPassword()
        {
            return $this->password;
        }

        public function setPassword(string $password)
        {
            $this->password = $password;
        }

        public function getEmail()
        {
            return $this->email;
        }

        public function setEmail(string $email)
        {
            $this->email = $email;
        }

        public function getUserData()
        {
            $attributes = [];
            if ($email = $this->getEmail()) {
                $attributes['email'] = $email;
            }
            if ($password = $this->getPassword()) {
                $attributes['password'] = $password;
            }

            return $this->getFakeData($attributes);
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