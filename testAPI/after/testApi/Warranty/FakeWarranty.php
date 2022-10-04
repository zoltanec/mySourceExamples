<?php
declare(strict_types=1);

namespace Test\testApi\Warranty {

    use Common\Core\Traits\RandomTrait;
    use Common\Model\Address\Address;
    use Common\Model\Company\Client;
    use Common\Model\Company\Company;
    use Common\Model\Storage\File;
    use Common\Model\User\Foreign;
    use Common\Model\User\Passport;
    use Common\Model\User\User;
    use Common\Util\Helper\CommonHelper;
    use Common\Warranty\Events\WarrantyEventSettings;
    use Common\Warranty\Model\WarrantyOrder;
    use Test\testApi\Common\ComplexFakerDecorator;

    class FakeWarranty extends ComplexFakerDecorator
    {
        use RandomTrait;

        private $fCompany;

        /**
         * @param array $attributes
         * @param string $tenderId
         *
         * @return array
         * @throws \Exception
         */
        public function fakeWarrantyData(array $attributes, $tenderId = '0187300010316002901'): array
        {
            $fakePath = \Yii::getAlias('@tests/stub') . '/warranty/response_success_' . $tenderId . '.json';

            if (file_exists($fakePath)) {
                $tenderInfo = json_decode(file_get_contents($fakePath), true);
            } else {
                throw new \RuntimeException('Fake tender file not found!');
            }

            if (isset($attributes['tender_info'])) {
                //Записываем только определенные поля в тендер
                foreach ($attributes['tender_info'] as $key => $value) {
                    if ($key == 'lots') {
                        foreach ($value as $index => $lot) {
                            $fields = [
                                'demands',
                                'selected',
                                'lotId',
                                'name',
                                'executionExpirationDate',
                                'isCollectiveLot',
                                'finalPrice',
                                'selected',
                                'requirements'
                            ];

                            foreach ($fields as $field) {
                                if (isset($lot[$field])) {
                                    $tenderInfo['lots'][$index][$field] = $lot[$field];
                                }
                            }
                        }
                    } else {
                        $tenderInfo[$key] = $value;
                    }
                }
            }
            //lifehack
            $tenderInfo['provision'] = 'execution';
            //$bank                    = $this->fakeCompanyBank();

            return [
                    'tender_info' => $tenderInfo,
                    'warranty_id' => $this->getTextId(),
                    'bank_id'     => 2, //$bank->company()->getId(),
                ] + $attributes;
        }

        /**
         * @param User|null $user
         * @param Company|null $company
         * @param bool $status
         * @param string $tenderId
         *
         * @return WarrantyOrder
         * @throws \yii\base\Exception
         */
        public function fakeWarranty(
            User $user = null,
            Company $company = null,
            string $tenderId = '0187300010316002901'
        ): WarrantyOrder
        {
            $user = $user ?? $this->fakeUser();

            $attributes = [
                'user_id' => $user->id,
            ];

            if ($company) {
                $attributes = $attributes + [
                        'company_id' => $company->id,
                    ];
            }

            $warrantyOrder = new WarrantyOrder();
            $attributes    = $this->fakeWarrantyData($attributes, $tenderId);

            $warrantyOrder = $warrantyOrder->setAttributes($attributes);
            $this->assertTrue($warrantyOrder->save(),
                'Фейковая заявка гарантии создана ' . $warrantyOrder->getJsonErrors());

            return $warrantyOrder;
        }

        /**
         * @param User|null $user
         * @param Company|null $company
         *
         * @return WarrantyOrder
         * @throws \Throwable
         * @throws \yii\base\Exception
         */
        public function fakeWarrantyWithDocuments(User $user = null, Company $company = null)
        {
            $warrantyOrder = $this->fakeWarranty($user, $company);

            $this->fakeFile($warrantyOrder->getEntityId(), File::TYPE_WARRANTY_DEAL_ACCEPTANCE);

            return $warrantyOrder;
        }

        /**
         * @param $status
         * @param User|null $user
         * @param Company|null $company
         * @param null $filledForm
         *
         * @return WarrantyOrder
         * @throws \Throwable
         */
        public function fakeWarrantyWithStatus($status, User $user = null, Company $company = null, $filledForm = null)
        {
            $faker = $this->getFaker();

            //Using tender response with demands section
            if (!$filledForm && in_array($status, WarrantyOrder::getBankStatusPermissions() + [WarrantyOrder::STATUS_PROCESSING])) {
                //get tender info with lots, demands and requirements
                $filledForm = base64_decode(file_get_contents(\Yii::getAlias('@tests/stub') . '/eds/aak-sbids-content'));
            }

            $attributes = [
                'status'      => $status,
                'user_id'     => $user->getId(),
                'company_id'  => $company->getId(),
                'filled_form' => $filledForm,
                'tender_info' => [
                    'beneficiary' => [
                        'inn' => $this->getFakerInn(),
                    ],
                    'provision'   => 'bid',
                    'lots'        => [
                        [
                            'lotId'                   => 0,
                            'name'                    => 'Сложный лот',
                            'isCollectiveLot'         => false,
                            'executionExpirationDate' => $faker->unixTime($max = 'now'),
                            'selected'                => 'true',
                            'finalPrice'              => random_int(1, 100) / 100,
                            'demands'                 => [
                                'bid'       => [
                                    [
                                        'amount' => random_int(1, 100) / 100,
                                        'days'   => random_int(1, 10),
                                        'end'    => $faker->unixTime,
                                        'start'  => $faker->unixTime,
                                    ]
                                ],
                                'execution' => [
                                    [
                                        'amount' => random_int(1, 100) / 100,
                                        'days'   => random_int(1, 10),
                                        'end'    => $faker->unixTime,
                                        'start'  => $faker->unixTime,
                                    ]
                                ],
                            ],
                            'requirements'            => [
                                [
                                    'startingPrice'           => random_int(1, 100) / 100,
                                    'bidProvisionPrice'       => random_int(1, 100) / 100,
                                    'executionProvisionPrice' => random_int(1, 100) / 100,
                                ]
                            ]
                        ],
                        [
                            'lotId'                   => 1,
                            'name'                    => 'Еще более сложный лот',
                            'isCollectiveLot'         => false,
                            'executionExpirationDate' => $faker->unixTime($max = 'now'),
                            'selected'                => true,
                            'finalPrice'              => random_int(1, 100) / 100,
                            'demands'                 => [
                                'bid'       => [
                                    [
                                        'amount' => random_int(1, 100) / 100,
                                        'days'   => random_int(1, 10),
                                        'end'    => $faker->unixTime,
                                        'start'  => $faker->unixTime,
                                    ]
                                ],
                                'execution' => [
                                    [
                                        'amount' => random_int(1, 100) / 100,
                                        'days'   => random_int(1, 10),
                                        'end'    => $faker->unixTime,
                                        'start'  => $faker->unixTime,
                                    ]
                                ],
                            ],
                            'requirements'            => [
                                [
                                    'startingPrice'           => random_int(1, 100) / 100,
                                    'bidProvisionPrice'       => random_int(1, 100) / 100,
                                    'executionProvisionPrice' => random_int(1, 100) / 100,
                                ]
                            ]
                        ],
                    ],
                ],
            ];

            $attributes = $this->fakeWarrantyData($attributes);
            $wo         = (new WarrantyOrder())->setAttributes($attributes);
            $this->assertTrue($wo->save(),
                'Фейковая заявка гарантии не создана ' . $wo->getJsonErrors());

            $this->setStatusLog($wo, $status);

            return $wo;
        }

        /**
         * @param WarrantyEventSettings $event
         * @param User|null $user
         * @param Company|null $company
         *
         * @return WarrantyOrder
         * @throws \Common\Exception\ValidationException
         * @throws \Throwable
         * @throws \yii\base\Exception
         */
        public function fakeWarrantyWithEvent(WarrantyEventSettings $event, User $user = null, Company $company = null)
        {
            $warrantyOrder = $this->fakeWarranty($user, $company);
            $warrantyOrder->addEvent($event);

            $this->assertTrue($warrantyOrder->save(), 'Фейковое событие гарантии не сохранено!'
                . $warrantyOrder->getJsonErrors());

            return $warrantyOrder;
        }

        /**
         * @param WarrantyOrder $warrantyOrder
         * @param $currentStatus
         *
         * @return bool
         * @throws \Throwable
         */
        public function setStatusLog(WarrantyOrder $warrantyOrder, $currentStatus)
        {
            if (!$currentStatus) {
                return false;
            }

            foreach (WarrantyOrder::getStatuses() as $status) {
                $warrantyOrder->setStatus($status);
                if ($status === $currentStatus) {
                    break;
                }
            }

            return true;
        }

        /**
         * $filledForm - default value equals fakeEds content
         *
         * @param User $user
         * @param Company $company
         * @param bool $beneficiaryCreate
         *
         * @return WarrantyOrder
         * @throws \Throwable
         * @throws \yii\base\Exception
         */
        public function fakeWarrantyUpdated(User $user, Company $company, $beneficiaryCreate = false): WarrantyOrder
        {
            $wo = $this->fakeWarrantyWithStatus(WarrantyOrder::STATUS_SENT, $user, $company);

            //Создаем связь с бенефициаром
            $beneficiaryInn = $wo->getBeneficiaryInn();
            $beneficiary    = Company::findOne(['inn' => $beneficiaryInn]);
            if ($beneficiaryCreate && !$beneficiary) {
                $this->fakeCompany($beneficiaryInn);
            }

            return $wo;
        }

        /**
         * @param User $user
         * @param Company $company
         *
         * @return WarrantyOrder
         * @throws \Throwable
         */
        public function fakeWarrantyNotFilled(User $user, Company $company)
        {
            return $this->fakeWarrantyWithStatus(WarrantyOrder::STATUS_SENT, $user, $company, $this->getFaker()->sentence);
        }

        /**
         * @param array $attributes
         *
         * @return User
         * @throws \yii\base\Exception
         */
        public function fakeWarrantyUser(array $attributes = []): User
        {
            $user = new User();

            $attributes = $this->fakeUserData($attributes);
            $attributes = CommonHelper::toUnderscore($attributes);

            if (!$user->setAttributes($attributes)->hashPasswordField()->save()) {
                throw new \RuntimeException('Пользователь не создан ' . $user->getJsonErrors());
            }

            if (isset($attributes['passport'])) {
                $passportAttributes = $attributes['passport'];

                $passportAttributes['user_id'] = $user->getId();
                $passportAttributes['type']    = Passport::TYPE_PASSPORT;

                $passport = (new Passport())->setAttributes($passportAttributes);
                if (!$passport->save()) {
                    throw new \RuntimeException('Паспорт пользователя не создан ' . $passport->getJsonErrors());
                }

                if (isset($passportAttributes['address'])) {
                    $addressAttributes = $passportAttributes['address'];

                    $address = (new Address())->setAttributes($addressAttributes);

                    if (!$address->save()) {
                        throw new \RuntimeException('Адрес не создан ' . $address->getJsonErrors());
                    }
                    $passport->link('address', $address);
                }

                if (isset($passportAttributes['file']) && $passportAttributes['file']) {
                    $this->fakeFile((string)$passport->getId(), File::TYPE_PASSPORT);
                }
            }

            if (isset($attributes['foreign_passport'])) {
                $foreignPassportAttr            = $attributes['foreign_passport'];
                $foreignPassportAttr['user_id'] = $user->getId();
                $foreignPassportAttr['type']    = Passport::TYPE_PASSPORT_INTERNATIONAL;

                $foreignPassport = (new Passport())->setAttributes($foreignPassportAttr);
                if (!$foreignPassport->save()) {
                    throw new \RuntimeException('Паспорт иностранца не создан ' . $foreignPassport->getJsonErrors());
                }

                if (isset($foreignPassportAttr['file']) && $foreignPassportAttr['file']) {
                    $this->fakeFile((string)$foreignPassport->getId(), File::TYPE_FOREIGN_PASSPORT);
                }
            }

            if (isset($attributes['foreign_residence'])) {
                $foreignResidenceAttr            = $attributes['foreign_residence'];
                $foreignResidenceAttr['user_id'] = $user->getId();
                $foreignResidenceAttr['type']    = Foreign::TYPE_MIGRATION_RESIDENCE;

                $foreignResidence = (new Foreign())->setAttributes($foreignResidenceAttr);
                if (!$foreignResidence->save()) {
                    throw new \RuntimeException('Миграционный документ не создан ' . $foreignResidence->getJsonErrors());
                }

                if (isset($foreignResidenceAttr['file']) && $foreignResidenceAttr['file']) {
                    $this->fakeFile((string)$foreignResidence->getId(), File::TYPE_MIGRATION_RESIDENCE);
                }
            }

            if (isset($attributes['foreign_migration'])) {
                $foreignMigrationAttr            = $attributes['foreign_migration'];
                $foreignMigrationAttr['user_id'] = $user->getId();
                $foreignMigrationAttr['type']    = Foreign::TYPE_MIGRATION_CARD;

                $foreignMigration = (new Foreign())->setAttributes($foreignMigrationAttr);
                if (!$foreignMigration->save()) {
                    throw new \RuntimeException('Миграционная карта не создана ' . $foreignMigration->getJsonErrors());
                }

                if (isset($foreignMigrationAttr['file']) && $foreignMigrationAttr['file']) {
                    $this->fakeFile((string)$foreignMigration->getId(), File::TYPE_MIGRATION_CARD);
                }
            }

            if (isset($attributes['address'])) {
                $address = (new Address())->setAttributes($attributes['address']);

                if (!$address->save()) {
                    throw new \RuntimeException('Адрес не создан ' . $address->getJsonErrors());
                }
                $user->link('address', $address);
            }

            return $user;
        }

        /**
         * @return User
         * @throws \yii\base\Exception
         */
        public function fakeWarrantyFilledUser(): User
        {
            $faker = $this->getFaker();

            $attributes = [
                'passport' => [
                    'address'         => [
                        'city'     => $faker->city,
                        'country'  => $faker->country,
                        'district' => $faker->sentence,
                        'house'    => $faker->buildingNumber,
                        'locality' => $faker->sentence,
                        'postcode' => $faker->buildingNumber,
                        'region'   => $faker->sentence,
                        'room'     => $faker->buildingNumber,
                        'street'   => $faker->sentence,
                    ],
                    'number'          => (string)rand(100000, 999999),
                    'issuedDate'      => (new \DateTime('today'))->getTimestamp(),
                    'issuedBy'        => $faker->sentence,
                    'subdivisionCode' => $faker->sentence,
                ],
            ];

            $user = $this->fakeWarrantyUser($attributes);
            $this->fakeFile((string)$user->passport()->getId(), File::TYPE_PASSPORT);

            return $user;
        }

        /**
         * @param array $attributes
         * @param bool $failTest
         *
         * @return Company
         * @throws \yii\base\Exception
         */
        public function fakeWarrantyCompany(array $attributes = [], $failTest = false): Company
        {
            $attributes = CommonHelper::toUnderscore($attributes);
            //т.к в фейковую заявку записываем filled_form умных предложений, формируем эту компанию
            $attributes['name']  = 'Общество с ограниченной ответственностью «Умные предложения»"';
            $attributes['alias'] = 'Общество с ограниченной ответственностью «Умные предложения»"';
            $attributes['ogrn']  = '5177746361469';
            $company             = (new Company())->setAttributes($attributes + $this->fakeCompanyData('7714420892'));

            if (!$company->save()) {
                throw new \RuntimeException('Компания не создана '
                    . $company->getJsonErrors());
            }

            if (isset($attributes['client'])) {
                $companyClient = (new Client())->setAttributes($attributes['client']);
                $companyClient->link('company', $company);

                if (!$companyClient->save()) {
                    throw new \RuntimeException('Клиент (компания) не создан '
                        . $companyClient->getJsonErrors());
                }
            }

            $this->fakeCompanyDebt($company);

            if (!$failTest) {
                $this->fakeCompanyBankAccount($company);
                $fakeRegistrar = $this->fakeRegistrar($company);

                $legal = $this->fakeAddress(Address::TYPE_LEGAL);
                $company->link('legalAddress', $legal);

                $real = $this->fakeAddress();
                $company->link('physicalAddress', $real);

                $this->fakeFile((string)$fakeRegistrar->getId(), File::TYPE_COMPANY_REGISTRAR_OGRN);
            }

            return $company;
        }
    }
}