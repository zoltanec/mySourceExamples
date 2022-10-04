<?php

namespace Test\testApi\Common;

use Common\Model\Address\Address;
use Test\testApi\Core\BaseFaker;

class FakeAddress extends BaseFaker
{
    /**
     * @param array $attributes
     *
     * @return array
     */
    public function fakeAddressData(array $attributes = []): array
    {
        $faker = $this->getFaker();

        return array_merge([
            'postcode' => $faker->postcode,
            'country'  => $faker->country,
            'region'   => $faker->country,
            'city'     => $faker->city,
            'district' => $faker->city,
            'locality' => $faker->city,
            'street'   => $faker->address,
            'house'    => $faker->buildingNumber,
            'type'     => Address::TYPE_PHYSICAL,
        ], $attributes);
    }

    /**
     * @param string $type
     *
     * @return Address
     * @throws \Exception
     */
    public function fakeAddress(string $type = Address::TYPE_PHYSICAL): Address
    {
        $address = (new Address())->setAttributes($this->fakeAddressData(['type' => $type]));

        if (!$address->save()) {
            throw new \RuntimeException('Адрес не создан ' . $address->getJsonErrors());
        }

        return $address;
    }
}