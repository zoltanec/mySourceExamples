<?php
declare(strict_types=1);

namespace Common\Service\Scoring\Plans {

    use Common\Core\Traits\DebugTrait;
    use Common\Service\Scoring\ScoringManager;
    use Common\Warranty\Model\WarrantyOrder;

    abstract class BasePlan
    {
        use DebugTrait;

        //Пропустить заявку в систему
        const PUSH_ORDER = 'push_order';
        //Отказать в заявке
        const DENY_ORDER = 'deny_order';
        //Ничего не делать до следующего скоринга
        const SKIP_ORDER = 'skip_order';

        /** @var array  */
        protected $checks = [];
        /** @var string */
        protected $checksGroup;

        /**
         * @return string
         */
        public function getGroupId()
        {
            return $this->checksGroup;
        }

        /**
         * ChecksMiner constructor.
         *
         * @param string $checksGroup
         */
        public function __construct(string $checksGroup)
        {
            $this->checksGroup = $checksGroup;
        }

        /**
         * @param WarrantyOrder $wo
         *
         * @return mixed
         */
        abstract public function isTriggered(WarrantyOrder $wo);

        /**
         * @param callable $check
         *
         * @return $this
         */
        public function addCheck(callable $check)
        {
            $this->checks[] = $check;

            return $this;
        }

        /**
         * @param ScoringManager $manager
         *
         * @return mixed
         */
        abstract public function __invoke(ScoringManager $manager);
    }
}
