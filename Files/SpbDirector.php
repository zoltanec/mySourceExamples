<?php
declare(strict_types=1);

namespace Common\Company\Service\Files {

    use Common\Model\Company\Company;

    /**
     * Class SpbDirector
     * @package Common\Company\Service\Files
     */
    class SpbDirector extends AccountingFilesDirector
    {
        /** @var SpbFilesBuilder  */
        private $filesBuilder;
        /** @var SpbFilesReportBuilder */
        private $reportBuilder;

        /**
         * SpbDirector constructor.
         *
         * @param SpbFilesBuilder $filesBuilder
         * @param SpbFilesReportBuilder $reportBuilder
         */
        public function __construct(
            SpbFilesBuilder $filesBuilder,
            SpbFilesReportBuilder $reportBuilder
        ) {
            $this->filesBuilder  = $filesBuilder;
            $this->reportBuilder = $reportBuilder;
        }

        protected function getFilesBuilder()
        {
            return $this->filesBuilder;
        }

        protected function getReportBuilder()
        {
            return $this->reportBuilder;
        }

        /**
         * @param Company $company
         * @param AccountingFilesBuilder $fileBuilder
         *
         * @return array
         */
        protected function buildList(Company $company, AccountingFilesBuilder $fileBuilder): array
        {
            return $this->getPetersburgList($company, $fileBuilder);
        }

        //For bank "sant petersburg nessesary files
        /**
         * @param Company $company
         * @param AccountingFilesBuilder $fileBuilder
         *
         * @return array
         */
        private function getPetersburgList(Company $company, AccountingFilesBuilder $fileBuilder): array
        {
            return $fileBuilder
                ->withCompany($company)
                ->withOsnTaxDoc()
                ->withIncomeTaxDoc()
                ->withUsnTaxDoc()
                ->withReportDocs()
                ->withReductionAssetsDoc()
                ->build();
        }
    }
}
