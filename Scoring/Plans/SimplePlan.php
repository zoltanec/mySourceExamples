<?php
declare(strict_types=1);

namespace Common\Service\Scoring\Plans {

    use Common\Service\Scoring\ScoringManager;
    use Common\Warranty\Model\WarrantyOrder;

    class SimplePlan extends BasePlan
    {
        /**
         * @param WarrantyOrder $wo
         *
         * @return bool
         */
        public function isTriggered(WarrantyOrder $wo) {
            if ($wo->company()) {
                return true;
            }
            return false;
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
         * @param ScoringManager $manager
         *
         * @return array|mixed
         */
        public function __invoke(ScoringManager $manager)
        {
            $manager->setAction(static::PUSH_ORDER);
            $res = [
                'groupId' => $this->getGroupId(),
                'checks'  => [],
            ];

            foreach ($this->checks as $check) {
                $check->setIsRating($manager->isRating());
                $checkRes = $check($manager->getMinedData());
                $res['checks'][] = $checkRes;
            }

            return $res;
        }
    }
}
