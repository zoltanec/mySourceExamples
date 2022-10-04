<?php

namespace Test\testApi\User\Passport {

    use Common\Model\User\Passport;
    use Common\Model\User\User;

    class FakeUserPassport extends PassportDecorator
    {
        public function __construct(FakeForeignPassport $faker)
        {
            parent::__construct($faker);
        }

        /**
         * @param User $user
         *
         * @return array|mixed
         */
        public function getPassportData(User $user)
        {
            return $this->getFakeData([
                'user_id' => $user->getId(),
            ]);
        }

        public function fakePassport(User $user = null): Passport
        {
            /** @var FakeForeignPassport $wrapped */
            $wrapped = $this->getWrapperFaker();

            return $wrapped->createFakeEntity(function () use ($user) {
                return $this->getPassportData($user);
            });
        }

        public function fakeForeignPassport(User $user)
        {
            /** @var FakeUserPassport $wrapped */
            $wrapped = $this->getWrapperFaker();
            return $wrapped->fakeForeignPassport($user);
        }
    }
}