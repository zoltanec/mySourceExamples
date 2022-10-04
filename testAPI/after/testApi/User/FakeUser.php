<?php

namespace Test\testApi\User {

    use Common\Model\User\User;
    use Common\Service\User\Interfaces\NationalityInterface;
    use Common\Service\User\Interfaces\ResidentInterface;
    use Common\Util\Helper\CommonHelper;
    use Faker\Generator;
    use Test\testApi\Core\Faker;

    class FakeUser extends Faker
    {
        /**
         * @return array
         */
        public function getFakeData(): array
        {
            /** @var Generator $faker */
            $faker = $this->getFaker();

            return [
                'email'              => $faker->safeEmail,
                'password'           => $faker->password(),
                'status'             => User::STATUS_ACTIVE,
                'firstName'          => ucfirst($faker->firstName),
                'surname'            => ucfirst($faker->lastName),
                'patronymic'         => ucfirst($faker->streetName),
                'role'               => User::ROLE_CLIENT,
                'inn'                => $this->getFakerInn(),
                'phone'              => preg_replace('/[^0-9]/', '', $faker->phoneNumber),
                'birthDate'          => $faker->unixTime($max = 'now'),
                'birthPlace'         => $faker->streetAddress,
                'nationality'        => NationalityInterface::NATIONALITY_RUSSIAN,
                'isResident'         => ResidentInterface::RESIDENT,
                'isPassportLocation' => 1,
            ];
        }

        public function getUserData()
        {
            return CommonHelper::toUnderscore($this->getFakeData());
        }

        /**
         * @return User
         * @throws \yii\base\Exception
         */
        public function fakeUser(): User
        {
            return $this->createFakeEntity(function () {
                return $this->getUserData();
            });
        }

        /**
         * @param \Closure $closure
         *
         * @return User
         * @throws \yii\base\Exception
         */
        public function createFakeEntity(\Closure $closure)
        {
            $attrs = $closure();
            $user = (new User())->setAttributes($attrs);

            if (!$user->hashPasswordField()->save()) {
                throw new \RuntimeException('Пользователь не создан ' . $user->getJsonErrors());
            }

            return $user;
        }
    }
}