<?php

namespace Common\Company\Service\Files {

    use Common\Model\Company\Company;
    use Common\Service\BaseService;

    /**
     * Class AccountingFiles
     * @package Common\Company\Service
     */
    class AccountingFiles extends BaseService
    {
        /**
         * @var FilesDirector
         */
        private $director;

        /**
         * AccountingFiles constructor.
         *
         * @param SpbDirector $director
         */
        public function __construct(
            SpbDirector $director
        ) {
            $this->director = $director;
        }

        /**
         * @param Company $company
         *
         * @return array
         */
        public function getAllDocuments(Company $company)
        {
            $this->debug("Loading list of required files for company {$company->getInn()}");

            return $this->director->getReports($company);
        }

        /**
         * @param Company $company
         * @param bool $skipSigned
         *
         * @return mixed
         */
        public function getAccountingFiles(Company $company, $skipSigned = false) {
            if ($skipSigned) {
                $documents = $this->getNotSignedFiles($company);
            } else {
                $documents = $this->director->getFiles($company);
            }

            return $documents;
        }

        /**
         * @param Company $company
         *
         * @return mixed
         */
        public function getNotSignedFiles(Company $company)
        {
            return $this->director->getNotSignedFiles($company);
        }
    }
}
