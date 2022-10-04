<?php

namespace Test\testApi\Company;

use Common\Model\Company\Bank;
use Common\Model\Company\Company;

class FakeCompanyBank extends CompanyDecorator
{
    /**
     * @param Company|null $company
     *
     * @return Bank
     */
    public function fakeCompanyBank(Company $company = null): Bank
    {
        //Формируем компанию с помощью билдера с адресом
        $company = $company ?? $this->getWrapperFaker()->fakeCompany();

        $companyBank = (new Bank())->setAttributes([
            'bic'                   => '1',
            'correspondent_account' => '1',
        ]);
        $companyBank->link('company', $company);

        if (!$companyBank->save()) {
            throw new \RuntimeException('Банк (компания) не создана ' . $companyBank->getJsonErrors());
        }

        //$legalAddress = $this->fakeAddress(Address::TYPE_LEGAL);
        //$company->link('legalAddress', $legalAddress);

        return $companyBank;
    }
}