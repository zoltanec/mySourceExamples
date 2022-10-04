<?php

namespace Common\Company\Service\Files {

    use Common\Builder\BaseBuilder;
    use Common\Model\Company\Company;

    /**
     * Class CompanyFilesDirector
     * @package Common\Company\Service\Files
     */
    class CompanyFilesDirector extends FilesDirector
    {
        /** @var CompanyFilesBuilder  */
        private $filesBuilder;

        /**
         * CompanyFilesDirector constructor.
         *
         * @param CompanyFilesBuilder $filesBuilder
         */
        public function __construct(
            CompanyFilesBuilder $filesBuilder
        ) {
            $this->filesBuilder = $filesBuilder;
        }

        protected function getFilesBuilder(): BaseBuilder
        {
            return $this->filesBuilder;
        }

        /**
         * @param Company $company
         * @param BaseBuilder $fileBuilder
         *
         * @return array
         */
        protected function buildList(Company $company, BaseBuilder $fileBuilder): array
        {
            return $fileBuilder
                ->withCompany($company)
                ->build();
        }
    }
}
