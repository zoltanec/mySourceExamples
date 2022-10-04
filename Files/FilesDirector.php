<?php
declare(strict_types=1);

namespace Common\Company\Service\Files {

    use Common\Builder\BaseBuilder;
    use Common\Model\Company\Company;
    use Common\Service\BaseService;

    //abstraction for all files list in project
    /**
     * Class FilesDirector
     * @package Common\Company\Service\Files
     */
    abstract class FilesDirector extends BaseService
    {
        /**
         * @param Company $company
         * @param BaseBuilder $fileBuilder
         *
         * @return array
         */
        abstract protected function buildList(Company $company, BaseBuilder $fileBuilder): array;

        /**
         * @return AccountingFilesBuilder
         */
        abstract protected function getFilesBuilder(): BaseBuilder;

        /**
         * @param Company $company
         *
         * @return array
         */
        public function getFiles(Company $company): array
        {
            return $this->buildList($company, $this->getFilesBuilder());
        }

        /**
         * @param Company $company
         *
         * @return array
         */
        public function getNotSignedFiles(Company $company): array
        {
            //Пропускаем подписанные файлы
            $builder = $this->getFilesBuilder()->withSkipSigned();
            return $this->buildList($company, $builder);
        }
    }
}
