<?php
declare(strict_types=1);

namespace Common\Company\Service\Files {

    use Common\Model\Company\Company;
    use Common\Model\Storage\File;

    /**
     * Class SpbFilesBuilder
     * @package Common\Company\Service\Files
     */
    class SpbFilesBuilder extends AccountingFilesBuilder
    {
        public function withUsnTaxDoc()
        {
            //Эта декларация запрашивается с 4 месяца за пред год
            return $this->addCallback(function () {
                //Декларация по УСН за последний отчетный год
                if ($this->getCompany()->getTaxType() === Company::TAX_USN) {
                    $type = FILE::TYPE_ACCOUNTING_USN_TAX;
                    if ($doc = $this->getDocument($type)) {
                        $this->schema[] = $doc;
                    }
                }
            });
        }

        public function withOsnTaxDoc()
        {
            //Эта декларация запрашивается с 5 месяца за пред год, с 1-4 мес за позапрошлый
            return $this->addCallback(function () {
                //Декларация 3-НДФЛ за последний завершенный финансовый год
                if ($this->getCompany()->getTaxType() === Company::TAX_OSN) {
                    $type = FILE::TYPE_ACCOUNTING_OSN_TAX;
                    if ($doc = $this->getDocument($type)) {
                        $this->schema[] = $doc;
                    }
                }
            });
        }

        public function withIncomeTaxDoc()
        {
            //Эта декларация запрашивается с 4 месяца за пред год
            return $this->addCallback(function () {
                //Для всех: Налоговая декларация по налогу на прибыль
                // за последний завершенный финансовый год
                $type = FILE::TYPE_ACCOUNTING_INCOME_TAX;
                if ($doc = $this->getDocument($type)) {
                    $this->schema[] = $doc;
                }
            });
        }
    }
}
