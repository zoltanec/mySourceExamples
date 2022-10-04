<?php


namespace Test\testApi\Company;

use Common\Model\BookKeeping\Line;
use Common\Model\BookKeeping\Report;
use Common\Model\Company\Company;
use Common\Service\BookKeeping\Repository\ReportRepository;

class FakeReport
{
    //Может быть декоратором билдера компании
    /**
     * @param $date
     * @param Company $company
     * @return array
     * @throws \Common\Exception\ValidationException
     */
    protected function fakeReport($date, Company $company): array
    {
        $lines = Line::find()->limit(6)->all();
        $data  = [];

        if (!is_array($date)) {
            $date = [$date];
        }

        foreach ($date as $_date) {
            $this->fakeSingleReport($lines, $_date, $company, $data);
        }

        $repository = new ReportRepository();
        if (!$repository->batch($data, ['date', 'book_keeping_line_id', 'company_id', 'value', 'created_at'])) {
            throw new \RuntimeException('Batch insert вернул 0 для сохранения финансовой отчетности');
        }

        return $data;
    }

    /**
     * @param array     $lines
     * @param \DateTime $date
     * @param Company   $company
     * @param           $data
     */
    protected function fakeSingleReport(array $lines, \DateTime $date, Company $company, &$data)
    {
        foreach ($lines as $line) {
            $data[] = (new Report())->setAttributes([
                'date'                 => $date->format('Y-m-d'),
                'book_keeping_line_id' => $line->getId(),
                'company_id'           => $company->getId(),
                'value'                => array_rand([mt_rand(0, 2000), null]),
                'created_at'           => time(),
            ]);
        }
    }
}