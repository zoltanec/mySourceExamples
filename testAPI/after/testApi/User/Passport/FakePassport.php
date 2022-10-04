<?php

namespace Test\testApi\User\Passport {

    use Common\Model\User\Passport;
    use Faker\Generator;
    use Test\testApi\Core\BaseFaker;

    class FakePassport extends BaseFaker
    {
        /**
         * @return array
         */
        public function getFakeData(): array
        {
            /** @var Generator $faker */
            $faker = $this->getFaker();

            return [
                'user_id'    => rand(1, 100),
                'serial'     => rand(1000, 9999),
                'number'     => rand(100000, 999999),
                'code'       => 1,
                'region'     => $faker->sentence,
                'city'       => $faker->city,
                'address'    => $faker->address,
                'building'   => $faker->buildingNumber,
                'apart'      => $faker->sentence,
                'issuedby'   => $faker->sentence,
                'issuedDate' => strtotime('today'),
                'type'       => Passport::TYPE_PASSPORT,
            ];
        }

        public function fakePassport()
        {
            $passport = $this->createFakeEntity(function () {
                return $this->getFakeData();
            });

            return $passport;
        }

        public function createFakeEntity(\Closure $closure)
        {
            $passportData = $closure();
            $passport     = new Passport();

            if (!$passport->setAttributes($passportData)->save()) {
                throw new \RuntimeException('Паспорт пользователя не создан ' . $passport->getJsonErrors());
            }

            return $passport;
        }
    }
}