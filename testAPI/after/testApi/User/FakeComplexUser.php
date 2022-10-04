<?php


namespace Test\testApi\User {

    use Common\Model\User\User;

    class FakeComplexUser extends UserDecorator
    {
        private $builder;

        public function __construct(FakeUser $faker, UserBuilder $builder)
        {
            $this->builder = $builder;
            parent::__construct($faker);
        }

        /**
         * @return User
         * @throws \yii\base\Exception
         */
        public function fakeUser(): User
        {
            $user = $this->getWrapperFaker()->fakeUser();

            $this->builder
                ->withUser($user)
                ->withPassport()
                ->withForeign()
                ->withMigrationCard()
                ->withMigrationResidence()
                ->build();

            return $user;
        }
    }
}