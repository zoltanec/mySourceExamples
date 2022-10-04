<?php

namespace Test\Traits {

    use Common\Agent\Model\AgentRequest;
    use Common\Agent\Model\AgentStaff;
    use Common\Core\Traits\RandomTrait;
    use Common\Model\Address\Address;
    use Common\Model\BookKeeping\Line;
    use Common\Model\BookKeeping\Report;
    use Common\Model\Company\Bank;
    use Common\Model\Company\BankAccount;
    use Common\Model\Company\BankStaff;
    use Common\Model\Company\Client;
    use Common\Model\Company\Company;
    use Common\Staff\Model\CompanyStaff;
    use Common\Model\Company\Debt;
    use Common\Model\Company\Registrar;
    use Common\Staff\Model\StaffRequest;
    use Common\Email\Model\EmailTask;
    use Common\Model\Egrul\RequestEgrul;
    use Common\Model\Papers\PaperItem;
    use Common\Model\Papers\PaperTemplate;
    use Common\Model\Storage\FileType;
    use Common\Model\Storage\File;
    use Common\Model\User\Foreign;
    use Common\Model\User\Passport;
    use Common\Model\User\User;
    use Common\Model\User\UserEds;
    use Common\Model\User\UserHashes;
    use Common\Model\User\UserRequestInn;
    use Common\Service\User\Permissions\Models\Item;
    use Common\Service\User\Permissions\Models\PermissionGroup;
    use Common\Service\User\Permissions\Models\PermissionTemplate;
    use Common\Service\User\Permissions\PermissionType;
    use Common\Warranty\Events\WarrantyEventSettings;
    use Common\Warranty\Model\DocumentVerify;
    use Common\Warranty\Model\Validator\Company\CompanyDocumentsValidator;
    use Common\Warranty\Model\WarrantyMessage;
    use Common\Warranty\Model\WarrantyOrder;
    use Faker\Factory;
    use Faker\Generator;
    use Common\Service\BookKeeping\Repository\ReportRepository;
    use Common\Util\Helper\CommonHelper;
    use Common\Service\DataMining\Supervisor;
    use Common\Service\User\Interfaces\NationalityInterface;
    use Common\Service\User\Interfaces\ResidentInterface;
    use yii\db\Exception;

    /**
     * Trait ObjectsTrait
     * @package Test\Traits
     */
    trait ObjectsTrait
    {
        use RandomTrait;
        /**
         * @param array $attributes
         *
         * @return array
         */
        protected function fakeUserData(array $attributes = [])
        {
            /** @var Generator $faker */
            $faker = $this->getFaker();

            return array_merge([
                'email'       => $faker->safeEmail,
                'password'    => $faker->password(),
                'status'      => User::STATUS_ACTIVE,
                'firstName'   => ucfirst($faker->firstName),
                'surname'     => ucfirst($faker->lastName),
                'patronymic'  => ucfirst($faker->streetName),
                'role'        => User::ROLE_CLIENT,
                'inn'         => $this->getFakerInn(),
                'phone'       => preg_replace('/[^0-9]/', '', $faker->phoneNumber),
                'birthDate'   => $faker->unixTime($max = 'now'),
                'birthPlace'  => $faker->streetAddress,
                'nationality' => NationalityInterface::NATIONALITY_RUSSIAN,
                'isResident'  => ResidentInterface::RESIDENT,
                'isPassportLocation' => 1,
            ], $attributes);
        }

        /**
         * @param User|null    $user
         * @param Company|null $company
         * @param array        $attributes
         *
         * @return array
         */
        protected function fakeStaffData(?User $user = null, ?Company $company = null, array $attributes = [])
        {
            /** @var Generator $faker */
            $faker = $this->getFaker();

            if ($user instanceof User) {
                $relation['user_id'] = $user->getId();
            }

            if ($company instanceof Company) {
                $relation['company_id'] = $company->getId();
            }

            return array_merge([
                'isFounder'    => $faker->randomElement(['0', '1']),
                'isDirector'   => $faker->randomElement(['0', '1']),
                'isDeleted'    => 0,
                'position'     => $faker->sentence,
                'shareRubles'  => $faker->sentence,
                'sharePercent' => $faker->sentence,
            ], $attributes, $relation ?? []);
        }

        /**
         * @param User $user
         *
         * @return array
         */
        protected function fakePassportData(User $user): array
        {
            /** @var Generator $faker */
            $faker = $this->getFaker();

            return [
                'user_id'    => $user->getId(),
                'serial'     => rand(1000, 9999),
                'number'     => rand(100000, 999999),
                'code'       => 1,
                'region'     => $faker->sentence,
                'city'       => $faker->city,
                'address'    => $faker->address,
                'building'   => $faker->buildingNumber,
                'apart'      => $faker->sentence,
                'issuedby'   => $faker->sentence,
                'issuedDate' => strtotime('today'),
                'type'       => Passport::TYPE_PASSPORT,
            ];
        }

        /**
         * @param User $user
         *
         * @return array
         */
        protected function fakeForeignPassportData(User $user): array
        {
            /** @var Generator $faker */
            $faker = $this->getFaker();

            return [
                'user_id'   => $user->getId(),
                'serial'    => rand(1000, 9999),
                'number'    => rand(100000, 999999),
                'code'      => 1,
                'region'    => $faker->sentence,
                'city'      => $faker->city,
                'address'   => $faker->address,
                'building'  => $faker->buildingNumber,
                'apart'     => $faker->sentence,
                'issuedby'  => $faker->sentence,
                'issuedDate' => strtotime('today'),
                'type'      => Passport::TYPE_PASSPORT_INTERNATIONAL,
            ];
        }

        /**
         * @param null|string $inn
         *
         * @return array
         */
        protected function fakeCompanyData(?string $inn): array
        {
            /** @var Generator $faker */
            $faker = $this->getFaker();

            return [
                'name'                   => $faker->company,
                'alias'                  => $faker->company . ' alias',
                'inn'                    => $inn ?? $this->getFakerInn(),
                'ogrn'                   => $this->getFakerOgrn(),
                'kpp'                    => (string)$faker->sentence,
                'type_organization'      => 'commercial',
                'financial_active_debts' => $faker->randomElement([0, 1]),
                'financial_hidden_loss'  => $faker->randomElement([0, 1]),
                'financial_staff_debts'  => $faker->randomElement([0, 1]),
                'financial_tax_debts'    => $faker->randomElement([0, 1]),
                'financial_fund_debts'   => $faker->randomElement([0, 1]),
                'room_type'              => Company::TYPE_ROOM_RENT,
            ];
        }

        /**
         * @param array $attributes
         *
         * @return array
         */
        protected function fakeAddressData(array $attributes = []): array
        {
            $faker = $this->getFaker();

            return array_merge([
                'postcode' => $faker->postcode,
                'country'  => $faker->country,
                'region'   => $faker->country,
                'city'     => $faker->city,
                'district' => $faker->city,
                'locality' => $faker->city,
                'street'   => $faker->address,
                'house'    => $faker->buildingNumber,
                'type'     => Address::TYPE_PHYSICAL,
            ], $attributes);
        }

        /**
         * @param User $user
         *
         * @return array
         */
        protected function fakeMigrationCardData(User $user): array
        {
            $faker = $this->getFaker();

            return [
                'user_id' => $user->getId(),
                'type'    => Foreign::TYPE_MIGRATION_CARD,
                'number'  => $faker->sentence,
            ];
        }

        /**
         * @param User $user
         *
         * @return array
         */
        protected function fakeMigrationResidenceData(User $user): array
        {
            $faker = $this->getFaker();

            return [
                'user_id' => $user->getId(),
                'type'    => Foreign::TYPE_MIGRATION_RESIDENCE,
                'number'  => $faker->sentence,
            ];
        }

        /**
         * @param string|null $email
         *
         * @return User
         */
        protected function fakeUserDummy(string $email = null): User
        {
            if (is_null($email)) {
                list('email' => $email) = $this->fakeUserData();
            }

            return (new User())->setAttributes(['email' => $email]);
        }

        /**
         * @param string|null $email
         * @param string|null $password
         *
         * @return User
         * @throws \Exception
         * @throws \yii\base\Exception
         */
        protected function fakeUser(string $email = null, string $password = null): User
        {
            $user = new User();

            if ($email) {
                $attributes['email'] = $email;
            }
            if ($password) {
                $attributes['password'] = $password;
            }

            $attributes = $this->fakeUserData($attributes ?? []);
            $attributes = CommonHelper::toUnderscore($attributes);

            if (!$user->setAttributes($attributes)->hashPasswordField()->save()) {
                throw new \RuntimeException('Пользователь не создан ' . $user->getJsonErrors());
            }

            $passportData = CommonHelper::toUnderscore($this->fakePassportData($user));
            $passport = new Passport();
            if (!$passport->setAttributes($passportData)->save($passportData)) {
                throw new \RuntimeException('Паспорт пользователя не создан ' . $passport->getJsonErrors());
            }

            $foreignPassportData = CommonHelper::toUnderscore($this->fakeForeignPassportData($user));
            $foreignPassport = new Passport();
            if (!$foreignPassport->setAttributes($foreignPassportData)->save()) {
                throw new \RuntimeException('Паспорт пользователя не создан ' . $foreignPassport->getJsonErrors());
            }

            $foreignCard = new Foreign();
            if (!$foreignCard->setAttributes($this->fakeMigrationCardData($user))->save()) {
                throw new \RuntimeException('Миграционная карта пользователя не создан ' . $foreignCard->getJsonErrors());
            }

            $foreignResidence = new Foreign();
            if (!$foreignResidence->setAttributes($this->fakeMigrationResidenceData($user))->save()) {
                throw new \RuntimeException('Вид на жительство пользователя не создан ' . $foreignResidence->getJsonErrors());
            }

            $user->refresh();

            return $user;
        }

        /**
         * @throws \yii\base\Exception
         */
        protected function fakeUserForeign()
        {
            $foreignUser = $this->fakeUser();
            $foreignUser->nationality = NationalityInterface::NATIONALITY_FOREIGNER;

            if (!$foreignUser->save()) {
                throw new \RuntimeException('Иностранный пользователь не создан '
                    . $foreignUser->getJsonErrors());
            }

            return $foreignUser;
        }

        /**
         * @param User|null $agentUser
         * @param null $status
         * @param array $attributes
         * @return AgentRequest
         * @throws \yii\base\Exception
         */
        protected function fakeAgentRequest(User $agentUser = null, $status = null, array $attributes = [])
        {
            $companyInn = $attributes['company_inn'] ?? null;
            if (!$companyInn) {
                $company = $this->fakeCompany();
            }

            $agentReq = new AgentRequest();
            $agentReq->setAttributes($attributes);

            if (!$agentReq->company_inn) {
                $agentReq->company_inn = $company->getInn();
            }
            $agentReq->agent_id = $agentUser ? $agentUser->getId() : $this->fakeAgent()->getId();
            $agentReq->status   = !is_null($status) ? $status : AgentRequest::STATUS_APPROVED;

            if (!$agentReq->save()) {
                throw new \RuntimeException('Запрос на регистрацию не создан '
                    . $agentReq->getJsonErrors());
            }

            return $agentReq;
        }

        /**
         * @param string|null $inn
         * @param User|null $contactUser
         * @return Company
         * @throws \Exception
         */
        protected function fakeCompany(string $inn = null, User $contactUser = null): Company
        {
            $this->getName();

            $this->debug("Company INN: " . var_export($inn, true));
            
            $company = $this->fakeCompanyRaw($inn);

            if (!$company->save()) {
                throw new \RuntimeException('Failed to create fake company: ' . $company->getJsonErrors());
            }

            //Создаем контакт компании
            if($contactUser === true) {
                $contactUser = $this->fakeUser();
            }
            if ($contactUser) {
                $company->link('contact', $contactUser);
                $this->fakeCompanyStaff($company, $contactUser);
            }

            return $company;
        }

        /**
         * @param string|null $inn
         *
         * @return Company
         * @throws \Exception
         */
        protected function fakeCompanyClient(string $inn = null, User $contactUser = null): Company
        {
            $company = $this->fakeCompany($inn, $contactUser);

            if (!$company->save()) {
                throw new \RuntimeException('Компания не создана ' . $company->getJsonErrors());
            }

            $companyClient = (new Client())->setAttributes([
                'goal_bank_guarantee' => false,
                'goal_bank_credit'    => true,
            ]);

            $companyClient->link('company', $company);

            if (!$companyClient->save()) {
                throw new \RuntimeException('Клиент (компания) не создан ' . $companyClient->getJsonErrors());
            }

            return $company;
        }

        /**
         * @return Company
         * @throws \Exception
         */
        protected function fakeCompanyBank(): Company
        {
            $company = $this->fakeCompanyRaw();

            if (!$company->save()) {
                throw new \RuntimeException('Компания не создана ' . $company->getJsonErrors());
            }

            $companyBank = (new Bank())->setAttributes([
                'bic'                   => '1',
                'correspondent_account' => '1',
            ]);
            $companyBank->link('company', $company);

            if (!$companyBank->save()) {
                throw new \RuntimeException('Банк (компания) не создана ' . $companyBank->getJsonErrors());
            }

            $legalAddress = $this->fakeAddress(Address::TYPE_LEGAL);
            $company->link('legalAddress', $legalAddress);

            return $company;
        }

        /**
         * @param Company $company
         * @param string  $type
         *
         * @return Debt
         */
        protected function fakeCompanyDebt(Company $company, $type = Debt::TYPE_TAX): Debt
        {
            $debt = new Debt();
            $debt->setAttributes([
                'company_id' => $company->id,
                'type'       => $type,
                'amount'     => 100,
                'creditor'   => $type === Debt::TYPE_STAFF ? '' : 'Creditor',
                'started_at' => time(),
            ]);

            if (!$debt->save()) {
                throw new \RuntimeException('Задолженность не создана ' . $debt->getJsonErrors());
            }

            return $debt;
        }

        /**
         * @param Company $company
         *
         * @return BankAccount
         */
        protected function fakeCompanyBankAccount(Company $company)
        {
            $faker = $this->getFaker();

            $account = new BankAccount();
            $account->setAttributes([
                'company_id'      => $company->id,
                'bic'             => $faker->sentence,
                'number'          => $faker->sentence,
                'name'            => $faker->sentence,
                'unpaid_invoices' => 1,
            ]);

            if (!$account->save()) {
                throw new \RuntimeException('Банковский аккаунт создан не был ' . $account->getJsonErrors());
            }

            return $account;
        }

        /**
         * @param null|string $inn
         * @param array       $attributes
         *
         * @return RequestEgrul
         */
        protected function fakeRequestEgrul(?string $inn = null, array $attributes = []): RequestEgrul
        {
            $request = (new RequestEgrul())->setAttributes([
                    'company_inn' => $inn ?? $this->getFakerInn(),
                ] + $attributes);

            if (!$request->save()) {
                throw new \RuntimeException('RequestEgrul не создан ' . $request->getJsonErrors());
            }

            return $request;
        }

        /**
         * @return Supervisor|__anonymous@16030
         * @throws \Exception
         */
        protected function fakeMinerSupervisor()
        {
            $user = $this->fakeUser();
            $client = $this->fakeWarrantyCompany();

            return new class($this->fakeWarrantyUpdated($user, $client)) extends Supervisor
            {
                public function __construct($warranty)
                {
                    $this->warranty = $warranty;
                }
            };
        }

        /**
         * @param int $requestId
         * @param int $userId
         * @return UserRequestInn
         */
        protected function fakeUserRequestInn(int $requestId, int $userId): UserRequestInn
        {
            $request = (new UserRequestInn())->setAttributes([
                'request_id' => $requestId,
                'user_id'    => $userId,
            ]);

            if (!$request->save()) {
                throw new \RuntimeException('UserRequestInn не создан ' . $request->getJsonErrors());
            }

            return $request;
        }

        /**
         * @param string|null $inn
         *
         * @return Company
         * @throws \Exception
         */
        protected function fakeCompanyRaw(string $inn = null): Company
        {
            return (new Company())->setAttributes($this->fakeCompanyData($inn));
        }

        /**
         * @param string $type
         *
         * @return Address
         * @throws \Exception
         */
        protected function fakeAddress(string $type = Address::TYPE_PHYSICAL): Address
        {
            $address = (new Address())->setAttributes($this->fakeAddressData(['type' => $type]));

            if (!$address->save()) {
                throw new \RuntimeException('Адрес не создан ' . $address->getJsonErrors());
            }

            return $address;
        }

        /**
         * @param Company|null $company
         * @param User|null    $user
         *
         * @return CompanyStaff
         * @throws \Exception
         * @throws \yii\base\Exception
         */
        protected function fakeCompanyStaff(Company $company = null, User $user = null, $params = []): CompanyStaff
        {
            $company = $company ?? $this->fakeCompany();
            $user    = $user ?? $this->fakeUser();

            return $this->_fakeCompanyStaff($user, $company, $params);
        }

        /**
         * @param User $user
         * @param Company $company
         * @param array $params
         * @return CompanyStaff
         * @throws \Exception
         */
        protected function _fakeCompanyStaff(User $user, Company $company, $params = []): CompanyStaff
        {
            $faker = $this->getFaker();

            $staff = (new CompanyStaff())->setAttributes($params + [
                'company_id'    => $company->getId(),
                'user_id'       => $user->getId(),
                'position'      => $faker->sentence,
                'share_rubles'  => $faker->sentence,
                'share_percent' => random_int(0, 100),
            ]);

            if (!$staff->save()) {
                throw new \RuntimeException('Сотрудник uid:'
                    . $user->getId() . ' не зарегистрирован в компании '
                    . $company->getId() . ' '
                    . $staff->getJsonErrors());
            }

            return $staff;
        }

        /**
         * @param User|null $user
         * @param Company|null $company
         * @return StaffRequest
         * @throws \yii\base\Exception
         */
        protected function fakeStaffRequest(User $user = null, Company $company = null) {
            $company = $company ?? $this->fakeCompany();
            $user    = $user ?? $this->fakeUser();

            $staffRequest = (new StaffRequest())->setAttributes([
                'user_id'     => $user->getId(),
                'company_inn' => $company->getInn(),
            ]);

            if (!$staffRequest->save()) {
                throw new \RuntimeException('Запрос не создан: добавление сотрудника uid: '
                    . $user->getId() . ' в компанию '
                    . $company->getId() . ' '
                    . $staffRequest->getJsonErrors());
            }

            return $staffRequest;

        }

        /**
         * @param Company|null $company
         * @param User|null    $user
         *
         * @return BankStaff
         * @throws \yii\base\Exception
         * @throws \Exception
         */
        protected function fakeBankStaff(Company $company = null, User $user = null): BankStaff
        {
            $company = $company ?? $this->fakeCompany();
            $user    = $user ?? $this->fakeUser();

            return $this->_fakeBankStaff($user, $company);
        }

        /**
         * @param User $user
         * @param Company $company
         * @return BankStaff
         * @throws \Exception
         */
        protected function _fakeBankStaff(User $user, Company $company): BankStaff
        {
            $faker = $this->getFaker();

            $staff = (new BankStaff())->setAttributes([
                'company_id'   => $company->getId(),
                'user_id'      => $user->getId(),
                'position'     => $faker->sentence,
            ]);

            if (!$staff->save()) {
                throw new \RuntimeException('Сотрудник uid: '
                    . $user->getId()
                    . ' не зарегистрирован в компании '
                    . $company->getId()
                    . '. '
                    . $staff->getJsonErrors());
            }

            return $staff;
        }

        /**
         * @return User
         * @throws \yii\base\Exception
         */
        protected function fakeAgent()
        {
            $user = new User();

            $attributes = $this->fakeUserData(['role' => User::ROLE_AGENT]);
            $attributes = CommonHelper::toUnderscore($attributes);

            if (!$user->setAttributes($attributes)->hashPasswordField()->save()) {
                throw new \RuntimeException('Пользователь не создан ' . $user->getJsonErrors());
            }

            $group = $this->fakeGroup();
            $this->fakePermission($user, $group,
                PermissionType::AGENT_CAN_SERVE_CLIENT);
            $this->fakePermission($user, $group,
                PermissionType::AGENT_CAN_SERVE_COMPANY);

            return $user;
        }

        /**
         * @param Company|null $company
         * @param User|null    $user
         *
         * @return AgentStaff
         * @throws \yii\base\Exception
         * @throws \Exception
         */
        protected function fakeAgentStaff(Company $company = null, User $user = null): AgentStaff
        {
            $company = $company ?? $this->fakeCompany();
            $user    = $user ?? $this->fakeAgent();

            return $this->_fakeAgentStaff($user, $company);
        }

        /**
         * @param User $user
         * @param Company $company
         * @return AgentStaff
         * @throws \Exception
         */
        protected function _fakeAgentStaff(User $user, Company $company): AgentStaff
        {
            $faker = $this->getFaker();

            $staff = (new AgentStaff())->setAttributes([
                'company_id'   => $company->getId(),
                'user_id'      => $user->getId(),
                'position'     => $faker->sentence,
            ]);

            if (!$staff->save()) {
                throw new \RuntimeException('Сотрудник uid: '
                    . $user->getId()
                    . ' не зарегистрирован в компании '
                    . $company->getId()
                    . '. '
                    . $staff->getJsonErrors());
            }

            return $staff;
        }

        /**
         * @param string $action
         * @param User   $user
         *
         * @return UserHashes
         * @throws \Exception
         * @throws \yii\base\Exception
         */
        protected function fakeHash(User $user, $action = UserHashes::ACTION_APPROVE)
        {
            $hash = (new UserHashes())->setAttributes([
                'user_id'    => $user->getId(),
                'action'     => $action,
                'disable_at' => null,
            ])->genHashString();

            if (!$hash->save()) {
                throw new \RuntimeException('Хэш не создан '
                    . $hash->getJsonErrors());
            }

            return $hash;
        }

        /**
         * @param User $user
         *
         * @return UserEds
         * @throws \Exception
         */
        protected function fakeEds(User $user)
        {
            $faker   = Factory::create();
            $userEds = new UserEds();

            $userEds->setAttributes(
                [
                    'user_id'    => $user->getId(),
                    'thumbprint' => sha1($user->getId() . random_int(100, 999999)),
                    'owner'      => $faker->lastName,
                    'until_date' => (new \DateTime('+1 year'))->getTimestamp(),
                    'is_deleted' => 0,
                ]
            );

            if (!$userEds->save()) {
                throw new \RuntimeException('Фейковый ЭЦП не создан '
                    . $userEds->getJsonErrors());
            }

            return $userEds;
        }

        /**
         * @param string $type
         *
         * @return FileType
         */
        protected function fakeFileType($type = null): FileType
        {
            $faker = $this->getFaker();

            $fileType = (new FileType())->setAttributes([
                'name'      => $faker->sentence,
                'type'      => $type ? $type : $faker->sentence,
                'ttl'       => 86400,
                'validator' => [],
            ]);

            if (!$fileType->save()) {
                throw new \RuntimeException('Тип файла не создан '
                    . $fileType->getJsonErrors());
            }

            return $fileType;
        }

        /**
         * @param string|null $entityId
         * @param string|null $type
         * @return File
         * @throws \yii\base\Exception
         */
        protected function fakeFile(string $entityId = null, string $type = null): File
        {
            if (!$entityId) {
                $entityId = $this->fakeUser()->getId();
                $type     = File::TYPE_PASSPORT;
            }

            $faker = $this->getFaker();

            $file = (new File())->setAttributes([
                'id'        => $faker->regexify('[a-f0-9]{24}'),
                'entity_id' => $entityId,
                'type'      => $type,
            ]);

            if (!$file->save()) {
                throw new \RuntimeException('Failed to create file '
                    . $file->getJsonErrors());
            }

            $this->log("Created new fake file for {$entityId} / {$type}");

            return $file;
        }

        /**
         * @throws \yii\base\Exception
         */
        protected function fakeCompanyWithFiles()
        {
            $fakeCompany = $this->fakeCompany();

            foreach (CompanyDocumentsValidator::getFileTypes() as $fileType) {
                $this->fakeFile($fakeCompany->getId(), $fileType);
            }

            return $fakeCompany;
        }

        /**
         * @param Company $company
         *
         * @return Registrar
         */
        protected function fakeRegistrar(Company $company): Registrar
        {
            $faker = $this->getFaker();

            $registrar = (new Registrar())->setAttributes([
                'company_id' => $company->getId(),
                'number'     => $faker->sentence,
                'name'       => $faker->name,
                'address'    => $faker->sentence,
                'date'       => date('Y-m-d'),
            ]);

            if (!$registrar->save()) {
                throw new \RuntimeException('Регистратор не создан '
                    . $registrar->getJsonErrors());
            }

            return $registrar;
        }

        /**
         * @param $date
         * @param Company $company
         * @return array
         * @throws \Common\Exception\ValidationException
         */
        protected function fakeReport($date, Company $company): array
        {
            $lines = Line::find()->limit(6)->all();
            $data  = [];

            if (!is_array($date)) {
                $date = [$date];
            }

            foreach ($date as $_date) {
                $this->fakeSingleReport($lines, $_date, $company, $data);
            }

            $repository = new ReportRepository();
            if (!$repository->batch($data, ['date', 'book_keeping_line_id', 'company_id', 'value', 'created_at'])) {
                throw new \RuntimeException('Batch insert вернул 0 для сохранения финансовой отчетности');
            }

            return $data;
        }

        /**
         * @param array $attributes
         * @param string $tenderId
         * @return array
         * @throws \Exception
         */
        protected function fakeWarrantyData(array $attributes, $tenderId = '0187300010316002901'): array
        {
            $fakePath = \Yii::getAlias('@tests/stub') . '/warranty/response_success_' . $tenderId . '.json';

            if (file_exists($fakePath)) {
                $this->log("Found stored fake data: {$tenderId}");
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
            $bank = $this->fakeCompanyBank();

            return [
                'tender_info' => $tenderInfo,
                'warranty_id' => $this->getTextId(),
                'bank_id'     => $bank->getId(),
            ] + $attributes;
        }

        /**
         * @param User|null $user
         * @param Company|null $company
         * @param bool $status
         * @param string $tenderId
         * @return WarrantyOrder
         * @throws \yii\base\Exception
         */
        protected function fakeWarranty(
            User $user = null,
            Company $company = null,
            string $tenderId = '0187300010316002901'
        ): WarrantyOrder {
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
         * @return WarrantyOrder
         * @throws \Throwable
         * @throws \yii\base\Exception
         */
        protected function fakeWarrantyWithDocuments(User $user = null, Company $company = null)
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
        protected function fakeWarrantyWithStatus($status, User $user = null, Company $company = null, $filledForm = null)
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
                    'provision' => 'bid',
                    'lots'      => [
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
            $wo = (new WarrantyOrder())->setAttributes($attributes);
            $this->assertTrue($wo->save(),
                'Фейковая заявка гарантии не создана ' . $wo->getJsonErrors());

            $this->setStatusLog($wo, $status);

            return $wo;
        }

        /**
         * @param WarrantyEventSettings $event
         * @param User|null $user
         * @param Company|null $company
         * @return WarrantyOrder
         * @throws \Common\Exception\ValidationException
         * @throws \Throwable
         * @throws \yii\base\Exception
         */
        protected function fakeWarrantyWithEvent(WarrantyEventSettings $event, User $user = null, Company $company = null)
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
         * @return bool
         * @throws \Throwable
         */
        protected function setStatusLog(WarrantyOrder $warrantyOrder, $currentStatus)
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
        protected function fakeWarrantyUpdated(User $user, Company $company, $beneficiaryCreate = false): WarrantyOrder
        {
            $wo = $this->fakeWarrantyWithStatus(WarrantyOrder::STATUS_SENT, $user, $company);

            //Создаем связь с бенефициаром
            $beneficiaryInn = $wo->getBeneficiaryInn();
            $beneficiary = Company::findOne(['inn' => $beneficiaryInn]);
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
        protected function fakeWarrantyNotFilled(User $user, Company $company)
        {
            return $this->fakeWarrantyWithStatus(WarrantyOrder::STATUS_SENT, $user, $company, $this->getFaker()->sentence);
        }

        /**
         * @param array $attributes
         * @return User
         * @throws \yii\base\Exception
         */
        protected function fakeWarrantyUser(array $attributes = []): User
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

                if(isset($passportAttributes['file']) && $passportAttributes['file']) {
                    $this->fakeFile($passport->getId(), File::TYPE_PASSPORT);
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

                if(isset($foreignPassportAttr['file']) && $foreignPassportAttr['file']) {
                    $this->fakeFile($foreignPassport->getId(), File::TYPE_FOREIGN_PASSPORT);
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

                if(isset($foreignResidenceAttr['file']) && $foreignResidenceAttr['file']) {
                    $this->fakeFile($foreignResidence->getId(), File::TYPE_MIGRATION_RESIDENCE);
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

                if(isset($foreignMigrationAttr['file']) && $foreignMigrationAttr['file']) {
                    $this->fakeFile($foreignMigration->getId(), File::TYPE_MIGRATION_CARD);
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
        protected function fakeWarrantyFilledUser(): User
        {
            $faker = $this->getFaker();

            $attributes = [
                'passport' => [
                    'address' => [
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
            $this->fakeFile($user->passport()->getId(), File::TYPE_PASSPORT);

            return $user;
        }

        /**
         * @param array $attributes
         * @param bool $failTest
         *
         * @return Company
         * @throws \yii\base\Exception
         */
        protected function fakeWarrantyCompany(array $attributes = [], $failTest = false): Company
        {
            $attributes = CommonHelper::toUnderscore($attributes);
            //т.к в фейковую заявку записываем filled_form умных предложений, формируем эту компанию
            $attributes['name']  = 'Общество с ограниченной ответственностью «Умные предложения»"';
            $attributes['alias'] = 'Общество с ограниченной ответственностью «Умные предложения»"';
            $attributes['ogrn']  = '5177746361469';
            $company    = (new Company())->setAttributes($attributes + $this->fakeCompanyData('7714420892'));

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

                $this->log("Creating fake registrar file");
                $this->fakeFile($fakeRegistrar->getId(), File::TYPE_COMPANY_REGISTRAR_OGRN);
            }

            return $company;
        }

        /**
         * @param array     $lines
         * @param \DateTime $date
         * @param Company   $company
         * @param           $data
         */
        protected function fakeSingleReport(array $lines, \DateTime $date, Company $company, &$data)
        {
            foreach ($lines as $line) {
                $data[] = (new Report())->setAttributes([
                    'date'                 => $date->format('Y-m-d'),
                    'book_keeping_line_id' => $line->getId(),
                    'company_id'           => $company->getId(),
                    'value'                => array_rand([mt_rand(0, 2000), null]),
                    'created_at'           => time(),
                ]);
            }
        }

        /**
         * @param $fileId
         * @param User $user
         * @return DocumentVerify
         * @throws \Exception
         */
        protected function fakeDocVerify($fileId, User $user): DocumentVerify
        {
            $company = $this->fakeCompany();
            $this->fakeBankStaff($company, $user);

            $attributes = [
                'fileId'     => $fileId,
                'bankUserId' => $user->getId(),
                'bankId'     => $company->getId(),
                'status'     => DocumentVerify::getStatuses()[random_int(0, 1)],
            ];
            $attributes = CommonHelper::toUnderscore($attributes);

            $verifyDoc = (new DocumentVerify())->setAttributes($attributes);

            if (!$verifyDoc->save()) {
                throw new \RuntimeException('Проверка документа банком не создана '
                    . $verifyDoc->getJsonErrors());
            }
            return $verifyDoc;
        }

        /**
         * @param User $user
         * @param WarrantyOrder $warranty
         * @param bool $fakeFiles
         * @return WarrantyMessage
         * @throws \yii\base\Exception
         * @throws \yii\mongodb\Exception
         */
        protected function fakeMessage(User $user, WarrantyOrder $warranty, bool $fakeFiles = false): WarrantyMessage
        {
            $source = WarrantyMessage::getSources()[random_int(0, 1)];
            if ($fakeFiles) {
                $fakeFile  = $this->fakeFile($warranty->getId() . ':' . 0, File::TYPE_WARRANTY_MESSAGE_ATTACHMENT);
                $fakeFile2 = $this->fakeFile($warranty->getId() . ':' . 1, File::TYPE_WARRANTY_MESSAGE_ATTACHMENT);
                $attachments = [
                    [
                        "id"   => $fakeFile->getId(),
                        "hash" => $this->getFaker()->sentence,
                    ],
                    [
                        "id"   => $fakeFile2->getId(),
                        "hash" => $this->getFaker()->sentence,
                    ],
                ];
            }

            $attributes = [
                'source'      => $source,
                'user_id'     => $user->getId(),
                'warranty_id' => $warranty->getId(),
                'is_deleted'  => 0,
                'content'     => [
                    'message'    => $this->getFaker()->sentence,
                    'attachments' => $attachments ?? [],
                ],
                'signed_hash' => $this->getFaker()->sentence,
            ];

            $message = new WarrantyMessage();
            $message->message_id = $warranty->messages ? count($warranty->messages) : 0;
            $message->setAttributes($attributes + [
                'user_id' => $user->getId(),
                'source'  => $user->getCompanyBank() ? WarrantyMessage::SOURCE_BANK : WarrantyMessage::SOURCE_CLIENT,
            ]);
            $message->setCreatedTime();

            $message->validate();
            if($message->getErrors()) {
                throw new \RuntimeException('Сообщение привязанное к гарантии не создано '
                    . $message->getJsonErrors());
            }

            try {
                $warranty->getCollection()->update(
                    ['_id' => $warranty->_id],
                    ['$push' => ['messages' => $message->toArray()]]
                );
            } catch (Exception $e) {
                throw new \RuntimeException('Сообщение привязанное к гарантии не создано '
                    . $e->getMessage());
            }

            return $message;
        }

        /**
         * @param User $user
         * @param null $attributes
         * @return EmailTask
         * @throws \Exception
         */
        protected function fakeEmailRequest(User $user, $attributes = null): EmailTask
        {
            $faker = $this->getFaker();

            if (!$attributes) {
                $attributes = [
                    'email'   => $user->getEmail(),
                    'status'  => EmailTask::STATUS_AWAIT,
                    'message' => $faker->sentence,
                    'subject' => $faker->sentence,
                ];
            } else {
                $attributes = $attributes + ['email' => $user->getEmail()];
            }

            $confirm = (new EmailTask())->setAttributes($attributes);

            if (!$confirm->save()) {
                throw new \RuntimeException('Запрос на почтовое уведомление не создан '
                    .$confirm->getJsonErrors());
            }
            return $confirm;
        }

        /**
         * @throws \yii\base\Exception
         */
        public function fakeLoginUser()
        {
            $fakeUser = $this->fakeUser();
            $login = \Yii::$app->getUser()->login($fakeUser);

            $this->assertTrue($login, 'Не удалось авторизовать пользователя!');

            return $fakeUser;
        }

        protected function fakePaperTemplate($bank = null, $negotiable = false)
        {
            $faker = $this->getFaker();
            $bank = $bank ? $bank : $this->fakeCompanyBank();

            $paperTemplate = new PaperTemplate();
            $textId = substr(md5(random_int(1, 99999)), 0, 8);
            $paperTemplate->setAttributes([
                PaperTemplate::TEMPLATE_ID   => $textId,
                PaperTemplate::BANK_ID       => $bank->getId(),
                PaperTemplate::NAME          => $faker->sentence,
                PaperTemplate::BODY          => $faker->sentence,
                PaperTemplate::ICON          => $faker->sentence,
                PaperTemplate::NEGOTIABLE    => $negotiable,
                PaperTemplate::SIGNED        => false,
                PaperTemplate::TEMPLATE_TYPE => PaperTemplate::TYPE_WARRANTY,
            ]);

            $this->debug("New fake template generated for {$bank->getId()}: {$textId}");

            if (!$paperTemplate->save()) {
                throw new \RuntimeException('Фейковый paper шаблон не создан '
                    . $paperTemplate->getJsonErrors());
            }
            return $paperTemplate;
        }

        /**
         * @param WarrantyOrder $warranty
         * @return PaperItem
         * @throws \Exception
         */
        protected function fakePaperItem(WarrantyOrder $warranty, $negotiate = false)
        {
            $faker = $this->getFaker();
            $bank  = $this->fakeCompanyBank();
            $paperTemplate = $this->fakePaperTemplate($bank, $negotiate);

            $id = $paperTemplate->getId();

            $attributes = [
                PaperItem::TEMPLATE_ID => $paperTemplate->getId(),
                PaperItem::WARRANTY_ID => $warranty->getId(),
                PaperItem::VARIABLES   => json_encode([]),
                PaperItem::DESCRIPTION => $faker->sentence,
            ];

            $paper = (new PaperItem())->setAttributes($attributes);

            if (!$paper->save()) {
                throw new \RuntimeException('Фейковый paper item не создан '
                    . $paper->getJsonErrors());
            }
            return $paper;
        }

        /**
         * @return PermissionGroup
         */
        public function fakeGroup()
        {
            $group = new PermissionGroup();
            $group->setAttributes([
                PermissionGroup::GROUP_NAME  => $this->getFaker()->word,
                PermissionGroup::DESCRIPTION => $this->getFaker()->sentence,
                PermissionGroup::TEXT_ID     => 'x' . ((string)rand()),
            ], false);

            if (!$group->save()) {
                throw new \RuntimeException('Группа прав не создана '
                    . $group->getJsonErrors());
            }

            return $group;
        }

        /**
         * @param $group
         * @param null $permissionType
         * @param null $vars
         * @return PermissionTemplate
         */
        public function fakeTemplates($group, $permissionType = null, $vars = null)
        {
            $tpl = new PermissionTemplate();
            $tpl->setAttributes([
                PermissionTemplate::GROUP_ID  => $group->group_id,
                PermissionTemplate::CODE      => $permissionType ?? PermissionType::CAN_APPROVE_BG,
                PermissionTemplate::VARIABLES => json_encode($vars ?? ['max' => 1000]),
            ], false);

            $this->assertTrue($tpl->save(), 'Фейковый шаблон группы не создан'
                . $tpl->getJsonErrors());

            return $tpl;
        }

        /**
         * @param User $user
         * @param PermissionGroup $group
         * @param string $code
         * @param array $options
         * @return Item
         */
        public function fakePermission(User $user, PermissionGroup $group, $code = PermissionType::CAN_ISSUE_BG, array $options = [])
        {
            $permission = new Item();
            $permission->setAttributes([
                Item::GROUP_ID   => $group->group_id,
                Item::USER_ID    => $user->getId(),
                Item::CODE       => $code,
                Item::CREATED_AT => time(),
                Item::UPDATED_AT => time(),
                Item::VARIABLES  => json_encode($options),
            ], false);

            $this->assertTrue($permission->save(), 'Фейковое правило не создано'
                . $permission->getJsonErrors());
            
            return $permission;
        }
    }
}
