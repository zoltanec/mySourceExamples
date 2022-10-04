<?php

namespace Test\testApi\Company;

use Common\Model\Company\Company;

class FakeCompanyByInn extends CompanyDecorator
{
    /**
     * @param string $inn
     *
     * @return array
     */
    public function getCompanyData(string $inn): array
    {
        return $this->getFakeData([
            'inn' => $inn,
        ]);
    }

    /**
     * @param string|null $inn
     *
     * @return Company
     * @throws \Exception
     */
    public function fakeCompany(string $inn = null): Company
    {
        $this->getWrapperFaker()->createFakeEntity(function () use ($inn) {
            return $this->getCompanyData($inn);
        });
    }
}