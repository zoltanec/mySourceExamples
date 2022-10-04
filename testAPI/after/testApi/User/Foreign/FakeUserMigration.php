<?php

namespace Test\testApi\User\Foreign {

    use Common\Model\User\Foreign;
    use Common\Model\User\User;

    class FakeUserMigration extends ForeignDecorator
    {
        /**
         * @param User $user
         *
         * @return array|mixed
         */
        public function getMigrationData(User $user)
        {
            return $this->getFakeData([
                'user_id' => $user->getId(),
            ]);
        }

        public function fakeMigration(User $user = null): Foreign
        {
            /** @var FakeMigration $wrapped */
            $wrapped = $this->getWrapperFaker();

            return $wrapped->createFakeEntity(function () use ($user) {
                return $this->getMigrationData($user);
            });
        }
    }
}