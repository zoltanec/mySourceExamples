<?php

namespace Test\testApi\Company;

use Common\Builder\BaseBuilder;
use Common\Model\Company\BankAccount;
use Common\Model\Company\Client;
use Common\Model\Company\Company;
use Common\Model\Company\Debt;
use Common\Model\Company\Registrar;
use Common\Warranty\Model\Validator\Company\CompanyDocumentsValidator;
use Test\testApi\Core\FileTrait;
use Test\Traits\FakerTrait;

class FakeCompanyBuilder extends BaseBuilder
{
    use FakerTrait, FileTrait;

    private $company;

    private function getCompany()
    {
        return $this->company;
    }

    public function __construct()
    {

    }

    /**
     * @param Company $company
     *
     * @return $this
     */
    public function withCompany(Company $company)
    {
        $this->company = $company;

        return $this;
    }

    public function withClient()
    {
        return $this->addCallback(function () {
            $companyClient = (new Client())->setAttributes([
                'goal_bank_guarantee' => false,
                'goal_bank_credit'    => true,
            ]);

            $companyClient->link('company', $this->company);

            if (!$companyClient->save()) {
                throw new \RuntimeException('Клиент (компания) не создан ' . $companyClient->getJsonErrors());
            }
        });
    }

    public function withDebt()
    {
        return $this->addCallback(function () {
            $debt = new Debt();
            $type = Debt::TYPE_TAX;

            $debt->setAttributes([
                'company_id' => $this->getCompany()->getId(),
                'type'       => $type,
                'amount'     => 100,
                'creditor'   => $type === Debt::TYPE_STAFF ? '' : 'Creditor',
                'started_at' => time(),
            ]);

            if (!$debt->save()) {
                throw new \RuntimeException('Задолженность не создана ' . $debt->getJsonErrors());
            }
        });
    }

    public function withBankAccount()
    {
        return $this->addCallback(function () {
            $faker = $this->getFaker();

            $account = new BankAccount();
            $account->setAttributes([
                'company_id'      => $this->getCompany()->id,
                'bic'             => $faker->sentence,
                'number'          => $faker->sentence,
                'name'            => $faker->sentence,
                'unpaid_invoices' => 1,
            ]);

            if (!$account->save()) {
                throw new \RuntimeException('Банковский аккаунт создан не был ' . $account->getJsonErrors());
            }
        });
    }

    public function withFiles()
    {
        return $this->addCallback(function () {
            $fakeCompany = $this->getCompany();

            foreach (CompanyDocumentsValidator::getFileTypes() as $fileType) {
                $this->fakeFile($fakeCompany->getId(), $fileType);
            }
        });
    }

    public function withRegistrar()
    {
        return $this->addCallback(function () {
            $faker = $this->getFaker();

            $registrar = (new Registrar())->setAttributes([
                'company_id' => $this->getCompany()->getId(),
                'number'     => $faker->sentence,
                'name'       => $faker->name,
                'address'    => $faker->sentence,
                'date'       => date('Y-m-d'),
            ]);

            if (!$registrar->save()) {
                throw new \RuntimeException('Регистратор не создан '
                    . $registrar->getJsonErrors());
            }
        });
    }
}