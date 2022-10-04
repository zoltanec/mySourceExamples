<?php
declare(strict_types=1);

namespace Common\Service\Scoring\Plans {

    use Common\Service\Scoring\ScoringManager;
    use Common\Warranty\Model\WarrantyOrder;

    class RatingPlan extends BasePlan
    {
        /**
         * @param WarrantyOrder $wo
         *
         * @return bool
         */
        public function isTriggered(WarrantyOrder $wo) {
            return true;
        }

        /**
         * RatingPlan constructor.
         *
         * @param string $checksGroup
         */
        public function __construct(string $checksGroup)
        {
            parent::__construct($checksGroup);
        }

        /**
         * В этом любом плане всегда по умолчанию пропускаем заявку в систему
         *
         * @param ScoringManager $manager
         *
         * @return array|mixed
         */
        public function __invoke(ScoringManager $manager)
        {
            $checks = [];

            foreach ($this->checks as $check) {
                $check->setIsRating($manager->isRating());
                $checks[] = $check($manager->getMinedData());
            }

            $res = [
                'groupId' => $this->getGroupId(),
                'total'   => 0,
                'max'     => 0,
                'checks'  => $checks,
            ];

            //Делаем итоговый подсчет проверок плана
            foreach ($checks as $check) {
                $res['total'] += $check['rate'];
                $res['max']   += $check['max'];
            }

            $manager->setAction(static::PUSH_ORDER);

            return $res;
        }
    }
}
