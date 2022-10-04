<?php

namespace Test\testApi\User\Passport {

    use Common\Model\User\Passport;
    use Common\Model\User\User;

    class FakeForeignPassport extends PassportDecorator
    {
        public function __construct(FakePassport $faker)
        {
            parent::__construct($faker);
        }

        public function getPassportData(User $user)
        {
            $passportData = $this->getFakeData([
                'user_id' => $user->getId(),
                'type' => Passport::TYPE_PASSPORT_INTERNATIONAL,
            ]);

            return $passportData;
        }

        public function fakeForeignPassport(User $user = null)
        {
            /** @var FakePassport $wrapped */
            $wrapped = $this->getWrapperFaker();

            return $wrapped->createfakeEntity(function () use ($user) {
                return $this->getPassportData($user);
            });
        }
    }
}