<?php

namespace Test\testApi\User\Foreign {

    use Common\Model\User\Foreign;
    use Faker\Generator;
    use Test\testApi\Core\BaseFaker;

    class FakeMigration extends BaseFaker
    {
        /**
         * @return array
         */
        public function getFakeData(): array
        {
            /** @var Generator $faker */
            $faker = $this->getFaker();

            return [
                'user_id' => rand(1, 100),
                'type'    => Foreign::TYPE_MIGRATION_CARD,
                'number'  => $faker->sentence,
            ];
        }

        public function fakeMigration(): Foreign
        {
            $passport = $this->createFakeEntity(function () {
                return $this->getFakeData();
            });

            return $passport;
        }

        public function createFakeEntity(\Closure $closure)
        {
            $foreignData = $closure();
            $foreign     = (new Foreign())->setAttributes($foreignData);

            if (!$foreign->save()) {
                throw new \RuntimeException('Документ нерезидента страны не создан ' . $foreign->getJsonErrors());
            }

            return $foreign;
        }
    }
}