<?php

namespace Common\Warranty\Service {

    use Common\Core\Traits\DebugTrait;
    use Common\DTO\FileDTO;
    use Common\Event\Request\EventBindFile;
    use Common\Listener\Request\RequestFileListener;
    use Common\Model\Storage\File;
    use Common\Warranty\Model\WarrantyMessage;
    use Common\Warranty\Model\WarrantyOrder;
    use Common\Exception\NotFoundException;
    use Common\Exception\ValidationException;
    use Common\Warranty\Service\Repository\WarrantyRepository;

    /**
     * Class WarrantyMessageService
     * @package Common\Service\Warranty
     */
    class WarrantyMessageService
    {
        use DebugTrait;

        /**
         * @var WarrantyService
         */
        private $warrantyService;

        /**
         * @var WarrantyRepository
         */
        private $warrantyRepository;

        /**
         * WarrantyMessageService constructor.
         * @param WarrantyService $warrantyService
         * @param WarrantyRepository $warrantyRepository
         */
        public function __construct(WarrantyService $warrantyService, WarrantyRepository $warrantyRepository)
        {
            $this->warrantyService    = $warrantyService;
            $this->warrantyRepository = $warrantyRepository;
        }

        /**
         * @param string $warrantyId
         * @param int $messageId
         * @return WarrantyMessage
         * @throws \Common\Exception\NotFoundException
         */
        public function getMessageOrException(string $warrantyId, int $messageId): WarrantyMessage
        {
            $this->debug("Loading {$warrantyId} / {$messageId}");
            $warranty = WarrantyOrder::find()->where([
                'warranty_id' => $warrantyId,
                'messages'    => [
                    '$elemMatch' => [
                        'message_id' => $messageId,
                    ],
                ],
            ])->one();

            if (!$warranty) {
                throw new NotFoundException('Message #' . $messageId . ' not found in warranty #' . $warrantyId);
            }
            $this->debug("Got warranty: " . $warranty->getId());

            $message = array_filter($warranty->getMessages(), function ($msg) use ($messageId) {
                return (intval($msg['message_id']) === intval($messageId));
            })[0];

            $this->debug("Message: " . json_encode($message));

            return (new WarrantyMessage())->setAttributes($message + [
                'warranty_id' => $warrantyId,
            ]);
        }

        /**
         * @param WarrantyOrder $warranty
         * @param array $params
         *
         * @return WarrantyMessage
         * @throws ValidationException
         */
        public function create(WarrantyOrder $warranty, array $params)
        {
            $params['content']['attachments'] = array_filter($params['content']['attachments'], function ($item) {
                $result = (!empty($item['id']));
                $this->debug("Filter status: " . var_export($result, true) . ", content: " . json_encode($item));

                return $result;
            });

            $message = new WarrantyMessage();
            $message->setCreatedTime();
            $message->message_id = count($warranty->getMessages());

            $this->debug("Total messages available: " . count($warranty->getMessages()) . ", messageId: " . $message->message_id);
            $this->initAndValidate($message, $params);

            $this->debug("Running push messaage method for {$warranty->getId()}");
            $this->debug("Total attachments count: " . count($message->getAttachments()));

            foreach ($message->getAttachments() as $file) {
                $DTO = new FileDTO();
                $DTO->setId($file['id']);
                \Yii::$app->trigger(RequestFileListener::REQUEST_FILE_UPLOAD, new EventBindFile($message, File::TYPE_WARRANTY_MESSAGE_ATTACHMENT, $DTO));
            }

            return $this->pushMessage($warranty, $message);
        }

        /**
         * @param WarrantyOrder $warranty
         * @param array $params
         * @param WarrantyMessage|null $message
         * @return WarrantyMessage
         * @throws ValidationException
         */
        public function update(WarrantyOrder $warranty, array $params, WarrantyMessage $message = null)
        {
            $this->debug("Total messages available: " . count($warranty->getMessages()) . ", messageId: " . $message->message_id);
            $this->initAndValidate($message, $params);

            $this->debug("Running update message for {$warranty->getId()}");
            $this->debug("Total attachments count: " . count($message->getAttachments()));
            return $this->updateMessage($warranty, $message);
        }

        /**
         * @param WarrantyMessage $message
         * @param array $params
         *
         * @throws ValidationException
         */
        private function initAndValidate(WarrantyMessage $message, array $params)
        {
            $message->setAttributes($params);

            if (!$message->validate()) {
                $this->debug($message->getErrors());
                throw new ValidationException(CODE_ERROR_VALIDATION, $message->getFirstErrors());
            }
        }

        /**
         * @param WarrantyOrder $warranty
         * @param WarrantyMessage $message
         * @param array $attributes
         *
         * @return WarrantyMessage
         */
        public function updateMessage(WarrantyOrder $warranty, WarrantyMessage $message, array $attributes = []): WarrantyMessage
        {
            if (!$attributes) {
                $attributes = $message->toArray();
            }

            $this->debug("Updating message for {$warranty->getId()}, message id: " . $message->getId());
            $this->setMessage($warranty->_id, $message->getId(), $attributes);

            return $message;
        }

        /**
         * @param WarrantyOrder $warranty
         * @param WarrantyMessage $message
         * @return WarrantyMessage
         */
        public function pushMessage(WarrantyOrder $warranty, WarrantyMessage $message)
        {
            $pushData = $message->toArray();

            $this->debug("Push new message: " . json_encode($pushData));

            if (!$warranty->getMessages()) {
                $this->warrantyRepository->set($warranty->_id, 'messages', [$pushData]);
            } else {
                $this->warrantyRepository->push($warranty->_id, 'messages', $pushData);
            }

            return $message;
        }

        /**
         * @param string $warrantyId
         * @param string $messageId
         * @param array $attributes
         */
        public function setMessage(string $warrantyId, string $messageId, array $attributes)
        {
            $this->warrantyRepository->set($warrantyId, 'messages.$[el]', $attributes, ['el.message_id' => intval($messageId)]);
        }

        /**
         * @param string $warrantyId
         * @param string $messageId
         *
         * @throws NotFoundException
         */
        public function delete(string $warrantyId, string $messageId)
        {
            $warranty = $this->warrantyService->getWarrantyOrderOrException($warrantyId);
            $message = $this->getMessageOrException($warrantyId, $messageId);

            $this->debug("Running messageUpdate for {$warranty->getId()}/{$message->getId()}");
            $message->setAttributes(['is_deleted' => WarrantyMessage::IS_DELETED]);
            $this->setMessage($warranty->_id, $message->getId(), $message->toArray());
        }
    }
}
