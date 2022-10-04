<?php

namespace Common\Warranty\Service\Bank {

    use Common\Exception\ForbiddenException;
    use Common\Model\User\User;
    use Common\Service\BaseService;
    use Common\Service\Scoring\ScoringManager;
    use Common\Service\User\Permissions\Dispatcher;
    use Common\Service\User\Permissions\PermissionService;
    use Common\Warranty\Model\WarrantyOrder;
    use Common\Exception\NotFoundException;
    use Common\Warranty\Service\WarrantyService;

    /**
     * Class GetWarrantyScoring
     * @package Common\Warranty\Service\Bank
     */
    class GetWarrantyScoring extends BaseService
    {
        /** @var WarrantyService */
        private $warrantyService;
        /** @var ScoringManager  */
        private $scoringManager;
        /** @var PermissionService */
        private $permissionService;

        /**
         * GetWarrantyScoring constructor.
         *
         * @param WarrantyService $warrantyService
         * @param ScoringManager $scoringManager
         * @param PermissionService $permissionService
         */
        public function __construct(
            WarrantyService $warrantyService,
            ScoringManager $scoringManager,
            PermissionService $permissionService
        ) {
            $this->warrantyService = $warrantyService;
            $this->scoringManager  = $scoringManager;
            $this->permissionService = $permissionService;
        }

        /**
         * @param string $warrantyId
         * @param User|null $user
         * @return array
         * @throws ForbiddenException
         * @throws NotFoundException
         * @throws \Common\Exception\Http\BadRequestHttpException
         * @throws \yii\base\InvalidConfigException
         * @throws \yii\di\NotInstantiableException
         */
        public function scoring(string $warrantyId, User $user = NULL): array
        {
            $this->debug('Starting scoring request');

            /** @var WarrantyOrder $warranty */
            $warrantyOrder = $this->warrantyService->getWarrantyOrderOrException($warrantyId);

            /** @var Dispatcher $processor */
            $processor = $user->permissions();
            if (!ShowItem::checkItemAccess($warrantyOrder, $processor)) {
                throw new ForbiddenException("Access to {$warrantyId} denied!");
            }

            $scoring = $this->scoringManager
                ->withWarranty($warrantyOrder)
                ->withRating()
                ->run();

            return $scoring;
        }
    }
}
