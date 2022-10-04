<?php

namespace Test\testApi\User\Foreign {

    use Common\Model\User\Foreign;
    use Common\Model\User\User;

    class FakeUserForeign extends ForeignDecorator
    {
        public function __construct(FakeUserMigration $faker)
        {
            parent::__construct($faker);
        }

        /**
         * @param User $user
         *
         * @return array|mixed
         */
        public function getResidenceData(User $user)
        {
            return $this->getFakeData([
                'user_id' => $user->getId(),
                'type'    => Foreign::TYPE_MIGRATION_RESIDENCE,
            ]);
        }

        /**
         * @param User|null $user
         *
         * @return Foreign
         */
        public function fakeResidence(User $user = null): Foreign
        {
            /** @var FakeUserMigration $wrapped */
            $wrapped = $this->getWrapperFaker();

            return $wrapped->createFakeEntity(function () use ($user) {
                return $this->getResidenceData($user);
            });
        }

        public function fakeMigration(User $user = null): Foreign
        {
            /** @var FakeUserMigration $wrapped */
            $wrapped = $this->getWrapperFaker();

            return $wrapped->fakeMigration($user);

        }
    }
}