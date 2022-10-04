<?php

namespace Test\testApi\User {

    use Common\Builder\BaseBuilder;
    use Common\Model\User\User;
    use Test\testApi\User\Foreign\FakeUserForeign;
    use Test\testApi\User\Passport\FakeUserPassport;

    class UserBuilder extends BaseBuilder
    {
        /** @var User */
        private $user;
        /** @var FakeUserPassport */
        private $passport;
        /** @var FakeUserForeign */
        private $foreign;

        private function getUser()
        {
            return $this->user;
        }

        /**
         * UserBuilder constructor.
         *
         * @param FakeUserPassport $passport
         * @param FakeUserForeign $foreign
         */
        public function __construct(FakeUserPassport $passport, FakeUserForeign $foreign)
        {
            $this->passport = $passport;
            $this->foreign = $foreign;
        }

        public function withUser(User $user)
        {
            $this->user = $user;
            return $this;
        }

        public function withPassport()
        {
            return $this->addCallback(function () {
                $this->passport->fakePassport($this->getUser());
            });
        }

        public function withForeign()
        {
            return $this->addCallback(function () {
                $this->passport->fakeForeignPassport($this->getUser());
            });
        }

        public function withMigrationCard()
        {
            return $this->addCallback(function () {
                $this->foreign->fakeMigration($this->getUser());
            });
        }

        public function withMigrationResidence()
        {
            return $this->addCallback(function () {
                $this->foreign->fakeResidence($this->getUser());
            });
        }
    }
}