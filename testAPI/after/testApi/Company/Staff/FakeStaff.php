<?php

namespace Test\testApi\Company\Staff {

    use Common\Staff\Model\CompanyStaff;
    use Test\testApi\Core\Faker;

    class FakeStaff extends Faker
    {
        public function getFakeData(): array
        {
            $faker = $this->getFaker();

            return [
                'company_id'    => rand(1, 100),
                'user_id'       => rand(1, 100),
                'position'      => $faker->sentence,
                'share_rubles'  => $faker->sentence,
                'share_percent' => rand(0, 100),
                'isFounder'     => $faker->randomElement(['0', '1']),
                'isDirector'    => $faker->randomElement(['0', '1']),
                'isDeleted'     => 0,
            ];
        }

        /**
         * @return CompanyStaff
         */
        public function fakeStaff(): CompanyStaff
        {
            return $this->createFakeEntity(function () {
                return $this->getFakeData();
            });
        }

        /**
         * @param \Closure $closure
         *
         * @return CompanyStaff
         */
        public function createFakeEntity(\Closure $closure): CompanyStaff
        {
            $attributes = $closure();
            $staff      = (new CompanyStaff())->setAttributes($attributes);

            if (!$staff->save()) {
                throw new \RuntimeException('Сотрудник uid:'
                    . $attributes['user_id'] . ' не зарегистрирован в компании '
                    . $attributes['company_id'] . ' '
                    . $staff->getJsonErrors());
            }

            return $staff;
        }
    }
}