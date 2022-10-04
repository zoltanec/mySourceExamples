<?php

namespace Test\Bidsmart\Common\Facade\Warranty {

    use Common\Exception\NotFoundException;
    use Common\Model\Storage\File;
    use Common\Warranty\DTO\Message\WarrantyMessageDTO;
    use Test\TestCase;

    /**
     * Class WarrantyMessageFacadeTest
     * @package Test\Bidsmart\Common\Facade\Warranty
     */
    class WarrantyMessageFacadeTest extends TestCase
    {
        /**
         * @group warranty
         * @group messages
         *
         * @throws \ReflectionException
         * @throws \yii\base\Exception
         * @throws \yii\base\InvalidConfigException
         * @throws \yii\di\NotInstantiableException
         * @throws \yii\mongodb\Exception
         */
        public function testListMessages()
        {
            $client   = $this->fakeUser();
            $company  = $this->fakeCompany();
            $fakeWarranty = $this->fakeWarranty($client, $company);

            $fakeBank = $this->fakeCompany();
            $bankUser = $this->fakeUser();

            $this->fakeBankStaff($fakeBank, $bankUser);

            $facade = $this->getMessageFacade();

            $listSize = random_int(5, 10);
            for ($i = 0; $i < $listSize; $i++) {
                $user = random_int(0, 1) ? $client : $bankUser;
                $this->fakeMessage($user, $fakeWarranty, true);
            }

            $messages = $facade->listMessages($client, $fakeWarranty->getId());
            $this->debug("Messages reply: " . json_encode($messages));
            $this->assertEquals($listSize, count($messages));

            foreach ($messages as $message) {
                $this->assertArrayHasKey('message_id', $message);
                $this->assertArrayHasKey('content', $message);
                $this->assertArrayHasKey('signed_hash', $message);
                $this->assertArrayHasKey('create_time', $message);

                $this->assertNotNull($message['create_time']);
                $this->assertNotNull($message['content']);
                $this->assertNotNull($message['signed_hash']);

                $this->assertSame(0, $message['is_deleted']);

                //check content
                $this->assertArrayHasKey('update_time', $message['content']);
                $this->assertArrayHasKey('message', $message['content']);
                $this->assertArrayHasKey('attachments', $message['content']);

                $this->assertNotSame([], $message['content']['attachments']);
            }
        }

        /**
         * @group messages
         *
         * @throws \yii\base\Exception
         * @throws \yii\base\InvalidConfigException
         * @throws \yii\di\NotInstantiableException
         */
        public function testEmptyListMessages()
        {
            $client   = $this->fakeUser();
            $company  = $this->fakeCompany();
            $fakeWarranty = $this->fakeWarranty($client, $company);

            $facade = $this->getMessageFacade();

            $messages = $facade->listMessages($client, $fakeWarranty->getId());
            $this->assertEquals(0, count($messages));
        }

        /**
         * @group messages
         *
         * @expectedException NotFoundException
         * @throws \yii\base\Exception
         * @throws \yii\base\InvalidConfigException
         * @throws \yii\di\NotInstantiableException
         */
        public function testExceptionListMessages()
        {
            $this->expectException(NotFoundException::class);

            $fakeUser = $this->fakeUser();
            $fakeBank = $this->fakeCompany();
            $bankUser = $this->fakeUser();

            $this->fakeBankStaff($fakeBank, $bankUser);

            $facade = $this->getMessageFacade();

            $facade->listMessages($fakeUser, $this->getFaker()->sentence);
        }

        /**
         * @group warranty
         * @group messages
         *
         * @throws \yii\base\Exception
         * @throws \yii\base\InvalidConfigException
         * @throws \yii\di\NotInstantiableException
         */
        public function testGetMessage()
        {
            $fakeUser     = $this->fakeUser();
            $fakeWarranty = $this->fakeWarranty();
            $fakeMessage  = $this->fakeMessage($fakeUser, $fakeWarranty);

            $facade = $this->getMessageFacade();
            $message = $facade->getMessage($fakeWarranty->getId(), $fakeMessage->getId());

            $this->assertNotNull($message);
            $this->assertSame($fakeMessage->getId(), $message->getId());
        }

        /**
         * @group messages
         *
         * @return array
         *
         */
        public function providerSendMessage()
        {
            $faker = $this->getFaker();

            return [
                [
                    [
                        'content'    => [
                            'message'     => $faker->sentence,
                            'attachments' => [
                                [
                                    "id"   => $faker->sentence,
                                    "hash" => $faker->sentence,
                                ],
                                [
                                    "id"   => $faker->sentence,
                                    "hash" => $faker->sentence,
                                ],
                            ],
                        ],
                        'signedHash' => $faker->sentence,
                    ],
                    [
                        'content'    => [
                            'message'     => $faker->sentence,
                            'attachments' => [
                                [
                                    "id"   => $faker->sentence,
                                    "hash" => $faker->sentence,
                                ],
                            ],                        ],
                        'signedHash' => $faker->sentence,
                    ],
                ],
            ];
        }

        /**
         * @group warranty
         * @group messages
         * @group messageSend
         *
         * @dataProvider providerSendMessage
         *
         * @param $data
         * @throws \ReflectionException
         * @throws \yii\base\Exception
         * @throws \yii\base\InvalidConfigException
         * @throws \yii\di\NotInstantiableException
         */
        public function testSendMessage($data)
        {
            $client   = $this->fakeUser();
            $company  = $this->fakeCompany();
            $fakeWarranty = $this->fakeWarranty($client, $company);

            $facade = $this->getMessageFacade();
            $reqDTO = $this->getRequestSerializer()->denormalize($data, WarrantyMessageDTO::class);

            $resDTO = $facade->send($client, $fakeWarranty->getId(), $reqDTO);
            $result = $this->getResponseSerializer()->normalize($resDTO);

            $message = $facade->getMessage($fakeWarranty->getId(), 0);

            foreach($data['content']['attachments'] as &$attachment) {
                $this->fakeFile($message->getEntityId(), File::TYPE_WARRANTY_MESSAGE_ATTACHMENT);
            }

            $this->debug("Msg: " . json_encode($message));

            $this->assertNotNull($result);
            foreach ($data as $field => $value) {
                if (is_array($value)) {
                    foreach ($value as $field2 => $value2) {
                        if ($field2 == 'attachments') {
                            $this->assertNotNull($message->files());

                            $this->assertSame(count($value2), count($message->files()));

                            foreach ($value2 as $file) {
                                $this->assertArrayHasKey('id', $file);
                                $this->assertArrayHasKey('hash', $file);
                            }
                        }
                        $this->assertSame($value2, $result[$field][$field2], 'field ' . $field2);
                    }
                } else {
                    $this->assertSame($value, $result[$field], 'field ' . $field);
                }
            }
        }

        /**
         * @group warranty
         * @group messages
         *
         * @dataProvider providerSendMessage
         * @param $data
         *
         * @throws \ReflectionException
         * @throws \yii\base\Exception
         * @throws \yii\base\InvalidConfigException
         * @throws \yii\di\NotInstantiableException
         */
        public function testUpdateMessage($data)
        {
            $fakeUser     = $this->fakeUser();
            $fakeWarranty = $this->fakeWarranty($fakeUser);
            $fakeMessage  = $this->fakeMessage($fakeUser, $fakeWarranty);

            $facade = $this->getMessageFacade();
            $resDTO = $this->getRequestSerializer()->denormalize($data, WarrantyMessageDTO::class);
            $facade->update($fakeUser, $fakeWarranty->getId(), $fakeMessage->getId(), $resDTO);

            $result = $this->getResponseSerializer()->normalize($resDTO);

            $this->assertNotNull($result);
            $this->assertTrue(is_array($result));
            $this->assertArrayHasKey('content', $result);
            $this->assertArrayHasKey('signedHash', $result);

            foreach ($result as $field => $value) {
                if (is_array($value)) {
                    $this->assertArrayHasKey('updateTime', $value);
                    $this->assertArrayHasKey('message', $value);
                    $this->assertArrayHasKey('attachments', $value);
                }
            }

            foreach ($data as $field => $value) {
                if (is_array($value)) {
                    foreach ($value as $field2 => $value2) {
                        if ($field2 == 'attachments') {
                            continue;
                        }
                        $this->assertSame($value2, $result[$field][$field2], 'field ' . $field2);
                    }
                } else {
                    $this->assertSame($value, $result[$field], 'field ' . $field);
                }
            }
        }

        /**
         * @group warranty
         * @group messages
         * @group messageDelete
         *
         * @throws \yii\base\Exception
         * @throws \yii\base\InvalidConfigException
         * @throws \yii\di\NotInstantiableException
         */
        public function testDelete()
        {
            $fakeUser     = $this->fakeUser();
            $fakeWarranty = $this->fakeWarranty();
            $fakeMessage  = $this->fakeMessage($fakeUser, $fakeWarranty);
            $facade = $this->getMessageFacade();

            //Удаляем
            $facade->delete($fakeUser, $fakeWarranty->getId(), $fakeMessage->getId());
            $this->debug("Message deleted. Trying to read it again");

            $deleted = $facade->getMessage($fakeWarranty->getId(), $fakeMessage->getId());
            $this->assertSame(1, $deleted->is_deleted);
        }
    }
}
