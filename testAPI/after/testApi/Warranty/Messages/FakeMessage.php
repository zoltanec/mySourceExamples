<?php

namespace Test\testApi\Warranty\Messages {

    use Common\Model\Storage\File;
    use Common\Model\User\User;
    use Common\Warranty\Model\WarrantyMessage;
    use Common\Warranty\Model\WarrantyOrder;
    use Test\testApi\Common\ComplexFakerDecorator;
    use yii\db\Exception;

    class FakeMessage extends ComplexFakerDecorator
    {
        /**
         * @param User $user
         * @param WarrantyOrder $warranty
         *
         * @return array
         */
        public function fakeMessageData(User $user, WarrantyOrder $warranty)
        {
            return [
                //side message: bank | client
                'source'      => $user->bankStaff() ? WarrantyMessage::SOURCE_BANK : WarrantyMessage::SOURCE_CLIENT,
                'user_id'     => $user->getId(),
                'warranty_id' => $warranty->getId(),
                'message_id'  => $warranty->messages ? count($warranty->messages) : 0,
                'is_deleted'  => 0,
                'content'     => [
                    'message'     => $this->getFaker()->sentence,
                    'attachments' => [],
                ],
                'signed_hash' => $this->getFaker()->sentence,
            ];
        }

        /**
         * @param User $user
         * @param WarrantyOrder $warranty
         * @param bool $fakeFiles
         *
         * @return WarrantyMessage
         * @throws \yii\base\Exception
         * @throws \yii\mongodb\Exception
         */
        public function fakeMessage(User $user, WarrantyOrder $warranty): WarrantyMessage
        {
            $attributes = $this->fakeMessageData($user, $warranty);
            $message    = $this->getValidatedMessage($attributes);

            try {
                $this->saveMessage($warranty, $message);
            } catch (Exception $e) {
                throw new \RuntimeException('Сообщение привязанное к гарантии не создано '
                    . $e->getMessage());
            }

            return $message;
        }

        /**
         * @param $attributes
         *
         * @return WarrantyMessage
         */
        private function getValidatedMessage($attributes): WarrantyMessage
        {
            $message = new WarrantyMessage();
            $message->setAttributes($attributes)->setCreatedTime();

            if (!$message->validate()) {
                throw new \RuntimeException('Сообщение привязанное к гарантии не создано '
                    . $message->getJsonErrors());
            }

            return $message;
        }

        /**
         * @param WarrantyOrder $wo
         * @param WarrantyMessage $message
         *
         * @throws \yii\mongodb\Exception
         */
        private function saveMessage(WarrantyOrder $wo, WarrantyMessage $message)
        {
            $wo->getCollection()->update(
                ['_id' => $wo->_id],
                ['$push' => ['messages' => $message->toArray()]]
            );
        }

        private function addAttachments(WarrantyOrder $wo, WarrantyMessage $message, $attachments)
        {
            $wo->getCollection()->update(
                ['_id' => $wo->_id],
                ['$set' => ['messages.$[el].content.attachments' => $attachments]],
                ['arrayFilters' => [['el.message_id' => $message->getId()]]]
            );
        }

        /**
         * @param User $user
         * @param WarrantyOrder $warranty
         * @param array $ids
         *
         * @return WarrantyMessage
         * @throws \yii\base\Exception
         * @throws \yii\mongodb\Exception
         */
        public function fakeMessageWithAttachments(User $user, WarrantyOrder $warranty, $ids = []): WarrantyMessage
        {
            $message = $this->fakeMessage($user, $warranty);

            if (empty($ids)) {
                $fakeFile  = $this->fakeFile($warranty->getId() . ':' . 0, File::TYPE_WARRANTY_MESSAGE_ATTACHMENT);
                $fakeFile2 = $this->fakeFile($warranty->getId() . ':' . 1, File::TYPE_WARRANTY_MESSAGE_ATTACHMENT);
                $ids       = [$fakeFile->getId(), $fakeFile2->getId()];
            }

            foreach ($ids as $id) {
                $attachments[] = [
                    "id"   => $id,
                    "hash" => $this->getFaker()->sentence,
                ];
            }

            //Аттрибуты для обновления фейкового объекта сообщения
            $attributes['content']['attachments'] = $attachments;
            try {
                $message->setAttributes($attributes);
                $this->addAttachments($warranty, $message, $attachments);
            } catch (Exception $e) {
                throw new \RuntimeException('Не удалось сохранить приложенные документы к сообщению'
                    . $e->getMessage());
            }

            return $message;
        }
    }
}