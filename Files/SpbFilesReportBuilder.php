<?php
declare(strict_types=1);

namespace Common\Company\Service\Files {

    use Common\Adapter\DocumentsReportAdapter;
    use Common\DTO\Company\BookKeeping\DocumentReportDTO;

    /**
     * Class SpbFilesReportBuilder
     * @package Common\Company\Service\Files
     */
    class SpbFilesReportBuilder extends SpbFilesBuilder
    {
        /**
         * @param string $type
         * @param null $day
         *
         * @return DocumentReportDTO
         */
        public function getDocument(string $type, $day = null): DocumentReportDTO
        {
            return $this->getReportDTO($type, $day);
        }

        /**
         * @param string $type
         * @param $day
         *
         * @return DocumentReportDTO
         */
        public function getReportDTO(string $type, ?\DateTime $day): DocumentReportDTO
        {
            /** @var DocumentReportDTO */
            return (new DocumentsReportAdapter())
                ->withCompany($this->getCompany())
                ->getDocumentReport($type, $day);
        }
    }
}
