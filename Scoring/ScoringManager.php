<?php
declare(strict_types=1);

namespace Common\Service\Scoring {

    use Common\Service\BaseService;
    use Common\Service\DataMining\Supervisor;
    use Common\Service\Scoring\Plans\BasePlan;
    use Common\Warranty\Model\WarrantyOrder;
    use Common\Warranty\Service\Rate;

    class ScoringManager extends BaseService
    {
        private $warranty;
        private $dataMiner;

        private $minedData   = [];
        private $scoringData = [];
        //Значение по умолчанию, если забудем указать в плане
        private $action = BasePlan::PUSH_ORDER;
        //При значении истина - нужно собрать рейтинг если заявка не прошла стоп факторы
        private $withRating = false;

        /**
         * @var array
         */
        private $plans = [];

        /**
         * ScoringManager constructor.
         *
         * @param Supervisor $dataMiner
         */
        public function __construct(Supervisor $dataMiner)
        {
            $this->dataMiner = $dataMiner;
        }

        /**
         * @param callable $check
         *
         * @return $this
         */
        public function addPlan(callable $check)
        {
            $this->plans[] = $check;

            return $this;
        }

        /**
         * @param WarrantyOrder $wo
         *
         * @return $this
         */
        public function withWarranty(WarrantyOrder $wo)
        {
            $this->warranty = $wo;

            return $this;
        }

        //!!!Внимание, чтобы получить рейтинг, даже если не прошел стоп параметры
        //нужно в скоринг менеджере добавить вызов ->withRating()
        /**
         * @return $this
         */
        public function withRating()
        {
            $this->withRating = true;

            return $this;
        }

        public function isRating()
        {
            return $this->withRating;
        }

        /**
         * @return mixed
         */
        public function getMinedData()
        {
            return $this->minedData;
        }

        /**
         * Результат скоринга
         *
         * @return mixed
         */
        public function getScoringData()
        {
            return $this->scoringData;
        }

        /**
         * Переменные для шаблонов
         *
         * @return array
         */
        public function getTemplateData()
        {
            return array_merge($this->getMinedData(), $this->getScoringData());
        }

        /**
         * @param string $action
         */
        public function setAction(string $action)
        {
            $this->action = $action;
        }

        /**
         * @return mixed
         */
        public function getAction()
        {
            return $this->action;
        }

        /**
         * @param Supervisor $dataSource
         *
         * @return array|mixed
         */
        public function run()
        {
            $this->minedData = $this->dataMiner
                ->withWarranty($this->warranty)
                ->get();

            $result = [
                'report'  => [],
                'total'   => 0,
                'max'     => 0,
                'abcRate' => '',
                'finRate' => '',
                'badMark' => false,
            ];

            if ($this->minedData['miner.total'] !== $this->minedData['miner.completed']) {
                $this->debug("Not all miners completed: {$this->minedData['miner.total']} !== {$this->minedData['miner.completed']}");
                $this->debug("Failers: " . $this->minedData['miner.failers']);
                $this->setAction(BasePlan::SKIP_ORDER);

                return [];
            }

            $this->debug('Total scoring plans found: ' . count($this->plans));

            /** @var BasePlan $plan */
            foreach ($this->plans as $plan) {
                $this->debug('Checking plan ' . $plan->getGroupId());

                //Если сработало условие на запуск плана проверок
                if ($plan->isTriggered($this->warranty)) {
                    $this->debug('Запускаем план проверок ' . $plan->getGroupId());
                    $checksRes = $plan($this);

                    //Если срабатывает стоп параметр из маркеров плохого фин состояния
                    if($plan->getGroupId() === 'stop' && $checksRes['bad_mark'] && $checksRes['rate']) {
                        $result['badMark'] = true;
                    }

                    //Что делать с заявкой после плана
                    $this->debug('Plan ' . $plan->getGroupId() . ' result is: ' . $this->getAction());

                    if (!$this->isRating()) {
                        switch ($this->getAction()) {
                            case BasePlan::DENY_ORDER:
                            case BasePlan::SKIP_ORDER:
                                $this->debug('Scoring denied with action: ' . $this->getAction());
                                return [];
                            case BasePlan::PUSH_ORDER:
                                $this->debug('Allow request for future processing');
                                break;
                        }
                    }

                    //Записываем рейтинг
                    $groupId                    = $checksRes['groupId'];
                    $result['report'][$groupId] = $checksRes;

                    //Если в плане есть рейтинг, считаем итоговый рейтинг всех планов
                    if (isset($checksRes['total'])) {
                        $result['total'] += $checksRes['total'];
                        $result['max']   += $checksRes['max'];
                    }
                }
            }
            $result['abcRate'] = $abcRate = Rate::getAbcRate($result['total'], $result['max']);
            $result['finRate'] = Rate::getFinRate($abcRate);

            //Результаты скоринга используем в методе getTemplateData для формирования шаблонов
            $this->scoringData = ['scoring' => $result];

            return $result;
        }
    }
}
