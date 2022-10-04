<?php
declare(strict_types=1);

namespace Test\testApi\Warranty\Messages {

    use Common\Model\Storage\File;
    use Common\Model\User\User;
    use Common\Warranty\DTO\Message\WarrantyMessageDTO;
    use Common\Warranty\Messages\Facade;
    use Common\Warranty\Model\WarrantyMessage;
    use Common\Warranty\Model\WarrantyOrder;
    use Test\testApi\Common\SimpleApi;
    use Test\testApi\Warranty\WarrantyApi;

    class MessageApi extends SimpleApi
    {
        private $warrantyApi;
        private $fMessage;
        private $facade;

        protected function getFaker()
        {
            return $this->fMessage;
        }

        /**
         * MessageApi constructor.
         *
         * @param Facade $facade
         * @param WarrantyApi $wApi
         * @param FakeMessage $fmessage
         *
         * @throws \Exception
         */
        public function __const2ruct(Facade $facade, WarrantyApi $wApi, FakeMessage $fmessage)
        {
            $this->listSize = random_int(5, 10);
            $this->facade      = $facade;
            $this->warrantyApi = $wApi;
            $this->fMessage = $fmessage;
        }

        public function __construct($name = null, array $data = [], $dataName = '',
                                    Facade $facade, WarrantyApi $wApi, FakeMessage $fmessage
    )
        {
            parent::__construct($name, $data, $dataName);
        }


        public function fakeWarranty()
        {
            return $this->givenWarranty();
        }

        public function givenListMessage()
        {
            $wo = $this->givenWarranty();

            for ($i = 0; $i < $this->getListSize(); $i++) {
                $this->fakeMessageWithAttachments($wo->user(), $wo);
            }

            $wo->refresh();
            return $wo;
        }

        public function givenWarranty()
        {
            return $this->warrantyApi->givenWarranty();
        }

        public function givenClientUser(WarrantyOrder $wo)
        {
            $fakeUser = $this->fakeUser();
            $this->fakeCompanyStaff($wo->company(), $fakeUser);

            return $fakeUser;
        }

        public function givenBankUser(WarrantyOrder $wo)
        {
            $fakeUser = $this->fakeUser();
            $this->fakeCompanyStaff($wo->bank(), $fakeUser);

            return $fakeUser;
        }

        public function givenMessage(?User $user = null)
        {
            $wo = $this->givenWarranty();
            if (!$user) {
                $user = $wo->user();
            }
            return $this->fakeMessage($user, $wo);
        }

        /**
         * @param WarrantyMessage $fakeMessage
         *
         * @return WarrantyMessage
         * @throws \Common\Exception\NotFoundException
         */
        public function getMessage(WarrantyMessage $fakeMessage)
        {
            //TODO переделать идентификаторы сообщений
            return $this->facade->getMessage($fakeMessage->getWoId(), (int)$fakeMessage->getId());
        }

        public function listMessages(WarrantyOrder $wo)
        {
            return $this->facade->listMessages($wo->user(), $wo->getId());
        }

        public function sendMessage(WarrantyOrder $wo, array $data)
        {
            $reqDTO = $this->serializer()->denormalize($data, WarrantyMessageDTO::class);

            /** @var WarrantyMessageDTO $resDTO */
            $resDTO = $this->facade->send($wo->user(), $wo->getId(), $reqDTO);
            return $resDTO;
        }

        public function isAllowed(WarrantyOrder $wo, $user = null)
        {
            if(!$user) {
                $user = $wo->user();
            }
            return $this->facade->allowed($user, $wo);
        }

        public function delete(WarrantyMessage $fakeMessage)
        {
            $this->facade->delete($fakeMessage->user(), $fakeMessage->getWoId(), (int)$fakeMessage->getId());
            $this->debug("Message deleted. Trying to read it again");

            //Reload from db
            return $this->getMessage($fakeMessage);
        }

        public function givenMessageWithFiles(): array
        {
            $wo = $this->givenWarranty();

            $fileFst = $this->fakeFile($wo->getId() . ':1', File::TYPE_WARRANTY_MESSAGE_ATTACHMENT);
            $fileSnd = $this->fakeFile($wo->getId() . ':2', File::TYPE_WARRANTY_MESSAGE_ATTACHMENT);
            $fileThd = $this->fakeFile($wo->getId() . ':3', File::TYPE_WARRANTY_MESSAGE_ATTACHMENT);
            $attachments = [$fileFst->getId(), $fileSnd->getId(), $fileThd->getId()];

            $this->fakeMessageWithAttachments($wo->user, $wo, $attachments);

            return $attachments;
        }

        public function assertListFields(array $list)
        {
            $this->assertArrayHasKey('warrantyId', $list);
            $this->assertArrayHasKey('messages', $list);
            $this->assertNotEmpty($list['warrantyId']);
            $this->assertNotEmpty($list['messages']);
        }

        public function assertMessageFields(array $list)
        {
            $expectedKeys = [
                'id',
                'content',
                'signedHash',
                'isDeleted',
                'createTime',
                'self',
                'isBank',
                'author',
                'updateTime',
            ];

            //Первый элемент массива
            $message = array_shift($list['messages']);
            array_walk($expectedKeys, function($item) use($message) {
                $this->assertArrayHasKey($item, $message);
                $this->assertNotNull($message[$item]);
            });
        }

        public function assertContentFields(array $list)
        {
            $expectedKeys = [
                'message',
                'attachments',
            ];
            $message = array_shift($list['messages']);
            $content = $message['content'];

            array_walk($expectedKeys, function($item) use($content) {
                $this->assertArrayHasKey($item, $content);
                $this->assertNotNull($content[$item]);
            });

            //Проверяем это поле перенесли в корень сообщения
            $this->assertArrayNotHasKey('update_time', $content);
        }
    }
}