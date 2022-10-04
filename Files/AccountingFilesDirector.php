<?php

namespace Common\Company\Service\Files;

use Common\Builder\BaseBuilder;
use Common\Model\Company\Company;

abstract class AccountingFilesDirector extends FilesDirector
{
    abstract protected function getReportBuilder(): BaseBuilder;

    public function getReports(Company $company) {
        $builder = $this->getReportBuilder();
        return $this->buildList($company, $builder);
    }
}