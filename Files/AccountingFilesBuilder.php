<?php
declare(strict_types=1);

namespace Common\Company\Service\Files {

    use Common\Adapter\DocumentsReportAdapter;
    use Common\Builder\BaseBuilder;
    use Common\Company\Service\BookKeepingService;
    use Common\DTO\FileDTO;
    use Common\Model\Company\Company;
    use Common\Model\Storage\File;
    use Common\Model\User\SystemUser;
    use Common\Service\BookKeeping\ReportService;
    use Common\Service\Company\Check\AccountingChecker;
    use Common\Service\DataMining\Miners\AccountingReport;

    /**
     * Class AccountingFilesBuilder
     * @package Common\Company\Service\File     */
    class AccountingFilesBuilder extends BaseBuilder
    {
        /** @var BookKeepingService  */
        private $bookKeeping;
        /** @var ReportService */
        private $reportService;
        /** @var AccountingReport */
        private $accountingReport;

        private $company;
        //Пропустить подписанные файлы
        private $skipSigned = false;

        /**
         * AccountingFiles constructor.
         *
         * @param BookKeepingService $bookKeeping
         * @param ReportService $reportService
         * @param AccountingReport $accountingReport
         */
        public function __construct(
            BookKeepingService $bookKeeping,
            ReportService      $reportService,
            AccountingReport   $accountingReport
        ) {
            $this->bookKeeping      = $bookKeeping;
            $this->reportService    = $reportService;
            $this->accountingReport = $accountingReport;
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

        //Документы подтверждающие фин. отчетность: за текущий период и предыдущие отчетные даты
        public function withReportDocs()
        {
            return $this->addCallback(function () {
                $reportDocs = $this->reportDocuments($this->getCompany(), new \DateTime());
                $this->schema = array_merge($reportDocs, $this->schema);
            });
        }

        /**
         * @param Company $company
         * @param \DateTime $currentDate
         *
         * @return array
         * @throws \Common\Service\External\Storage\Exception\BadResponseException
         * @throws \GuzzleHttp\Exception\GuzzleException
         * @throws \ReflectionException
         * @throws \yii\base\InvalidConfigException
         * @throws \yii\di\NotInstantiableException
         */
        public function reportDocuments(Company $company, \DateTime $currentDate)
        {
            $res = [];
            $days = $this->reportService->getCompanyReportDays($company, $currentDate);
            $this->debug('Days: ' . json_encode($days));

            /** @var \DateTime $day */
            foreach (array_reverse($days) as $day) {
                $this->debug("Processing data for {$day->format('Y/m/d')}");

                $type = FILE::TYPE_COMPANY_BOOKEEPING . ':' . $day->format('Y_m_d');
                if ($doc = $this->getDocument($type, $day)) {
                    $res[] = $doc;
                }
            }

            return $res;
        }

        public function withReductionAssetsDoc()
        {
            return $this->addCallback(function () {
                $report = $this->bookKeeping->report(new SystemUser(), $this->getCompany(), new \DateTime());
                /** @var AccountingChecker $accountingChecker */
                $accountingChecker = \Yii::$container->get(AccountingChecker::class);

                //Проверяем заполнен ли бух. баланс компании
                $summary = $accountingChecker->checkAccountingReport($report->toArrayOrigin(), $this->company->getInn());

                //Отчет представленный в человекопонятном виде
                $accountingData = $this->accountingReport->getAccountingFriendly($report->toArrayOrigin());

                if (empty($summary) && $this->accountingReport->getAssetsLoss($accountingData)) {
                    //обоснование снижения чистых активов более чем на 25%
                    $type = FILE::TYPE_ACCOUNTING_REDUCTION_ASSETS;
                    if ($doc = $this->getDocument($type)) {
                        $this->schema[] = $doc;
                    }
                }
            });
        }

        /**
         * @param string $type
         * @param null $day
         *
         * @return array|null
         * @throws \Common\Service\External\Storage\Exception\BadResponseException
         * @throws \GuzzleHttp\Exception\GuzzleException
         * @throws \ReflectionException
         * @throws \yii\base\InvalidConfigException
         * @throws \yii\di\NotInstantiableException
         */
        public function getDocument(string $type, $day = null)
        {
            $dto = $this->getFileDTO($type, $day);
            return $dto ? $dto->toCamelArray() : null;
        }

        /**
         * @param string $type
         * @param $day
         *
         * @return FileDTO
         * @throws \Common\Service\External\Storage\Exception\BadResponseException
         * @throws \GuzzleHttp\Exception\GuzzleException
         * @throws \ReflectionException
         * @throws \yii\base\InvalidConfigException
         * @throws \yii\di\NotInstantiableException
         */
        public function getFileDTO(string $type, $day): ?FileDTO
        {
            /** @var FileDTO $fileDTO */
            $fileDTO = (new DocumentsReportAdapter())
                ->withCompany($this->company)
                ->getFileDTO($type, $day);

            //Пропускаем подписанные файлы
            if ($this->isSkipSigned() && $fileDTO->isSigned()) {
                return null;
            }
            return $fileDTO;
        }
    }
}
