<?php

namespace Common\Warranty\Service {

    use Common\Core\Traits\RandomTrait;
    use Common\Exception\NotFoundException;
    use Common\Model\Company\Company;
    use Common\Service\BaseService;
    use Common\Model\User\User;
    use Common\Service\External\AuctionLoader\BadResponseException;
    use Common\Service\External\Egrul\EgrulClient;
    use Common\Service\External\Egrul\EgrulClientInterface;
    use Common\Warranty\Model\WarrantyOrder;
    use Common\Service\External\AuctionLoader\AuctionLoaderClientInterface;
    use Common\Exception\Http\BadRequestHttpException;

    /**
     * Class WarrantyCompanyService
     * @package Common\Service\Warranty
     */
    class WarrantyCreateService extends BaseService
    {
        use RandomTrait;

        /** @var AuctionLoaderClientInterface  */
        protected $auctionLoaderClient;
        /** @var WarrantyService  */
        protected $warrantyService;
        /** @var EgrulClientInterface */
        protected $egrul;

        const STATUS_ERROR = 'ERROR';

        /**
         * WarrantyCreateService constructor.
         *
         * @param AuctionLoaderClientInterface $auctionLoaderClient
         * @param WarrantyService $warrantyService
         * @param EgrulClientInterface $egrul
         */
        public function __construct(
            AuctionLoaderClientInterface $auctionLoaderClient,
            WarrantyService $warrantyService,
            EgrulClientInterface $egrul
        ) {
            $this->auctionLoaderClient = $auctionLoaderClient;
            $this->warrantyService = $warrantyService;
            $this->egrul = $egrul;
        }

        /**
         * @param User $user
         * @param array $tenderInfo
         * @return WarrantyOrder
         * @throws BadRequestHttpException
         */
        public function createOrder(User $user, array $tenderInfo): WarrantyOrder
        {
            $warrantyOrder = false;

            if ($user->isClient()) {
                //Если есть черновики у пользователя, перезапишем черновик
                $warrantyOrder = $this->warrantyService->getUserDraft($user->getId());
            }

            if (!$warrantyOrder) {
                $warrantyOrder = new WarrantyOrder();
            }

            $warrantyOrder->setAttributes([
                'tender_id'   => $tenderInfo['tenderId'],
                'user_id'     => $user->id,
                'bank_id'     => 2,
                'warranty_id' => $this->genWarrantyId(),
                'tender_info' => $tenderInfo
            ]);

            if (!$warrantyOrder->validate()) {
                throw new BadRequestHttpException('Validate exception for warranty order');
            }
            if (!$warrantyOrder->save()) {
                throw new BadRequestHttpException('Can\'t save warranty order');
            }

            return $warrantyOrder;
        }

        /**
         * @return string
         */
        public function genWarrantyId(): string
        {
            $warrantyId     = $this->getTextId();
            $warrantyExists = $this->warrantyService->getWarrantyOrder($warrantyId);
            if ($warrantyExists) {
                $warrantyId = $this->genWarrantyId();
            }

            return $warrantyId;
        }

        //Получить краткую информацию о бенефициаре
        /**
         * @param string $beneficiaryInn
         *
         * @return array
         * @throws \GuzzleHttp\Exception\GuzzleException
         */
        public function getBeneficiaryInfo(string $beneficiaryInn)
        {
            $reply = [];
            try {
                $beneficiaryReply = $this->egrul->getShortInfo($beneficiaryInn);
                $beneficiaryInfo  = $beneficiaryReply->getResponse();

                //КПП берем из данных аукциона, в ГКС нет кпп
                $keys = [
                    'okpo',
                    'ogrn',
                    'oktmo',
                    'address',
                    'legalForm',
                    'name',
                    'alias',
                    'inn',
                    'okved',
                ];
                foreach ($keys as $key) {
                    $reply[$key] = $beneficiaryInfo[$key];
                }
            } catch (\Exception $exception) {
                $this->debug($exception->getMessage());
                // Даже если удалось загрузить данные бенефициара,
                // в экшене warrantyCreate всегда будет создан запрос в егрюл, чтобы добавить компанию
            }

            return $reply;
        }

        /**
         * @param Company $company
         *
         * @return array
         * @throws NotFoundException
         */
        public function getBeneficiaryInfoFromCompany(Company $company): array
        {
            $legalAddress = $company->legalAddress();
            if (!$legalAddress || empty($legalAddress->getFull())) {
                throw new NotFoundException('Бенефициар загружен, но без юридического адреса!');
            }

            return [
                'okpo'      => $company->okpo,
                'ogrn'      => $company->ogrn,
                'okved'     => $company->okved,
                'oktmo'     => $company->oktmo,
                'address'   => $legalAddress->getFull(),
                'legalForm' => $company->legal,
                'name'      => $company->name,
                'alias'     => $company->alias,
                'inn'       => $company->inn,
                'kpp'       => $company->kpp,
            ];
        }

        /**
         * @param string $tenderId
         * @return array
         * @throws BadRequestHttpException
         * @throws BadResponseException
         * @throws \Common\Service\Warranty\Exception\BadResponseException
         */
        public function getInfo(string $tenderId): array
        {
            if (preg_match('#[^0-9]+#', $tenderId, $match)) {
                throw new BadRequestHttpException('TenderId must be digital');
            }

            $auctionResponse = $this->auctionLoaderClient->check(['item' => $tenderId]);
            $response = $auctionResponse->getResponse();

            if ($auctionResponse->getStatus() === static::STATUS_ERROR) {
                throw new BadResponseException($response['message']);
            }

            if (!isset($response['lots'])) {
                throw new BadRequestHttpException('Ошибка, неправильный ответ от сервиса, лоты отсутствуют!');
            }

            return $response;
        }
    }
}
