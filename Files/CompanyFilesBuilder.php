<?php
declare(strict_types=1);

namespace Common\Company\Service\Files {

    use Common\Builder\BaseBuilder;
    use Common\Model\Company\Company;

    /**
     * Class CompanyFilesBuilder
     * @package Common\Company\Service\Files
     */
    class CompanyFilesBuilder extends BaseBuilder
    {
        private $company;
        //Пропустить подписанные файлы
        private $skipSigned = false;

        /**
         * CompanyFilesBuilder constructor.
         *
         * @param Company $company
         */
        public function __construct(Company $company) {
            $this->withCompany($company);
        }

        public function withCompany(Company $company)
        {
            $this->company = $company;
            return $this;
        }

        public function withSkipSigned()
        {
            $this->skipSigned = true;
            return $this;
        }

        public function isSkipSigned(): bool
        {
            return $this->skipSigned;
        }

        public function getCompany(): Company
        {
            return $this->company;
        }

        public function withCompanyCharter()
        {
            return $this->addCallback(function () {

            });
        }

        public function withElectionProtocol()
        {
            return $this->addCallback(function () {
            });
        }

        public function withShareRegister()
        {
            return $this->addCallback(function () {
            });
        }

        public function withRoomProperty()
        {
            return $this->addCallback(function () {
            });
        }

        public function withNoTaxDebts()
        {
            return $this->addCallback(function () {
            });
        }

        public function withEgrulCheckOut()
        {
            return $this->addCallback(function () {
            });
        }

        public function withRegistrarInn()
        {
            return $this->addCallback(function () {
            });
        }

        public function withRegistrarOgrn()
        {
            return $this->addCallback(function () {
            });
        }
    }
}
