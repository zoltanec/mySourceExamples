<?php


namespace Test\testApi\Company;

use Common\Model\Company\Company;
use Faker\Generator;
use Test\testApi\Core\Faker;

class FakeCompany extends Faker
{
    /**
     * @return array
     */
    public function getFakeData(): array
    {
        /** @var Generator $faker */
        $faker = $this->getFaker();

        return [
            'name'                   => $faker->company,
            'alias'                  => $faker->company . ' alias',
            'inn'                    => $this->getFakerInn(),
            'ogrn'                   => $this->getFakerOgrn(),
            'kpp'                    => $faker->sentence,
            'type_organization'      => 'commercial',
            'financial_active_debts' => $faker->randomElement([0, 1]),
            'financial_hidden_loss'  => $faker->randomElement([0, 1]),
            'financial_staff_debts'  => $faker->randomElement([0, 1]),
            'financial_tax_debts'    => $faker->randomElement([0, 1]),
            'financial_fund_debts'   => $faker->randomElement([0, 1]),
            'room_type'              => Company::TYPE_ROOM_RENT,
        ];
    }

    public function createFakeEntity(\Closure $closure)
    {
        $attributes = $closure();
        $company = (new Company())->setAttributes($attributes);

        if (!$company->save()) {
            throw new \RuntimeException('Failed to create fake company: ' . $company->getJsonErrors());
        }

        return $company;
    }

    /**
     * @return Company
     */
    public function fakeCompany(): Company
    {
        return $this->createFakeEntity(function () {
            return $this->getFakeData();
        });
    }
}