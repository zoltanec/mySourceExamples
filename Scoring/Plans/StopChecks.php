<?php
declare(strict_types=1);

namespace Common\Service\Scoring\Plans {

    use Common\Service\Scoring\ScoringManager;
    use Common\Warranty\Model\WarrantyOrder;

    class StopChecks extends BasePlan
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

            $this->debug('Manager running with option withRating is : ' . intval($manager->isRating()));
            foreach ($this->checks as $check) {
                $check->setIsRating($manager->isRating());
                $checkRes = $check($manager->getMinedData());
                //Если не надо собирать рейтинг в случае проблем возвращаем пустой рез

                //!!!Внимание, чтобы получить рейтинг, даже если не прошел стоп параметры
                //нужно в скоринг менеджере добавить вызов ->withRating()
                if (!$manager->isRating()) {
                    if ($checkRes['rate'] === -1) {
                        $manager->setAction(static::SKIP_ORDER);
                        $this->debug('Check ' . $checkRes['name'] . ' is waiting, action is: ' . $manager->getAction());

                        return [];
                    }

                    //Если хоть одна проверка вернет true, отклоняем заявку
                    if ($checkRes['rate'] === 1) {
                        $manager->setAction(static::DENY_ORDER);
                        $this->debug('Проверка с именем ' . $checkRes['name']
                            . ' не пройдена, действие c заявкой: '
                            . $manager->getAction());

                        return [];
                    }

                    if ($checkRes['rate'] === 0) {
                        $this->debug('Check ' . $checkRes['name'] . ' passed: ' . $manager->getAction());

                        $res['checks'][] = $checkRes;
                    }
                } else {
                    $res['checks'][] = $checkRes;
                }
            }

            return $res;
        }
    }
}
